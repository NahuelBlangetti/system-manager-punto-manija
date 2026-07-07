<?php

namespace App\Jobs;

use App\Filament\Pages\ValidarImport;
use App\Filament\Support\ProductUnitNormalizer;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImport;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Process\Process;

class ProcessImportFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public bool $failOnTimeout = true;

    public function __construct(public int $importId) {}

    public function handle(): void
    {
        $import = ProductImport::findOrFail($this->importId);
        $import->update(['status' => 'processing']);

        $failedChunks = 0;

        try {
            $path = Storage::disk('local')->path($import->file_path);
            $extension = strtolower(pathinfo($import->filename, PATHINFO_EXTENSION));

            $text = $extension === 'pdf'
                ? $this->extractFromPdf($path)
                : $this->extractFromSpreadsheet($path);

            if (mb_strlen(trim($text)) < 20) {
                throw new \RuntimeException('No se pudo extraer texto del archivo. ¿Es un PDF escaneado o un Excel vacío?');
            }

            $text = $this->cleanNoise($text);
            $chunkResult = $this->chunkText($text);
            $chunks = $chunkResult['chunks'];

            $rawProducts = [];

            foreach ($chunks as $index => $chunk) {
                try {
                    $rawProducts = array_merge($rawProducts, $this->callOpenAi($chunk));
                } catch (\Throwable $e) {
                    $failedChunks++;
                    Log::warning('Import chunk failed', [
                        'importId' => $import->id,
                        'chunkIndex' => $index,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (empty($rawProducts) && $failedChunks > 0) {
                throw new \RuntimeException('No se pudo procesar ninguna sección del archivo con la IA. Intentá nuevamente más tarde.');
            }

            $products = $this->buildProductRows($rawProducts);

            $totalChunks = count($chunks);
            $truncationNote = $chunkResult['truncated']
                ? "El archivo es muy grande: se procesaron {$chunkResult['used']} de {$chunkResult['total']} secciones."
                : null;

            $import->update([
                'products' => $products,
                'product_count' => count($products),
                'status' => 'done',
                'processed_at' => now(),
                'error_message' => $truncationNote,
            ]);

            Storage::disk('local')->delete($import->file_path);

            $body = "Se encontraron {$import->product_count} productos en {$import->filename}.";
            if ($failedChunks > 0) {
                $body .= ' Se procesaron '.($totalChunks - $failedChunks)." de {$totalChunks} secciones; revisá el log para más detalle.";
            }
            if ($truncationNote) {
                $body .= ' '.$truncationNote;
            }

            Notification::make()
                ->title('Importación lista')
                ->body($body)
                ->success()
                ->actions([
                    Action::make('ver')
                        ->label('Revisar')
                        ->url(ValidarImport::getUrl(['id' => $import->id]))
                        ->button(),
                ])
                ->sendToDatabase($import->user);
        }
    }

    public function failed(\Throwable $e): void
    {
        $import = ProductImport::find($this->importId);

        if (! $import) {
            return;
        }

        $import->update([
            'status' => 'error',
            'error_message' => $e->getMessage(),
        ]);

        if ($import->user) {
            Notification::make()
                ->title('Error al procesar la importación')
                ->body($e->getMessage())
                ->danger()
                ->sendToDatabase($import->user);
        }

        try {
            if ($webhookUrl = config('services.discord.webhook_url')) {
                Http::post($webhookUrl, [
                    'content' => "Import #{$this->importId} failed: {$e->getMessage()}",
                ]);
            }
        } catch (\Throwable $ignored) {
            // Never let a Discord notification failure mask the real error.
        }
    }

    private function extractFromPdf(string $path): string
    {
        $process = new Process(['pdftotext', '-layout', $path, '-']);
        $process->setTimeout(120);
        $process->run();

        return trim($process->getOutput());
    }

    private function extractFromSpreadsheet(string $path): string
    {
        $spreadsheet = IOFactory::load($path);
        $lines = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            foreach ($sheet->toArray(null, true, true, false) as $row) {
                $cells = array_map(fn ($cell) => trim((string) $cell), $row);
                $line = implode(' | ', $cells);

                if (trim($line, ' |') !== '') {
                    $lines[] = $line;
                }
            }
        }

        return implode("\n", $lines);
    }

    private function cleanNoise(string $text): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);

        $counts = array_count_values(array_map('trim', $lines));

        $lines = array_filter($lines, function ($line) use ($counts) {
            $trimmed = trim($line);

            if (mb_strlen($trimmed) < 3) {
                return false;
            }

            if (preg_match('/^[-=_\s]+$/', $trimmed)) {
                return false;
            }

            if (($counts[$trimmed] ?? 0) > 3) {
                return false;
            }

            return true;
        });

        return implode("\n", $lines);
    }

    /**
     * @return array{chunks: list<string>, truncated: bool, total: int, used: int}
     */
    private function chunkText(string $text, ?int $chunkSize = null, ?int $maxChunks = null): array
    {
        $chunkSize = $chunkSize ?? config('services.import.chunk_size', 4000);
        $maxChunks = $maxChunks ?? config('services.import.max_chunks', 25);

        $lines = explode("\n", $text);
        $chunks = [];
        $current = '';

        foreach ($lines as $line) {
            if (mb_strlen($current) + mb_strlen($line) + 1 > $chunkSize && $current !== '') {
                $chunks[] = $current;
                $current = '';
            }

            $current .= ($current === '' ? '' : "\n").$line;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        $total = count($chunks);
        $truncated = $total > $maxChunks;

        if ($truncated) {
            Log::warning('Import truncated', ['totalChunks' => $total, 'usedChunks' => $maxChunks]);
            $chunks = array_slice($chunks, 0, $maxChunks);
        }

        return [
            'chunks' => $chunks,
            'truncated' => $truncated,
            'total' => $total,
            'used' => count($chunks),
        ];
    }

    private function callOpenAi(string $chunk): array
    {
        $apiKey = config('services.openai.key');

        if (empty($apiKey)) {
            throw new \RuntimeException('No está configurada la clave OPENAI_API_KEY.');
        }

        $prompt = <<<PROMPT
Sos un asistente especializado en extracción de datos de catálogos y listas de precios de proveedores argentinos.

Del siguiente texto, identificá todos los productos y devolvé ÚNICAMENTE un objeto JSON válido con esta forma exacta:
{"products":[{"name":"nombre completo","sku":"codigo interno o cadena vacia","barcode":"codigo de barras o cadena vacia","unit":"unidad de medida (unidad, metro, m2, kg, g, litro, caja, rollo, par o docena)","cost_price":0,"sale_price":0,"stock":0,"category":"sugerencia de categoria en español"}]}

Reglas obligatorias:
- Precios en pesos argentinos como número (sin \$, sin puntos, sin comas).
- Si hay precio mayorista y minorista, usar el minorista (precio al público) como sale_price.
- Si no hay precio de costo visible, usar 0.
- Si no hay stock visible, usar 0.
- Sin markdown, sin bloques de código, sin texto antes o después del JSON.

TEXTO:
{$chunk}
PROMPT;

        $response = Http::timeout(90)
            ->retry(2, 1000, fn ($exception) => $exception instanceof ConnectionException)
            ->withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Error en la API de OpenAI: código '.$response->status());
        }

        $content = $response->json('choices.0.message.content', '');
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded) || ! isset($decoded['products'])) {
            throw new \RuntimeException('La IA no devolvió un formato válido para esta sección.');
        }

        return $decoded['products'];
    }

    private function buildProductRows(array $rawProducts): array
    {
        $categoriesByName = Category::orderBy('name')->pluck('id', 'name')->toArray();

        $seenNames = [];
        $seenBarcodes = [];
        $rows = [];

        foreach ($rawProducts as $item) {
            $name = trim($item['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $barcode = trim($item['barcode'] ?? '');
            $normalizedName = Str::lower($name);

            $internalDuplicate = isset($seenNames[$normalizedName])
                || ($barcode !== '' && isset($seenBarcodes[$barcode]));

            $seenNames[$normalizedName] = true;
            if ($barcode !== '') {
                $seenBarcodes[$barcode] = true;
            }

            $existing = $barcode !== ''
                ? Product::where('barcode', $barcode)->first()
                : Product::whereRaw('LOWER(name) = ?', [$normalizedName])->first();

            $costPrice = max(0, (float) ($item['cost_price'] ?? 0));
            $salePrice = max(0, (float) ($item['sale_price'] ?? 0));

            $action = $existing ? 'update' : 'create';
            $priceDirection = null;
            $existingProductId = null;
            $existingCostPrice = null;
            $existingSalePrice = null;

            if ($existing) {
                $existingProductId = $existing->id;
                $existingCostPrice = (float) $existing->cost_price;
                $existingSalePrice = (float) $existing->sale_price;
                $priceDirection = match (true) {
                    $costPrice > $existingCostPrice => 'up',
                    $costPrice < $existingCostPrice => 'down',
                    default => 'same',
                };
            }

            $rows[] = [
                'selected' => ! $internalDuplicate,
                'internal_duplicate' => $internalDuplicate,
                'action' => $action,
                'existing_product_id' => $existingProductId,
                'existing_cost_price' => $existingCostPrice,
                'existing_sale_price' => $existingSalePrice,
                'price_direction' => $priceDirection,
                'name' => $name,
                'sku' => trim($item['sku'] ?? ''),
                'barcode' => $barcode,
                'unit' => ProductUnitNormalizer::normalize($item['unit'] ?? null),
                'cost_price' => $costPrice,
                'sale_price' => $salePrice,
                'stock' => max(0, (int) ($item['stock'] ?? 0)),
                'category_id' => $this->matchCategory(trim($item['category'] ?? ''), $categoriesByName),
            ];
        }

        return $rows;
    }

    private function matchCategory(string $hint, array $categoriesByName): ?int
    {
        if ($hint === '') {
            return null;
        }

        $hint = Str::lower($hint);

        foreach ($categoriesByName as $catName => $catId) {
            if (stripos($catName, $hint) !== false || stripos($hint, $catName) !== false) {
                return $catId;
            }
        }

        return null;
    }
}

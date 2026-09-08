<?php

namespace App\Filament\Pages;

use App\Filament\Support\ProductUnitNormalizer;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImport;
use App\Models\Supplier;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;

class ValidarImport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Validar importación';

    protected static ?string $slug = 'validar-import';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.validar-import';

    public ?ProductImport $import = null;

    public array $products = [];

    public ?int $importSupplierId = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManageProducts();
    }

    public function mount(): void
    {
        $id = request()->query('id');
        $import = $id ? ProductImport::find($id) : null;

        if (! $import || $import->user_id !== Auth::id()) {
            $this->rejectImport('Importación no encontrada', 'No se encontró la importación solicitada.');

            return;
        }

        if (! $import->isReviewable()) {
            [$title, $body] = match ($import->status) {
                'processing', 'pending' => [
                    'Importación en curso',
                    'El archivo todavía se está analizando. Cuando termine, aparecerá en "Listas para revisar" y te llegará una notificación.',
                ],
                'cancelled' => [
                    'Importación cancelada',
                    'Esta importación fue cancelada. Subí el archivo nuevamente si querés importarlo.',
                ],
                'validated' => [
                    'Importación ya confirmada',
                    'Ya guardaste los productos de este archivo en el catálogo.',
                ],
                'error' => [
                    'Error al procesar',
                    $import->error_message ?? 'Hubo un error al analizar el archivo. Intentá subirlo de nuevo.',
                ],
                default => [
                    'Importación no disponible',
                    'Esta importación ya no se puede revisar.',
                ],
            };

            $this->rejectImport($title, $body);

            return;
        }

        // Reabrir una importación cancelada que aún tiene productos.
        if ($import->isCancelled()) {
            $import->update(['status' => 'done']);
            $import->refresh();
        }

        $this->import = $import;
        $this->products = $this->sortUpdatesFirst($import->products ?? []);
        $this->form->fill(['importSupplierId' => $import->supplier_id]);
    }

    /**
     * Las actualizaciones de productos ya conocidos van primero; las altas
     * nuevas (que requieren revisar nombre, precio y categoría desde cero)
     * quedan al final.
     */
    private function sortUpdatesFirst(array $products): array
    {
        usort(
            $products,
            fn (array $a, array $b) => (($a['action'] ?? 'create') === 'create' ? 1 : 0)
                <=> (($b['action'] ?? 'create') === 'create' ? 1 : 0)
        );

        return $products;
    }

    private function rejectImport(string $title, string $body): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->warning()
            ->send();

        $this->redirect(CargarProductos::getUrl());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('importSupplierId')
                    ->label('Proveedor')
                    ->options(fn () => Supplier::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required(),
                    ])
                    ->createOptionUsing(fn (array $data) => Supplier::create($data)->getKey()),
            ]);
    }

    protected function getForms(): array
    {
        return ['form'];
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return Category::orderBy('name')->pluck('name', 'id')->toArray();
    }

    #[Computed]
    public function unitOptions(): array
    {
        return array_combine(ProductUnitNormalizer::WHITELIST, ProductUnitNormalizer::WHITELIST);
    }

    #[Computed]
    public function stats(): array
    {
        $new = $update = $duplicate = $selected = 0;

        foreach ($this->products as $product) {
            if (! empty($product['selected'])) {
                $selected++;
            }

            if (! empty($product['internal_duplicate'])) {
                $duplicate++;
            } elseif (($product['action'] ?? '') === 'update') {
                $update++;
            } else {
                $new++;
            }
        }

        return [
            'total' => count($this->products),
            'new' => $new,
            'update' => $update,
            'duplicate' => $duplicate,
            'selected' => $selected,
        ];
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function removeProduct(int $index): void
    {
        unset($this->products[$index]);
        $this->products = array_values($this->products);
    }

    public function toggleAll(bool $checked): void
    {
        foreach ($this->products as $i => $_) {
            $this->products[$i]['selected'] = $checked;
        }
    }

    public function createProducts(): void
    {
        $selected = array_values(array_filter($this->products, fn ($p) => $p['selected'] && trim($p['name']) !== ''));

        if (empty($selected)) {
            Notification::make()
                ->title('Ningún producto seleccionado')
                ->body('Marcá al menos un producto para guardar.')
                ->warning()
                ->send();

            return;
        }

        // Última verificación contra el estado actual de la base: si al revisar
        // el archivo una fila quedó marcada como "nuevo" pero su sku o código de
        // barras YA existe ahora en el catálogo (típico de una recarga mensual
        // de stock donde el nombre del producto cambió un poco y el matching por
        // nombre no lo reconoció), la tratamos como actualización de precio en
        // vez de intentar insertarla de nuevo. No tiene sentido perder el dato
        // porque el nombre no calzó exacto.
        $barcodesToCheck = collect($selected)
            ->map(fn ($row) => trim((string) ($row['barcode'] ?? '')))
            ->filter()
            ->unique();

        $skusToCheck = collect($selected)
            ->map(fn ($row) => trim((string) ($row['sku'] ?? '')))
            ->filter()
            ->unique();

        $existingByBarcode = Product::whereIn('barcode', $barcodesToCheck)->pluck('id', 'barcode');
        $existingBySku = Product::whereIn('sku', $skusToCheck)->pluck('id', 'sku');

        $created = 0;
        $updated = 0;
        $skippedDuplicates = [];
        $now = now();

        try {
            DB::transaction(function () use ($selected, $now, &$created, &$updated, &$skippedDuplicates, $existingByBarcode, $existingBySku) {
                $createRows = [];
                $seenBarcodes = [];
                $seenSkus = [];

                foreach ($selected as $row) {
                    $barcode = trim((string) ($row['barcode'] ?? ''));
                    $sku = trim((string) ($row['sku'] ?? ''));

                    $existingProductId = ! empty($row['existing_product_id'])
                        ? $row['existing_product_id']
                        : (($barcode !== '' ? $existingByBarcode[$barcode] ?? null : null)
                            ?? ($sku !== '' ? $existingBySku[$sku] ?? null : null));

                    if ($existingProductId) {
                        Product::where('id', $existingProductId)->update([
                            'name' => $row['name'],
                            'sku' => $sku ?: null,
                            'barcode' => $barcode ?: null,
                            'unit' => $row['unit'],
                            'cost_price' => $row['cost_price'],
                            'sale_price' => $row['sale_price'],
                            'stock' => (int) $row['stock'],
                            'category_id' => $row['category_id'] ?: null,
                            'supplier_id' => $this->importSupplierId,
                            'updated_at' => $now,
                        ]);
                        $updated++;

                        continue;
                    }

                    // A esta altura no hay ningún producto existente con ese código:
                    // lo único que queda por descartar es que dos filas NUEVAS del
                    // propio archivo compartan el mismo código entre sí (error de
                    // carga del proveedor) — eso sí hay que avisarlo, no hay con qué
                    // actualizar.
                    $duplicateCode = match (true) {
                        $barcode !== '' && isset($seenBarcodes[$barcode]) => "código de barras \"{$barcode}\"",
                        $sku !== '' && isset($seenSkus[$sku]) => "SKU \"{$sku}\"",
                        default => null,
                    };

                    if ($duplicateCode) {
                        $skippedDuplicates[] = "{$row['name']} ({$duplicateCode} repetido en el archivo)";

                        continue;
                    }

                    if ($barcode !== '') {
                        $seenBarcodes[$barcode] = true;
                    }
                    if ($sku !== '') {
                        $seenSkus[$sku] = true;
                    }

                    $createRows[] = [
                        'name' => $row['name'],
                        'sku' => $sku ?: null,
                        'barcode' => $barcode ?: null,
                        'unit' => $row['unit'],
                        'cost_price' => $row['cost_price'],
                        'sale_price' => $row['sale_price'],
                        'stock' => (int) $row['stock'],
                        'min_stock' => 0,
                        'category_id' => $row['category_id'] ?: null,
                        'supplier_id' => $this->importSupplierId,
                        'active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($createRows, 200) as $chunk) {
                    DB::table('products')->insert($chunk);
                }

                $created = count($createRows);
            });
        } catch (QueryException $e) {
            Notification::make()
                ->title('No se pudo guardar la importación')
                ->body('El archivo tiene un SKU o código de barras que ya existe en el catálogo. Esto es un problema del archivo del proveedor, no del sistema: corregí ese código y volvé a intentar. No se guardó ningún producto de este lote.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $this->import->update(['status' => 'validated']);
        $this->import->dismissReviewNotifications();

        Notification::make()
            ->title("{$created} creado(s), {$updated} actualizado(s)")
            ->success()
            ->send();

        if (! empty($skippedDuplicates)) {
            Notification::make()
                ->title('Se omitieron productos por código repetido en el archivo')
                ->body(
                    'Esto es un problema del archivo del proveedor, no del sistema. '
                    .count($skippedDuplicates).' producto(s) no se crearon: '
                    .implode(', ', array_slice($skippedDuplicates, 0, 5))
                    .(count($skippedDuplicates) > 5 ? '…' : '')
                    .'. Corregí el código con el proveedor y volvé a cargarlos si hace falta.'
                )
                ->warning()
                ->persistent()
                ->send();
        }

        $this->redirect(CargarProductos::getUrl());
    }

    public function cancelImport(): void
    {
        if (! $this->import || $this->import->status !== 'done') {
            return;
        }

        if ($this->import->file_path && Storage::disk('local')->exists($this->import->file_path)) {
            Storage::disk('local')->delete($this->import->file_path);
        }

        $this->import->update([
            'status' => 'cancelled',
        ]);

        $this->import->dismissReviewNotifications();

        Notification::make()
            ->title('Importación cancelada')
            ->body('No se guardó ningún producto.')
            ->success()
            ->send();

        $this->redirect(CargarProductos::getUrl());
    }
}

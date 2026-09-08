<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportFile;
use App\Models\ProductImport;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessImportFilePdfTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Todos los tests de import usan Excel/CSV; el camino de PDF (pdftotext
     * vía Symfony\Process) nunca se ejercitó de punta a punta. Generamos un
     * PDF real con dompdf (ya es dependencia del proyecto para reportes) para
     * confirmar que la extracción de texto del PDF funciona en este entorno.
     */
    public function test_a_real_pdf_is_extracted_and_processed_end_to_end(): void
    {
        Storage::fake('local');
        config(['services.openai.key' => 'test-key']);

        $user = User::factory()->create();

        $pdfContent = Pdf::loadHTML('<p>Casco MTB Pro | SKU CAS-01 | $25000</p>')->output();
        Storage::disk('local')->put('imports/lista.pdf', $pdfContent);

        $import = ProductImport::create([
            'user_id' => $user->id,
            'filename' => 'lista.pdf',
            'file_path' => 'imports/lista.pdf',
            'file_hash' => hash('sha256', $pdfContent),
            'status' => 'pending',
        ]);

        Http::fake([
            'https://api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        'products' => [
                            [
                                'name' => 'Casco MTB Pro',
                                'sku' => 'CAS-01',
                                'barcode' => '',
                                'unit' => 'unidad',
                                'cost_price' => 20000,
                                'sale_price' => 25000,
                                'stock' => 1,
                                'category' => '',
                            ],
                        ],
                    ])]],
                ],
            ], 200),
        ]);

        (new ProcessImportFile($import->id))->handle();

        $import->refresh();

        $this->assertSame('done', $import->status);
        $this->assertSame(1, $import->product_count);
        $this->assertSame('Casco MTB Pro', $import->products[0]['name']);
    }
}

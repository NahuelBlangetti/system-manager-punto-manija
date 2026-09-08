<?php

namespace Tests\Feature;

use App\Filament\Pages\ValidarImport;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ValidarImportDuplicateSkuTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reproduce un caso real de producción: el proveedor mandó dos productos
     * distintos con el mismo código interno (sku) en el mismo archivo. Antes,
     * el insert masivo de creación reventaba entero por la unique constraint
     * de sku y no se guardaba NADA del lote, ni los productos sin problemas.
     * Ahora el repetido se descarta y se avisa, pero el resto se guarda.
     */
    public function test_duplicate_sku_within_the_file_does_not_abort_the_whole_batch(): void
    {
        $user = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Asientos', 'slug' => 'asientos']);

        $import = ProductImport::create([
            'user_id' => $user->id,
            'filename' => 'lista.xlsx',
            'file_path' => 'imports/lista.xlsx',
            'status' => 'done',
            'products' => [
                [
                    'selected' => true,
                    'internal_duplicate' => false,
                    'action' => 'create',
                    'existing_product_id' => null,
                    'name' => 'Asiento con elastomero',
                    'sku' => '93537',
                    'barcode' => '',
                    'unit' => 'unidad',
                    'cost_price' => 9869,
                    'sale_price' => 19738,
                    'stock' => 1,
                    'category_id' => $category->id,
                ],
                [
                    'selected' => true,
                    'internal_duplicate' => false,
                    'action' => 'create',
                    'existing_product_id' => null,
                    'name' => 'Cubierta rodado 26',
                    'sku' => '93537',
                    'barcode' => '',
                    'unit' => 'unidad',
                    'cost_price' => 3870,
                    'sale_price' => 7740,
                    'stock' => 42,
                    'category_id' => $category->id,
                ],
            ],
        ]);

        Livewire::actingAs($user)
            ->withQueryParams(['id' => $import->id])
            ->test(ValidarImport::class)
            ->call('createProducts');

        $this->assertSame(1, Product::count());
        $this->assertSame('93537', Product::first()->sku);
        $this->assertSame('validated', $import->fresh()->status);
    }
}

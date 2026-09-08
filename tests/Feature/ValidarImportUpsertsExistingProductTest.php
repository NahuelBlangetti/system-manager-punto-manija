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

class ValidarImportUpsertsExistingProductTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reproduce el caso real de la recarga mensual de stock: el producto con
     * sku "93537" ya existía en el catálogo de una importación anterior, pero
     * el nombre cambió levemente en el nuevo archivo, así que al revisar no
     * quedó vinculado a "existing_product_id" (el matching por nombre no dio).
     * Antes esto intentaba un INSERT y explotaba contra la unique constraint
     * de sku. Ahora createProducts() vuelve a chequear contra la base antes
     * de insertar y lo actualiza en vez de duplicarlo.
     */
    public function test_a_row_without_existing_product_id_updates_the_product_matched_by_sku(): void
    {
        $user = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Accesorios', 'slug' => 'accesorios']);

        $existing = Product::create([
            'name' => 'Cubre rayos colores varios',
            'sku' => '93537',
            'barcode' => null,
            'unit' => 'unidad',
            'cost_price' => 3000,
            'sale_price' => 6000,
            'stock' => 10,
            'category_id' => $category->id,
            'active' => true,
        ]);

        $import = ProductImport::create([
            'user_id' => $user->id,
            'filename' => 'lista.xlsx',
            'file_path' => 'imports/lista.xlsx',
            'status' => 'done',
            'products' => [
                [
                    'selected' => true,
                    'internal_duplicate' => false,
                    // El matching por nombre en buildProductRows() no encontró
                    // al producto existente porque el nombre cambió, así que
                    // llegó a revisión marcado como "nuevo" sin vincular.
                    'action' => 'create',
                    'existing_product_id' => null,
                    'name' => 'Cubre rayos colores varios (actualizado)',
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

        $existing->refresh();
        $this->assertSame('Cubre rayos colores varios (actualizado)', $existing->name);
        $this->assertSame(7740.0, (float) $existing->sale_price);
        $this->assertSame(42, $existing->stock);
        $this->assertSame('validated', $import->fresh()->status);
    }
}

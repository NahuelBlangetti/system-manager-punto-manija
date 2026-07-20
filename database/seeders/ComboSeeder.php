<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ComboItem;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Vincula los combos ya cargados en el catálogo real de Punto Manija (categoría
 * "COMBOS MANIJA") con sus productos componentes reales, para que la venta de un
 * combo descuente stock de los productos verdaderos en vez de no descontar nada.
 *
 * Específico del catálogo de Punto Manija (nombres reales de producto) — no es
 * reutilizable para otro cliente. Se corre una sola vez, a mano:
 *   php artisan db:seed --class=ComboSeeder
 *
 * Da de baja (soft delete + inactivo, no borrado permanente) los combos
 * "2 PROMO VODKA + 2 PROMO FERNET" y "4 PROMO VODKA + PROMO FERNET": el texto
 * original dice "vodkas saborizados a elección", es decir, el cliente final
 * elige la marca en el momento — no hay una receta fija que los represente
 * correctamente hoy. Quedan con una nota en la descripción para que el admin
 * los pueda recrear a mano como combos de marca fija (igual que se hizo con
 * "PROMO BRANCA + PROMO VODKA").
 */
class ComboSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Corrección de datos: este combo quedó mal categorizado en "VODKAS".
            $this->fixCategory('2 PROMO ABSOLUT', 'COMBOS MANIJA');

            // --- Combos con una única receta real (sin ambigüedad) ---
            $this->linkFixedCombo('SKYY + SMIRNOFF + 4 SPEED XL', [
                'SMIRNOFF SABORIZADO' => 1,
                'SKYY' => 1,
                'SPEED XL' => 4,
            ]);

            $this->linkFixedCombo('ABSOLUT + SKYY + 4 SPEED XL', [
                'ABSOLUT' => 1,
                'SKYY' => 1,
                'SPEED XL' => 4,
            ]);

            $this->linkFixedCombo('3 PROMO SKYY', [
                'SKYY' => 3,
                'SPEED XL' => 6,
            ]);

            $this->linkFixedCombo('2 PROMO ABSOLUT', [
                'ABSOLUT' => 2,
                'SPEED XL' => 4,
            ]);

            $this->linkFixedCombo('ABSOLUT + 2 SKYY + 9 SPEED XL', [
                'ABSOLUT' => 1,
                'SKYY' => 2,
                'SPEED XL' => 9,
            ]);

            // "2 PROMO SKYY + PROMO FERNET" = promo Skyy + promo Fernet, sin elección de marca.
            $this->linkFixedCombo('2 PROMO SKYY + PROMO FERNET', [
                'SKYY' => 2,
                'SPEED XL' => 4,
                'BRANCA 750' => 1,
                'COCA COLA 2,5 DESC' => 1, // Supuesto: versión descartable. Ajustar si era retornable.
                'HIELO 1 KG' => 1,
            ]);

            // "JAGER + ABSOLUT" = promo Jager + promo Absolut, sin elección de marca.
            $this->linkFixedCombo('JAGER + ABSOLUT', [
                'JAGERMEISTER 700' => 1,
                'PACK RED BULL x 4' => 1,
                'ABSOLUT' => 1,
                'SPEED XL' => 2,
            ]);

            // --- Combo con elección real de marca (Skyy o Smirnoff): se parte en 2 productos ---
            $this->splitBrandChoiceCombo(
                originalName: 'PROMO BRANCA + PROMO VODKA',
                baseComponents: [
                    'BRANCA 750' => 1,
                    'COCA COLA 2,5 DESC' => 1, // Supuesto: versión descartable. Ajustar si era retornable.
                    'HIELO 1 KG' => 1, // Supuesto: el texto decía "1,5KG", no existe ese producto exacto.
                    'SPEED XL' => 2,
                ],
                variants: [
                    'Skyy' => ['SKYY' => 1],
                    'Smirnoff' => ['SMIRNOFF SABORIZADO' => 1],
                ],
            );

            // --- Combos "a elección" sin marca fija: no se pueden representar con
            // una receta única. Se dan de baja (recuperables) en vez de dejarlos
            // colgados en el catálogo sin ningún vínculo a stock real. ---
            $this->retireOpenChoiceCombo(
                '2 PROMO VODKA + 2 PROMO FERNET',
                'Ofrecía "vodkas saborizados" sin marca fija (el cliente elegía en el momento). '.
                'Recrear como combo(s) de marca fija — ej. uno por Skyy, otro por Absolut, otro por Smirnoff — '.
                'usando el toggle "Es un combo" en el producto.'
            );

            $this->retireOpenChoiceCombo(
                '4 PROMO VODKA + PROMO FERNET',
                'Ofrecía "vodkas saborizados a elección" sin marca fija (el cliente elegía en el momento). '.
                'Recrear como combo(s) de marca fija — ej. uno por Skyy, otro por Absolut, otro por Smirnoff — '.
                'usando el toggle "Es un combo" en el producto.'
            );

            // --- La categoría "COMBOS MANIJA" ya no hace falta: el filtro "Combo"
            // del panel los encuentra igual, y así cada uno vuelve a aparecer en la
            // tienda pública bajo su categoría real al navegar. Se recategoriza según
            // el producto que domina el combo (por precio/protagonismo) y se borra la
            // categoría; los productos quedan sin categoría automáticamente si alguno
            // se escapa de este mapeo. ---
            $this->fixCategory('SKYY + SMIRNOFF + 4 SPEED XL', 'VODKAS');
            $this->fixCategory('ABSOLUT + SKYY + 4 SPEED XL', 'VODKAS');
            $this->fixCategory('3 PROMO SKYY', 'VODKAS');
            $this->fixCategory('2 PROMO ABSOLUT', 'VODKAS');
            $this->fixCategory('ABSOLUT + 2 SKYY + 9 SPEED XL', 'VODKAS');
            $this->fixCategory('2 PROMO SKYY + PROMO FERNET', 'VODKAS'); // Skyy x2 + Speed XL pesa más en $ que la parte Fernet.
            $this->fixCategory('JAGER + ABSOLUT', 'LICORES'); // Jagermeister + Red Bull pesa más en $ que la parte Absolut.
            $this->fixCategory('PROMO BRANCA + PROMO VODKA (Skyy)', 'FERNET');
            $this->fixCategory('PROMO BRANCA + PROMO VODKA (Smirnoff)', 'FERNET');
            $this->fixCategoryIfExists('Promo Fernet + 2 Coca Descartables + Hielo', 'FERNET');

            $this->removeComboCategory();
        });
    }

    private function fixCategory(string $productName, string $categoryName): void
    {
        $product = Product::where('name', $productName)->firstOrFail();
        $category = Category::where('name', $categoryName)->firstOrFail();

        $product->update(['category_id' => $category->id]);
    }

    /**
     * Igual que fixCategory(), pero no falla si el producto no existe. Se usa para
     * productos cargados a mano por el admin (no garantizados por este seeder).
     */
    private function fixCategoryIfExists(string $productName, string $categoryName): void
    {
        $product = Product::where('name', $productName)->first();

        if (! $product) {
            return;
        }

        $category = Category::where('name', $categoryName)->firstOrFail();

        $product->update(['category_id' => $category->id]);
    }

    private function removeComboCategory(): void
    {
        Category::where('name', 'COMBOS MANIJA')->first()?->delete();
    }

    /**
     * @param  array<string, int>  $components  nombre de producto real => cantidad
     */
    private function linkFixedCombo(string $comboName, array $components): void
    {
        $combo = Product::where('name', $comboName)->firstOrFail();

        $combo->update(['is_combo' => true]);
        $combo->comboItems()->delete();

        foreach ($components as $componentName => $quantity) {
            ComboItem::create([
                'product_id' => $combo->id,
                'component_product_id' => $this->findComponent($componentName)->id,
                'quantity' => $quantity,
            ]);
        }
    }

    /**
     * @param  array<string, int>  $baseComponents  comunes a todas las variantes
     * @param  array<string, array<string, int>>  $variants  etiqueta => componentes extra de esa variante
     */
    private function splitBrandChoiceCombo(string $originalName, array $baseComponents, array $variants): void
    {
        // Si ya se corrió antes, el producto original fue renombrado a la primera
        // variante y ya no existe con este nombre: no hay nada más que hacer.
        $original = Product::where('name', $originalName)->first();

        if (! $original) {
            return;
        }

        $isFirstVariant = true;

        foreach ($variants as $variantLabel => $extraComponents) {
            $comboProduct = $isFirstVariant
                ? $original
                : $original->replicate(['sku', 'barcode']);

            $comboProduct->name = "{$originalName} ({$variantLabel})";
            $comboProduct->is_combo = true;
            $comboProduct->save();

            $comboProduct->comboItems()->delete();

            foreach (array_merge($baseComponents, $extraComponents) as $componentName => $quantity) {
                ComboItem::create([
                    'product_id' => $comboProduct->id,
                    'component_product_id' => $this->findComponent($componentName)->id,
                    'quantity' => $quantity,
                ]);
            }

            $isFirstVariant = false;
        }
    }

    private function findComponent(string $name): Product
    {
        return Product::where('name', $name)->where('is_combo', false)->firstOrFail();
    }

    private function retireOpenChoiceCombo(string $comboName, string $reason): void
    {
        // Si ya se corrió antes, el producto ya está dado de baja (soft deleted)
        // y no aparece con el scope por defecto: no hay nada más que hacer.
        $combo = Product::where('name', $comboName)->first();

        if (! $combo) {
            return;
        }

        $fecha = now()->format('d/m/Y');
        $nota = "[COMBO DADO DE BAJA {$fecha}] {$reason}";

        $combo->update([
            'active' => false,
            'description' => trim(($combo->description ? $combo->description."\n\n" : '')."{$nota}"),
        ]);

        $combo->delete();
    }
}

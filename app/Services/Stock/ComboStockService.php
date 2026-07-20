<?php

namespace App\Services\Stock;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;

/**
 * Centraliza el descuento/reposición de stock para que las ventas de combos
 * impacten el stock de los productos que los componen en vez del propio.
 * Usado desde CrearVenta (venta), EditSale y SalesTable (reversión de stock).
 */
class ComboStockService
{
    public static function availableStock(Product $product): int
    {
        if (! $product->is_combo) {
            return (int) $product->stock;
        }

        $comboItems = $product->comboItems()->with('component')->get();

        if ($comboItems->isEmpty()) {
            return 0;
        }

        return (int) $comboItems->min(
            fn ($comboItem) => $comboItem->component
                ? intdiv((int) $comboItem->component->stock, max(1, $comboItem->quantity))
                : 0
        );
    }

    /**
     * @throws \RuntimeException si no hay stock suficiente de algún componente.
     */
    public static function deduct(Product $product, int $quantity, string $notes, Model $reference, int $userId): void
    {
        if (! $product->is_combo) {
            self::adjust($product, -$quantity, $notes, $reference, $userId);

            return;
        }

        foreach ($product->comboItems as $comboItem) {
            $component = Product::lockForUpdate()->find($comboItem->component_product_id);
            $needed = $comboItem->quantity * $quantity;

            if (! $component || $component->stock < $needed) {
                $available = $component ? $component->stock : 0;

                throw new \RuntimeException(
                    "Stock insuficiente de \"{$component?->name}\" para armar el combo \"{$product->name}\". ".
                    "Disponible: {$available}, necesario: {$needed}."
                );
            }

            self::adjust($component, -$needed, "{$notes} (componente de combo \"{$product->name}\")", $reference, $userId);
        }
    }

    public static function restore(Product $product, int $quantity, string $notes, Model $reference, int $userId): void
    {
        if (! $product->is_combo) {
            self::adjust($product, $quantity, $notes, $reference, $userId);

            return;
        }

        foreach ($product->comboItems as $comboItem) {
            $component = Product::lockForUpdate()->find($comboItem->component_product_id);

            if (! $component) {
                continue;
            }

            $amount = $comboItem->quantity * $quantity;

            self::adjust($component, $amount, "{$notes} (componente de combo \"{$product->name}\")", $reference, $userId);
        }
    }

    private static function adjust(Product $product, int $delta, string $notes, Model $reference, int $userId): void
    {
        $before = (int) $product->stock;
        $after = $before + $delta;

        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $userId,
            'type' => $delta >= 0 ? 'in' : 'out',
            'quantity' => abs($delta),
            'stock_before' => $before,
            'stock_after' => $after,
            'notes' => $notes,
            'reference_type' => $reference::class,
            'reference_id' => $reference->getKey(),
        ]);

        $product->update(['stock' => $after]);
    }
}

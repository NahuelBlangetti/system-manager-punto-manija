<?php

namespace App\Services\Sales;

use App\Models\CashRegister;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Stock\ComboStockService;
use Illuminate\Support\Facades\DB;

/**
 * Registra una venta a partir de un carrito ya calculado (totales, descuentos
 * y promos resueltos en el POS). Usado por CrearVenta y por el cobro con
 * posnet Mercado Pago Point, que crea la venta recién cuando el pago se aprueba.
 *
 * Payload esperado:
 * [
 *   'payment_method' => 'cash|transfer|card|point',
 *   'subtotal' => float, 'discount' => float, 'promo_discount' => float,
 *   'total' => float, 'notes' => ?string,
 *   'items' => [['product_id', 'name', 'unit', 'unit_price', 'quantity', 'subtotal'], ...],
 * ]
 */
class SaleRegistrar
{
    /**
     * @param  bool  $alreadyPaid  el cobro ya se hizo (posnet): no se rechaza por
     *                             falta de stock, se registra y el stock puede quedar negativo.
     *
     * @throws \RuntimeException si falta stock y la venta no está cobrada.
     */
    public function register(array $payload, CashRegister $cashRegister, int $userId, bool $alreadyPaid = false): Sale
    {
        return DB::transaction(function () use ($payload, $cashRegister, $userId, $alreadyPaid) {
            $products = [];

            foreach ($payload['items'] as $item) {
                $product = Product::withTrashed()->lockForUpdate()->find($item['product_id']);

                if (! $alreadyPaid) {
                    $available = $product ? ComboStockService::availableStock($product) : 0;

                    if (! $product || $product->trashed() || $available < $item['quantity']) {
                        throw new \RuntimeException(
                            "Stock insuficiente para \"{$item['name']}\". ".
                            "Disponible: {$available} {$item['unit']}. ".
                            "En carrito: {$item['quantity']}."
                        );
                    }
                }

                $products[$item['product_id']] = $product;
            }

            $sale = Sale::create([
                'user_id' => $userId,
                'cash_register_id' => $cashRegister->id,
                'payment_method' => $payload['payment_method'],
                'subtotal' => $payload['subtotal'],
                'discount' => $payload['discount'],
                'promo_discount' => $payload['promo_discount'],
                'total' => $payload['total'],
                'notes' => $payload['notes'] ?? null,
                'status' => 'completed',
            ]);

            foreach ($payload['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);

                $product = $products[$item['product_id']];

                if ($product) {
                    ComboStockService::deduct($product, $item['quantity'], "Venta {$sale->sale_number}", $sale, $userId, $alreadyPaid);
                }
            }

            return $sale;
        });
    }
}

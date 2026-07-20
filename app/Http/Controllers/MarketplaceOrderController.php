<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\WebOrder;
use App\Services\Shipping\ShippingPriceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketplaceOrderController extends Controller
{
    public function store(Request $request, ShippingPriceCalculator $calculator): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'delivery_type' => ['required', 'in:pickup,delivery'],
            'address' => ['required_if:delivery_type,delivery', 'nullable', 'string', 'max:255'],
            'lat' => ['required_if:delivery_type,delivery', 'nullable', 'numeric', 'between:-90,90'],
            'lng' => ['required_if:delivery_type,delivery', 'nullable', 'numeric', 'between:-180,180'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $products = Product::query()
            ->whereIn('id', collect($data['items'])->pluck('id'))
            ->where('active', true)
            ->get()
            ->keyBy('id');

        foreach ($data['items'] as $item) {
            $product = $products->get($item['id']);

            if (! $product) {
                return response()->json(['message' => 'Uno de los productos ya no está disponible.'], 422);
            }

            if ($product->stock < $item['qty']) {
                return response()->json([
                    'message' => "Sin stock suficiente de \"{$product->name}\" (quedan {$product->stock}).",
                ], 422);
            }
        }

        $webOrder = DB::transaction(function () use ($data, $products, $calculator) {
            $subtotal = 0;
            $itemRows = [];

            foreach ($data['items'] as $item) {
                $product = $products->get($item['id']);
                $lineSubtotal = (float) $product->sale_price * $item['qty'];
                $subtotal += $lineSubtotal;

                $itemRows[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->sale_price,
                    'quantity' => $item['qty'],
                    'subtotal' => $lineSubtotal,
                ];
            }

            $shippingCost = null;

            if ($data['delivery_type'] === 'delivery') {
                $quote = $calculator->quote($data['distance_km'] ?? null);
                $shippingCost = $quote['cost'];
            }

            $webOrder = WebOrder::create([
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'delivery_type' => $data['delivery_type'],
                'address' => $data['address'] ?? null,
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'distance_km' => $data['delivery_type'] === 'delivery' ? ($data['distance_km'] ?? null) : null,
                'shipping_cost' => $shippingCost,
                'subtotal' => $subtotal,
                'total' => $subtotal + ($shippingCost ?? 0),
                'status' => 'pending',
            ]);

            $webOrder->items()->createMany($itemRows);

            return $webOrder;
        });

        return response()->json([
            'order_id' => $webOrder->id,
            'whatsapp_url' => $this->buildWhatsappUrl($webOrder),
        ]);
    }

    private function buildWhatsappUrl(WebOrder $webOrder): string
    {
        $waNumber = config('store.whatsapp');
        $storeName = config('store.name', 'la tienda');

        $lines = ["Hola *{$storeName}*! 👋 Me gustaría hacer el siguiente pedido:", ''];

        foreach ($webOrder->items as $item) {
            $lines[] = "• {$item->product_name} (x{$item->quantity}) — \$".number_format((float) $item->subtotal, 0, ',', '.');
        }

        $lines[] = '';

        if ($webOrder->delivery_type === 'delivery') {
            $lines[] = "📍 *Entrega:* {$webOrder->address}";

            if ($webOrder->lat && $webOrder->lng) {
                $lines[] = "🗺️ https://www.google.com/maps?q={$webOrder->lat},{$webOrder->lng}";
            }

            $lines[] = $webOrder->shipping_cost !== null
                ? '🛵 *Envío:* $'.number_format((float) $webOrder->shipping_cost, 0, ',', '.')
                : '🛵 *Envío:* a coordinar';
        } else {
            $lines[] = '🏠 *Retiro en el local*';
        }

        $lines[] = '';
        $lines[] = '💰 *Total estimado: $'.number_format((float) $webOrder->total, 0, ',', '.').'*';
        $lines[] = '';
        $lines[] = "Nombre: {$webOrder->customer_name}";
        $lines[] = '¿Podría confirmar disponibilidad? ¡Muchas gracias!';

        return 'https://wa.me/'.$waNumber.'?text='.rawurlencode(implode("\n", $lines));
    }
}

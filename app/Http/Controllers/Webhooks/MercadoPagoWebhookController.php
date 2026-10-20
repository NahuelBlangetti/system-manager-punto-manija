<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\MercadoPago\PointPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recibe las notificaciones "Order (Mercado Pago)" de los cobros Point.
 * Es un respaldo del polling del POS: si el navegador se cerró mientras el
 * cliente pagaba, la venta igual queda registrada.
 */
class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, PointPaymentService $payments): JsonResponse
    {
        // PHP convierte el query param "data.id" en "data_id".
        $orderId = (string) ($request->query('data_id') ?? $request->input('data.id', ''));

        if (! $this->hasValidSignature($request, $orderId)) {
            Log::warning('Webhook Mercado Pago con firma inválida', ['data_id' => $orderId]);

            return response()->json(['message' => 'invalid signature'], 401);
        }

        $type = (string) ($request->input('type') ?? $request->query('type', ''));

        if ($orderId === '' || ! in_array($type, ['order', 'orders'], true)) {
            return response()->json(['message' => 'ignored']);
        }

        try {
            $payments->handleOrderNotification($orderId);
        } catch (\Throwable $e) {
            report($e);

            // 500 → Mercado Pago reintenta la notificación más tarde.
            return response()->json(['message' => 'error'], 500);
        }

        return response()->json(['message' => 'ok']);
    }

    /**
     * @see https://www.mercadopago.com.ar/developers/es/docs/your-integrations/notifications/webhooks
     */
    private function hasValidSignature(Request $request, string $dataId): bool
    {
        $secret = (string) config('services.mercadopago.webhook_secret');

        if ($secret === '') {
            return true;
        }

        $parts = collect(explode(',', (string) $request->header('x-signature')))
            ->mapWithKeys(function (string $part) {
                [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');

                return [$key => $value];
            });

        $ts = $parts->get('ts');
        $hash = $parts->get('v1');

        if (! $ts || ! $hash) {
            return false;
        }

        $manifest = '';

        if ($dataId !== '') {
            $manifest .= 'id:'.strtolower($dataId).';';
        }

        if ($requestId = $request->header('x-request-id')) {
            $manifest .= "request-id:{$requestId};";
        }

        $manifest .= "ts:{$ts};";

        return hash_equals(hash_hmac('sha256', $manifest, $secret), $hash);
    }
}

<?php

namespace App\Services\MercadoPago;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Cliente mínimo de la Orders API de Mercado Pago para posnets Point
 * (N950 / Point Smart) en modo PDV.
 *
 * @see https://www.mercadopago.com.ar/developers/es/docs/mp-point/overview
 */
class PointClient
{
    public const TERMINAL_SETTING_KEY = 'mercadopago.point_terminal_id';

    public function isConfigured(): bool
    {
        return filled(config('services.mercadopago.access_token'));
    }

    public function terminalId(): ?string
    {
        $terminal = Setting::get(self::TERMINAL_SETTING_KEY) ?: config('services.mercadopago.point_terminal_id');

        return filled($terminal) ? (string) $terminal : null;
    }

    /**
     * @return array<int, array{id: string, pos_id: mixed, store_id: mixed, external_pos_id: mixed, operating_mode: string}>
     */
    public function listTerminals(): array
    {
        $response = $this->send(fn () => $this->request()->get('/terminals/v1/list', ['limit' => 50]));

        return $this->json($response, 'listar los posnets')['data']['terminals'] ?? [];
    }

    public function setOperatingMode(string $terminalId, string $mode = 'PDV'): void
    {
        $response = $this->send(fn () => $this->request()->patch('/terminals/v1/setup', [
            'terminals' => [
                ['id' => $terminalId, 'operating_mode' => $mode],
            ],
        ]));

        $this->json($response, 'cambiar el modo del posnet');
    }

    /**
     * Crea la orden y la envía al posnet. El posnet la muestra al instante.
     *
     * @return array<string, mixed>
     */
    public function createOrder(string $terminalId, float $amount, string $externalReference, string $description): array
    {
        $response = $this->send(fn () => $this->request()
            ->withHeaders(['X-Idempotency-Key' => $externalReference])
            ->post('/v1/orders', [
                'type' => 'point',
                'external_reference' => $externalReference,
                'expiration_time' => config('services.mercadopago.point_expiration', 'PT5M'),
                'description' => Str::limit($description, 150, ''),
                'transactions' => [
                    'payments' => [
                        ['amount' => number_format($amount, 2, '.', '')],
                    ],
                ],
                'config' => [
                    'point' => [
                        'terminal_id' => $terminalId,
                        'print_on_terminal' => config('services.mercadopago.point_print_on_terminal', 'no_ticket'),
                    ],
                ],
            ]), timeoutHint: true);

        return $this->json($response, 'enviar el cobro al posnet');
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrder(string $orderId): array
    {
        return $this->json($this->send(fn () => $this->request()->get("/v1/orders/{$orderId}")), 'consultar el cobro');
    }

    /**
     * Solo funciona mientras la orden está en "created" (aún no la tomó el
     * posnet). En "at_terminal" hay que cancelarla desde el propio posnet.
     *
     * @return array<string, mixed>
     */
    public function cancelOrder(string $orderId): array
    {
        $response = $this->send(fn () => $this->request()
            ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
            ->post("/v1/orders/{$orderId}/cancel"));

        return $this->json($response, 'cancelar el cobro');
    }

    /**
     * @param  callable(): Response  $call
     */
    private function send(callable $call, bool $timeoutHint = false): Response
    {
        try {
            return $call();
        } catch (ConnectionException $e) {
            throw new MercadoPagoException(
                'Sin conexión con Mercado Pago. Revisá internet e intentá de nuevo.'
                .($timeoutHint ? ' Si el posnet muestra el cobro, cancelalo desde el posnet antes de reintentar.' : ''),
                previous: $e,
            );
        }
    }

    private function request(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new MercadoPagoException('Falta configurar MERCADOPAGO_ACCESS_TOKEN en el .env.');
        }

        return Http::baseUrl(rtrim((string) config('services.mercadopago.base_url'), '/'))
            ->withToken((string) config('services.mercadopago.access_token'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15);
    }

    /**
     * @return array<string, mixed>
     */
    private function json(Response $response, string $action): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $body = $response->json() ?? [];
        $detail = $body['errors'][0]['message'] ?? $body['message'] ?? $response->body();

        throw new MercadoPagoException(
            "Mercado Pago rechazó {$action} (HTTP {$response->status()}): {$detail}"
        );
    }
}

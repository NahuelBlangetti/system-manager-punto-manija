<?php

namespace App\Services\MercadoPago;

use App\Models\CashRegister;
use App\Models\PointPayment;
use App\Models\Sale;
use App\Services\Sales\SaleRegistrar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Flujo de cobro con posnet Mercado Pago Point:
 *
 * 1. start(): congela el carrito en un PointPayment y crea la orden en MP.
 *    El posnet muestra el monto al instante.
 * 2. sync(): consulta la orden (lo llama el POS por polling y el webhook).
 *    Cuando MP la marca "processed" se crea la Sale con payment_method=point.
 *    Es idempotente: la venta se crea una sola vez aunque lleguen ambos avisos.
 * 3. cancel(): cancela la orden mientras el posnet todavía no la tomó.
 */
class PointPaymentService
{
    public function __construct(
        private readonly PointClient $client,
        private readonly SaleRegistrar $registrar,
    ) {}

    /**
     * @throws MercadoPagoException
     */
    public function start(array $salePayload, CashRegister $cashRegister, int $userId): PointPayment
    {
        $terminalId = $this->client->terminalId();

        if (! $terminalId) {
            throw new MercadoPagoException('No hay un posnet configurado. Elegilo en Administración → Posnet Mercado Pago.');
        }

        $salePayload['payment_method'] = 'point';

        $payment = PointPayment::create([
            'user_id' => $userId,
            'cash_register_id' => $cashRegister->id,
            'external_reference' => (string) Str::uuid(),
            'terminal_id' => $terminalId,
            'amount' => $salePayload['total'],
            'status' => PointPayment::STATUS_PENDING,
            'sale_payload' => $salePayload,
        ]);

        try {
            $order = $this->client->createOrder(
                $terminalId,
                (float) $salePayload['total'],
                $payment->external_reference,
                $this->describe($salePayload),
            );
        } catch (MercadoPagoException $e) {
            $payment->update([
                'status' => PointPayment::STATUS_ERROR,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $payment->update([
            'mp_order_id' => $order['id'] ?? null,
            'status' => $order['status'] ?? 'created',
            'last_response' => $order,
        ]);

        return $payment;
    }

    /**
     * Consulta el estado en Mercado Pago y, si se aprobó, registra la venta.
     */
    public function sync(PointPayment $payment): PointPayment
    {
        if (! $payment->mp_order_id || ($payment->isFinished() && ($payment->sale_id || ! $payment->isPaid()))) {
            return $payment;
        }

        return $this->applyOrder($payment, $this->client->getOrder($payment->mp_order_id));
    }

    /**
     * @throws MercadoPagoException si el posnet ya tomó el cobro.
     */
    public function cancel(PointPayment $payment): PointPayment
    {
        $payment = $this->sync($payment);

        if ($payment->isFinished()) {
            return $payment;
        }

        if ($payment->status !== 'created') {
            throw new MercadoPagoException('El posnet ya tomó el cobro: cancelalo desde el posnet (botón rojo / "Cancelar").');
        }

        $order = $this->client->cancelOrder($payment->mp_order_id);

        return $this->applyOrder($payment, $order + ['status' => 'canceled']);
    }

    public function handleOrderNotification(string $orderId): ?PointPayment
    {
        $payment = PointPayment::where('mp_order_id', $orderId)->first();

        return $payment ? $this->sync($payment) : null;
    }

    /**
     * @param  array<string, mixed>  $order
     */
    public function applyOrder(PointPayment $payment, array $order): PointPayment
    {
        return DB::transaction(function () use ($payment, $order) {
            /** @var PointPayment $locked */
            $locked = PointPayment::lockForUpdate()->findOrFail($payment->id);
            $mpPayment = data_get($order, 'transactions.payments.0', []);

            $locked->fill([
                'status' => $order['status'] ?? $locked->status,
                'status_detail' => $order['status_detail'] ?? data_get($mpPayment, 'status_detail', $locked->status_detail),
                'mp_payment_id' => data_get($mpPayment, 'id', $locked->mp_payment_id),
                'payment_type' => data_get($mpPayment, 'payment_method.type', $locked->payment_type),
                'payment_method_id' => data_get($mpPayment, 'payment_method.id', $locked->payment_method_id),
                'installments' => data_get($mpPayment, 'payment_method.installments', $locked->installments),
                'card_last_digits' => data_get($mpPayment, 'payment_method.card.last_digits')
                    ?? data_get($mpPayment, 'card.last_digits')
                    ?? $locked->card_last_digits,
                'last_response' => $order,
            ]);

            if ($locked->isPaid() && ! $locked->sale_id) {
                $locked->paid_at ??= now();
                $locked->sale_id = $this->registerSale($locked)->id;
            }

            $locked->save();

            return $locked;
        });
    }

    private function registerSale(PointPayment $payment): Sale
    {
        // Si la caja con la que se inició el cobro se cerró mientras tanto, la
        // venta va a la caja abierta actual para no alterar un arqueo cerrado.
        $cashRegister = $payment->cashRegister?->status === 'open'
            ? $payment->cashRegister
            : (CashRegister::where('status', 'open')->first() ?? $payment->cashRegister);

        if (! $cashRegister) {
            throw new \RuntimeException("Cobro Point {$payment->mp_order_id} aprobado sin caja disponible para registrar la venta.");
        }

        $payload = $payment->sale_payload;
        $payload['payment_method'] = 'point';
        $payload['notes'] = trim(($payload['notes'] ?? '')."\n".$this->paymentNote($payment));

        $sale = $this->registrar->register($payload, $cashRegister, $payment->user_id, alreadyPaid: true);

        Log::info('Venta registrada por cobro Point', [
            'sale' => $sale->sale_number,
            'mp_order_id' => $payment->mp_order_id,
            'amount' => $payment->amount,
        ]);

        return $sale;
    }

    private function paymentNote(PointPayment $payment): string
    {
        return collect([
            'Posnet MP',
            $payment->paymentTypeLabel(),
            $payment->card_last_digits ? "****{$payment->card_last_digits}" : null,
            $payment->installments > 1 ? "{$payment->installments} cuotas" : null,
            "Orden {$payment->mp_order_id}",
        ])->filter()->implode(' · ');
    }

    private function describe(array $salePayload): string
    {
        $count = collect($salePayload['items'] ?? [])->sum('quantity');

        return "Punto Manija · {$count} ".($count === 1 ? 'producto' : 'productos');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cobro enviado al posnet Mercado Pago Point (Orders API).
 * Guarda el carrito congelado en `sale_payload` y crea la Sale al confirmarse.
 */
class PointPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_ERROR = 'error';

    /** Estados de Mercado Pago que ya no van a cambiar a "processed". */
    public const FAILED_STATUSES = ['canceled', 'expired', 'failed', 'refunded', self::STATUS_ERROR];

    protected $fillable = [
        'user_id',
        'cash_register_id',
        'sale_id',
        'external_reference',
        'terminal_id',
        'mp_order_id',
        'mp_payment_id',
        'amount',
        'status',
        'status_detail',
        'payment_type',
        'payment_method_id',
        'installments',
        'card_last_digits',
        'sale_payload',
        'last_response',
        'error_message',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sale_payload' => 'array',
        'last_response' => 'array',
        'installments' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    public function isFinished(): bool
    {
        return $this->isPaid() || in_array($this->status, self::FAILED_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING, 'created' => 'Enviado al posnet',
            'at_terminal' => 'El cliente está pagando en el posnet',
            'action_required' => 'El posnet requiere una acción',
            self::STATUS_PROCESSED => 'Pago aprobado',
            'canceled' => 'Cobro cancelado',
            'expired' => 'Cobro vencido sin pagar',
            'failed' => 'Pago rechazado',
            'refunded' => 'Pago devuelto',
            self::STATUS_ERROR => 'Error de comunicación con Mercado Pago',
            default => $this->status,
        };
    }

    public function paymentTypeLabel(): ?string
    {
        return match ($this->payment_type) {
            'credit_card' => 'Crédito',
            'debit_card' => 'Débito',
            'prepaid_card' => 'Prepaga',
            'account_money', 'qr' => 'Dinero en cuenta / QR',
            null => null,
            default => $this->payment_type,
        };
    }
}

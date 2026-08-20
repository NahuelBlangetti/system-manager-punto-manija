<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    protected $fillable = [
        'user_id',
        'opening_amount',
        'closing_amount',
        'expected_amount',
        'difference',
        'notes',
        'opened_at',
        'closed_at',
        'status',
    ];

    protected $casts = [
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CashRegisterEntry::class);
    }

    public function cashSalesTotal(): float
    {
        return $this->salesTotalByPaymentMethod('cash');
    }

    public function transferSalesTotal(): float
    {
        return $this->salesTotalByPaymentMethod('transfer');
    }

    public function cardSalesTotal(): float
    {
        return $this->salesTotalByPaymentMethod('card');
    }

    public function salesTotalByPaymentMethod(string $paymentMethod): float
    {
        return (float) $this->sales()
            ->where('status', 'completed')
            ->where('payment_method', $paymentMethod)
            ->sum('total');
    }

    public function incomeEntriesTotal(): float
    {
        return (float) $this->entries()->where('type', 'income')->sum('amount');
    }

    public function expenseEntriesTotal(): float
    {
        return (float) $this->entries()->where('type', 'expense')->sum('amount');
    }

    public function calculateExpectedAmount(): float
    {
        return (float) $this->opening_amount
            + $this->cashSalesTotal()
            + $this->incomeEntriesTotal()
            - $this->expenseEntriesTotal();
    }

    public function recalculate(): void
    {
        $this->expected_amount = $this->calculateExpectedAmount();

        if ($this->closing_amount !== null) {
            $this->difference = $this->closing_amount - $this->expected_amount;
        }

        $this->saveQuietly();
    }

    public function close(float $closingAmount, ?string $closingNotes = null): void
    {
        $notes = $this->notes;

        if (filled($closingNotes)) {
            $notes = filled($notes) ? $notes."\n\n".$closingNotes : $closingNotes;
        }

        $this->closing_amount = $closingAmount;
        $this->closed_at = now();
        $this->status = 'closed';
        $this->notes = $notes;

        $this->recalculate();
    }

    public static function lastClosed(): ?self
    {
        return static::query()
            ->where('status', 'closed')
            ->whereNotNull('closing_amount')
            ->latest('closed_at')
            ->first();
    }

    public static function suggestedOpeningAmount(): float
    {
        $lastClosed = static::lastClosed();

        return $lastClosed ? (float) $lastClosed->closing_amount : 0;
    }

    public static function formatMoney(float $amount): string
    {
        return '$ '.number_format($amount, 2, ',', '.');
    }
}

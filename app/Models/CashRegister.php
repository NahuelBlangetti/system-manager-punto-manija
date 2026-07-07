<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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
        'opening_amount'  => 'decimal:2',
        'closing_amount'  => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference'      => 'decimal:2',
        'opened_at'       => 'datetime',
        'closed_at'       => 'datetime',
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

    public function recalculate(): void
    {
        $cashSales = $this->sales()->where('payment_method', 'cash')->sum('total');
        $income    = $this->entries()->where('type', 'income')->sum('amount');
        $expenses  = $this->entries()->where('type', 'expense')->sum('amount');

        $this->expected_amount = $this->opening_amount + $cashSales + $income - $expenses;

        if ($this->closing_amount !== null) {
            $this->difference = $this->closing_amount - $this->expected_amount;
        }

        $this->saveQuietly();
    }
}

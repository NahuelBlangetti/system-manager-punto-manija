<?php

namespace App\Models;

use App\Observers\WebOrderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(WebOrderObserver::class)]
class WebOrder extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'delivery_type',
        'address',
        'lat',
        'lng',
        'distance_km',
        'shipping_cost',
        'subtotal',
        'total',
        'status',
        'notes',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'distance_km' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(WebOrderItem::class);
    }
}

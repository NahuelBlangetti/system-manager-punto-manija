<?php

namespace App\Models;

use App\Enums\ProductDiscountType;
use App\Filament\Support\ProductImagePath;
use App\Observers\ProductObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ProductObserver::class)]
class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'supplier_id',
        'name',
        'sku',
        'barcode',
        'imei',
        'unit',
        'description',
        'image',
        'cost_price',
        'sale_price',
        'discount_min_qty',
        'discount_type',
        'discount_value',
        'stock',
        'min_stock',
        'active',
        'is_combo',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'discount_type' => ProductDiscountType::class,
        'discount_value' => 'decimal:2',
        'active' => 'boolean',
        'is_combo' => 'boolean',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return ProductImagePath::publicUrl($this->image);
    }

    public function unitPriceForQuantity(int $quantity): float
    {
        $salePrice = (float) $this->sale_price;

        if (! $this->discount_min_qty || ! $this->discount_type || $quantity < $this->discount_min_qty) {
            return $salePrice;
        }

        return match ($this->discount_type) {
            ProductDiscountType::Percentage => max(0.0, round($salePrice * (1 - ((float) $this->discount_value / 100)), 2)),
            ProductDiscountType::Fixed => max(0.0, round($salePrice - (float) $this->discount_value, 2)),
        };
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function comboItems(): HasMany
    {
        return $this->hasMany(ComboItem::class, 'product_id');
    }
}

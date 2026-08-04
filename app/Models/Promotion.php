<?php

namespace App\Models;

use App\Enums\PromotionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'type',
        'percentage_value',
        'days_of_week',
        'start_time',
        'end_time',
        'active',
    ];

    protected $casts = [
        'type' => PromotionType::class,
        'percentage_value' => 'decimal:2',
        'days_of_week' => 'array',
        'active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function appliesToProduct(Product $product): bool
    {
        return $this->category_id === null || $this->category_id === $product->category_id;
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->active) {
            return false;
        }

        $now = now();

        if (! empty($this->days_of_week) && ! in_array($now->dayOfWeek, $this->days_of_week)) {
            return false;
        }

        if ($this->start_time && $this->end_time) {
            $currentTime = $now->format('H:i:s');
            $start = substr((string) $this->start_time, 0, 8);
            $end = substr((string) $this->end_time, 0, 8);

            $isWithinRange = $start <= $end
                ? ($currentTime >= $start && $currentTime <= $end)
                : ($currentTime >= $start || $currentTime <= $end);

            if (! $isWithinRange) {
                return false;
            }
        }

        return true;
    }
}

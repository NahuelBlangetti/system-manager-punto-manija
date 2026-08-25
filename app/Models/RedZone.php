<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RedZone extends Model
{
    protected $fillable = [
        'name',
        'polygon',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'polygon' => 'array',
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Ray casting: true si el punto cae dentro (o sobre el borde) del polígono.
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        $points = $this->polygon ?? [];
        $count = count($points);

        if ($count < 3) {
            return false;
        }

        $inside = false;

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $yi = (float) $points[$i]['lat'];
            $xi = (float) $points[$i]['lng'];
            $yj = (float) $points[$j]['lat'];
            $xj = (float) $points[$j]['lng'];

            $intersects = ($yi > $lat) !== ($yj > $lat)
                && $lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi;

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Primera zona roja activa que contiene el punto, o null si no hay ninguna.
     */
    public static function findBlocking(float $lat, float $lng): ?self
    {
        return static::query()
            ->active()
            ->get()
            ->first(fn (self $zone): bool => $zone->containsPoint($lat, $lng));
    }
}

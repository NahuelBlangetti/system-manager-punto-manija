<?php

namespace App\Services\Shipping;

use App\Models\Setting;

class ShippingSettings
{
    public const KEY = 'shipping';

    /**
     * @return array{base_price: float, price_per_km: float, max_distance_km: float, rounding_step: float}
     */
    public static function defaults(): array
    {
        return [
            'base_price' => (float) config('store.shipping.base_price'),
            'price_per_km' => (float) config('store.shipping.price_per_km'),
            'max_distance_km' => (float) config('store.shipping.max_distance_km'),
            'rounding_step' => (float) config('store.shipping.rounding_step'),
        ];
    }

    /**
     * Valores efectivos: lo guardado por el admin, con fallback a .env/config.
     *
     * @return array{base_price: float, price_per_km: float, max_distance_km: float, rounding_step: float}
     */
    public static function all(): array
    {
        $stored = Setting::get(self::KEY, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        $defaults = self::defaults();

        return [
            'base_price' => (float) ($stored['base_price'] ?? $defaults['base_price']),
            'price_per_km' => (float) ($stored['price_per_km'] ?? $defaults['price_per_km']),
            'max_distance_km' => (float) ($stored['max_distance_km'] ?? $defaults['max_distance_km']),
            'rounding_step' => (float) ($stored['rounding_step'] ?? $defaults['rounding_step']),
        ];
    }

    /**
     * @param  array{base_price?: mixed, price_per_km?: mixed, max_distance_km?: mixed, rounding_step?: mixed}  $values
     */
    public static function save(array $values): void
    {
        Setting::set(self::KEY, [
            'base_price' => (float) ($values['base_price'] ?? 0),
            'price_per_km' => (float) ($values['price_per_km'] ?? 0),
            'max_distance_km' => (float) ($values['max_distance_km'] ?? 0),
            'rounding_step' => (float) ($values['rounding_step'] ?? 1),
        ]);
    }
}

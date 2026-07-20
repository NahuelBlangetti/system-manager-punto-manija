<?php

namespace App\Services\Shipping;

class ShippingPriceCalculator
{
    /**
     * @param  array{base_price?: float, price_per_km?: float, max_distance_km?: float, rounding_step?: float}|null  $settings
     * @return array{cost: float|null, out_of_range: bool}
     */
    public function quote(?float $distanceKm, ?array $settings = null): array
    {
        $settings = array_merge(ShippingSettings::all(), $settings ?? []);
        $maxDistanceKm = (float) $settings['max_distance_km'];

        if ($distanceKm === null || $distanceKm > $maxDistanceKm) {
            return ['cost' => null, 'out_of_range' => true];
        }

        $basePrice = (float) $settings['base_price'];
        $pricePerKm = (float) $settings['price_per_km'];
        $roundingStep = (float) ($settings['rounding_step'] ?: 1);

        $rawCost = $basePrice + ($pricePerKm * $distanceKm);
        $cost = round($rawCost / $roundingStep) * $roundingStep;

        return ['cost' => $cost, 'out_of_range' => false];
    }
}

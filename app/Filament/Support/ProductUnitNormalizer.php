<?php

namespace App\Filament\Support;

class ProductUnitNormalizer
{
    public const WHITELIST = ['unidad', 'metro', 'm2', 'kg', 'g', 'litro', 'caja', 'rollo', 'par', 'docena'];

    private const SYNONYMS = [
        'un' => 'unidad',
        'unid' => 'unidad',
        'mts' => 'metro',
        'mt' => 'metro',
        'mts2' => 'm2',
        'kgs' => 'kg',
        'lt' => 'litro',
        'litros' => 'litro',
        'cjs' => 'caja',
        'cajas' => 'caja',
        'rollos' => 'rollo',
        'pares' => 'par',
        'docenas' => 'docena',
    ];

    public static function normalize(?string $raw): string
    {
        $value = strtolower(trim((string) $raw));

        if (in_array($value, self::WHITELIST, true)) {
            return $value;
        }

        return self::SYNONYMS[$value] ?? 'unidad';
    }
}

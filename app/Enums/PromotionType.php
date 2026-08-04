<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PromotionType: string implements HasLabel
{
    case Percentage = 'percentage';
    case TwoForOne = 'two_for_one';

    public function getLabel(): string
    {
        return match ($this) {
            self::Percentage => 'Descuento %',
            self::TwoForOne => '2x1',
        };
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case Empleado = 'empleado';
    case Delivery = 'delivery';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Empleado => 'Empleado',
            self::Delivery => 'Delivery',
        };
    }
}

<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Punto Manija';

    protected static ?string $title = 'Punto Manija';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nueva_venta')
                ->label('Nueva Venta')
                ->icon('heroicon-o-shopping-bag')
                ->url(CrearVenta::getUrl())
                ->color('primary')
                ->size('lg'),
        ];
    }
}

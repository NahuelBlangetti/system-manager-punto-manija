<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Pages\CrearVenta;
use App\Filament\Resources\Sales\SaleResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nueva_venta')
                ->label('Nueva venta')
                ->icon('heroicon-o-shopping-cart')
                ->url(CrearVenta::getUrl()),
        ];
    }
}

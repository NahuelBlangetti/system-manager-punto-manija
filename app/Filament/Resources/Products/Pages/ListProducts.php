<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Pages\CargarProductos;
use App\Filament\Resources\Products\ProductResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function getSubheading(): ?string
    {
        return 'Filtrá por proveedor o categoría, seleccioná los productos y exportá el PDF desde las acciones masivas.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cargarProductos')
                ->label('Cargar productos')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(function (): bool {
                    $user = Auth::user();

                    return $user instanceof User && $user->canManageProducts();
                })
                ->url(fn () => CargarProductos::getUrl()),
            CreateAction::make(),
        ];
    }
}

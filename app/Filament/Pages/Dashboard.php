<?php

namespace App\Filament\Pages;

use App\Filament\Resources\WebOrders\WebOrderResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Punto Manija';

    protected static ?string $title = 'Punto Manija';

    public static function canAccess(): bool
    {
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();

        return ! ($user instanceof User && $user->isDelivery());
    }

    public function mount(): void
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isDelivery()) {
            $this->redirect(WebOrderResource::getUrl());
        }
    }

    protected function getHeaderActions(): array
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isDelivery()) {
            return [];
        }

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

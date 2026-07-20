<?php

namespace App\Filament\Resources\WebOrders\Pages;

use App\Filament\Resources\WebOrders\WebOrderResource;
use App\Models\User;
use App\Models\WebOrder;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListWebOrders extends ListRecords
{
    protected static string $resource = WebOrderResource::class;

    public function getTitle(): string
    {
        return 'Pedidos Web';
    }

    public function getSubheading(): ?string
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isDelivery()) {
            return 'Tocá un pedido para ver detalle, llamar o abrir el mapa.';
        }

        return 'Gestión de pedidos del marketplace.';
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'activos';
    }

    public function getTabs(): array
    {
        $user = Auth::user();
        $isDelivery = $user instanceof User && $user->isDelivery();

        $tabs = [
            'activos' => Tab::make('Activos')
                ->badge(fn (): int => WebOrder::query()->whereIn('status', ['pending', 'confirmed'])->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['pending', 'confirmed'])),
            'confirmados' => Tab::make('A entregar')
                ->badge(fn (): int => WebOrder::query()->where('status', 'confirmed')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'confirmed')),
        ];

        if (! $isDelivery) {
            $tabs['pendientes'] = Tab::make('Pendientes')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'));
        }

        $tabs['entregados'] = Tab::make('Entregados')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'delivered'));

        $tabs['todos'] = Tab::make('Todos');

        return $tabs;
    }
}

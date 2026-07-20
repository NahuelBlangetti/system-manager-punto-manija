<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Filament\Resources\WebOrders\WebOrderResource;
use App\Models\User;
use App\Models\WebOrder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class WebOrderObserver
{
    public function created(WebOrder $webOrder): void
    {
        $body = $webOrder->delivery_type === 'delivery'
            ? "{$webOrder->customer_name} pidió envío a {$webOrder->address} — total \${$webOrder->total}."
            : "{$webOrder->customer_name} pidió retiro en el local — total \${$webOrder->total}.";

        $action = Action::make('ver')
            ->label('Ver pedido')
            ->url(WebOrderResource::getUrl('edit', ['record' => $webOrder]))
            ->button();

        User::query()
            ->whereIn('role', [
                UserRole::Admin->value,
                UserRole::Empleado->value,
                UserRole::Delivery->value,
            ])
            ->each(function (User $user) use ($body, $action) {
                Notification::make()
                    ->title('Nuevo pedido web')
                    ->body($body)
                    ->actions([$action])
                    ->success()
                    ->sendToDatabase($user);
            });
    }
}

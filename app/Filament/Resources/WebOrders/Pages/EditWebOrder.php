<?php

namespace App\Filament\Resources\WebOrders\Pages;

use App\Filament\Resources\WebOrders\WebOrderResource;
use App\Models\User;
use App\Models\WebOrder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditWebOrder extends EditRecord
{
    protected static string $resource = WebOrderResource::class;

    protected function getHeaderActions(): array
    {
        /** @var WebOrder $record */
        $record = $this->getRecord();
        $user = Auth::user();
        $isDelivery = $user instanceof User && $user->isDelivery();

        if (! $isDelivery || $record->status !== 'confirmed') {
            return [];
        }

        return [
            Action::make('entregado')
                ->label('Marcar entregado')
                ->color('success')
                ->icon('heroicon-o-truck')
                ->action(function () use ($record): void {
                    $record->update(['status' => 'delivered']);

                    Notification::make()
                        ->title('Pedido marcado como entregado')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
        ];
    }

    protected function getFormActions(): array
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isDelivery()) {
            return [];
        }

        return parent::getFormActions();
    }
}

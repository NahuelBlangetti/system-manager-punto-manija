<?php

namespace App\Filament\Resources\WebOrders\Pages;

use App\Filament\Resources\WebOrders\WebOrderResource;
use App\Models\User;
use App\Models\WebOrder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Size;
use Illuminate\Support\Facades\Auth;

class EditWebOrder extends EditRecord
{
    protected static string $resource = WebOrderResource::class;

    protected function getHeaderActions(): array
    {
        /** @var WebOrder $record */
        $record = $this->getRecord();
        $actions = [];

        $tel = static::telUrl($record->customer_phone);
        if ($tel) {
            $actions[] = Action::make('llamar')
                ->label('Llamar')
                ->icon('heroicon-o-phone')
                ->color('gray')
                ->size(Size::Large)
                ->url($tel);
        }

        $maps = static::mapsUrl($record);
        if ($maps) {
            $actions[] = Action::make('mapa')
                ->label('Abrir mapa')
                ->icon('heroicon-o-map')
                ->color('gray')
                ->size(Size::Large)
                ->url($maps)
                ->openUrlInNewTab();
        }

        $whatsapp = static::whatsappUrl($record->customer_phone);
        if ($whatsapp) {
            $actions[] = Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->size(Size::Large)
                ->url($whatsapp)
                ->openUrlInNewTab();
        }

        $user = Auth::user();
        $isDelivery = $user instanceof User && $user->isDelivery();

        if ($record->status === 'confirmed' && ($isDelivery || $user instanceof User)) {
            $actions[] = Action::make('entregado')
                ->label('Marcar entregado')
                ->color('success')
                ->icon('heroicon-o-truck')
                ->size(Size::Large)
                ->action(function () use ($record): void {
                    $record->update(['status' => 'delivered']);

                    Notification::make()
                        ->title('Pedido marcado como entregado')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                });
        }

        return $actions;
    }

    protected function getFormActions(): array
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isDelivery()) {
            return [];
        }

        return parent::getFormActions();
    }

    protected static function telUrl(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return filled($digits) ? 'tel:'.$digits : null;
    }

    protected static function mapsUrl(WebOrder $record): ?string
    {
        if ($record->lat && $record->lng) {
            return "https://www.google.com/maps?q={$record->lat},{$record->lng}";
        }

        if (filled($record->address)) {
            return 'https://www.google.com/maps/search/?api=1&query='.urlencode($record->address);
        }

        return null;
    }

    protected static function whatsappUrl(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (! filled($digits)) {
            return null;
        }

        if (! str_starts_with($digits, '54') && strlen($digits) <= 10) {
            $digits = '54'.$digits;
        }

        return 'https://wa.me/'.$digits;
    }
}

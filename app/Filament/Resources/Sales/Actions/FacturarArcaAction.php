<?php

namespace App\Filament\Resources\Sales\Actions;

use App\Models\Sale;
use App\Services\Arca\ArcaException;
use App\Services\Arca\FacturarVenta;
use App\Services\Tickets\FacturaTicketEscPosBuilder;
use App\Support\EscPosPrint;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;

class FacturarArcaAction
{
    public static function make(): Action
    {
        return Action::make('facturarArca')
            ->label('Factura ARCA')
            ->icon('heroicon-o-document-check')
            ->color('info')
            ->visible(fn (Sale $record): bool => FacturarVenta::disponible()
                && $record->status === 'completed'
                && $record->factura === null)
            ->requiresConfirmation()
            ->modalHeading('Emitir Factura C')
            ->modalDescription('Se pide el CAE a ARCA para esta venta. Si ARCA no responde, la venta queda cobrada y se puede reintentar.')
            ->action(function (Sale $record, HasTable $livewire): void {
                try {
                    $factura = app(FacturarVenta::class)->handle($record, Auth::id());
                } catch (\Throwable $e) {
                    if (! $e instanceof ArcaException) {
                        report($e);
                    }

                    Notification::make()
                        ->title('Sin factura de ARCA')
                        ->body($e instanceof ArcaException ? $e->getMessage() : 'Error inesperado al facturar.')
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                EscPosPrint::dispatch($livewire, app(FacturaTicketEscPosBuilder::class)->buildFactura($factura));

                Notification::make()
                    ->title('Factura C '.$factura->numeroCompleto().' autorizada por ARCA')
                    ->success()
                    ->send();
            });
    }
}

<?php

namespace App\Filament\Resources\Sales\Actions;

use App\Models\Sale;
use App\Services\Tickets\FacturaTicketEscPosBuilder;
use App\Support\EscPosPrint;
use Filament\Actions\Action;
use Filament\Tables\Contracts\HasTable;

class PrintFacturaAction
{
    public static function make(): Action
    {
        return Action::make('printFactura')
            ->label('Imprimir factura')
            ->icon('heroicon-o-qr-code')
            ->color('info')
            ->visible(fn (Sale $record): bool => $record->factura !== null)
            ->action(function (Sale $record, HasTable $livewire): void {
                $factura = $record->factura;

                if (! $factura) {
                    return;
                }

                EscPosPrint::dispatch($livewire, app(FacturaTicketEscPosBuilder::class)->buildFactura($factura));
            });
    }
}

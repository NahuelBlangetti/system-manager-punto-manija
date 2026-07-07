<?php

namespace App\Filament\Resources\CashRegisters\Pages;

use App\Filament\Resources\CashRegisters\CashRegisterResource;
use App\Models\CashRegister;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCashRegister extends CreateRecord
{
    protected static string $resource = CashRegisterResource::class;

    // Pre-cargar el monto de cierre de la última caja como monto de apertura
    protected function fillForm(): void
    {
        parent::fillForm();

        $last = CashRegister::where('status', 'closed')
            ->whereNotNull('closing_amount')
            ->orderByDesc('closed_at')
            ->first();

        if (! $last) {
            return;
        }

        $this->data['opening_amount'] = (float) $last->closing_amount;

        $fechaCierre = $last->closed_at?->format('d/m/Y H:i') ?? 'fecha desconocida';

        Notification::make()
            ->title('Monto pre-cargado')
            ->body(
                'El monto de apertura se completó con el cierre de la última caja ('
                .$fechaCierre.'): $'
                .number_format($last->closing_amount, 2, ',', '.')
                .'. Podés cambiarlo si es necesario.'
            )
            ->info()
            ->send();
    }

    // V6: bloquear apertura de segunda caja si ya hay una abierta
    protected function beforeCreate(): void
    {
        if (CashRegister::where('status', 'open')->exists()) {
            Notification::make()
                ->title('Ya hay una caja abierta')
                ->body('Cerrá la caja actual antes de abrir una nueva.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $this->record->recalculate();
    }
}

<?php

namespace App\Filament\Resources\CashRegisters\Pages;

use App\Filament\Resources\CashRegisters\CashRegisterResource;
use App\Models\CashRegister;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCashRegister extends CreateRecord
{
    protected static string $resource = CashRegisterResource::class;

    public function getTitle(): string
    {
        return 'Abrir caja';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'open';

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Caja abierta';
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

<?php

namespace App\Filament\Resources\CashRegisters\Pages;

use App\Filament\Resources\CashRegisters\CashRegisterResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCashRegister extends EditRecord
{
    protected static string $resource = CashRegisterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // A5: bloquear eliminación si la caja tiene ventas asociadas
            Action::make('delete')
                ->label('Eliminar')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Eliminar caja')
                ->modalDescription('Esta acción es permanente. No se puede deshacer.')
                ->before(function (Action $action) {
                    if ($this->record->sales()->exists()) {
                        Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Esta caja tiene ventas asociadas. Eliminándola las ventas quedarían huérfanas y no aparecerían en ningún arqueo.')
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->cancel();
                    }
                })
                ->action(function () {
                    $this->record->delete();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    // A4: bloquear reapertura de caja cerrada
    protected function beforeSave(): void
    {
        if ($this->record->status === 'closed' && $this->data['status'] === 'open') {
            Notification::make()
                ->title('No se puede reabrir una caja cerrada')
                ->body('El arqueo de este turno ya fue registrado. Abrí una nueva caja si necesitás continuar operando.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        $this->record->recalculate();

        if ($this->record->status === 'closed' && (float) $this->record->difference !== 0.0) {
            $this->notifyDifference();
        }
    }

    private function notifyDifference(): void
    {
        $difference = (float) $this->record->difference;
        $formatted = '$ '.number_format(abs($difference), 2, ',', '.');
        $label = $difference < 0 ? "faltante de {$formatted}" : "sobrante de {$formatted}";

        $action = Action::make('ver')
            ->label('Ver caja')
            ->url(CashRegisterResource::getUrl('edit', ['record' => $this->record]))
            ->button();

        $cashierName = $this->record->user->name;

        User::all()->each(function (User $user) use ($label, $difference, $action, $cashierName) {
            $notification = Notification::make()
                ->title('Diferencia de caja al cerrar')
                ->body("La caja de {$cashierName} cerró con un {$label}.")
                ->actions([$action]);

            $difference < 0 ? $notification->danger() : $notification->warning();

            $notification->sendToDatabase($user);
        });
    }
}

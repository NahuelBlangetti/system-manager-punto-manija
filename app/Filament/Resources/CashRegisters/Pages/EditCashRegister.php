<?php

namespace App\Filament\Resources\CashRegisters\Pages;

use App\Filament\Resources\CashRegisters\Actions\CloseCashRegisterAction;
use App\Filament\Resources\CashRegisters\CashRegisterResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCashRegister extends EditRecord
{
    protected static string $resource = CashRegisterResource::class;

    public function getTitle(): string
    {
        return $this->record->status === 'open' ? 'Caja abierta' : 'Detalle de caja';
    }

    // Una caja cerrada queda de solo lectura: no hay botón de guardar.
    protected function getFormActions(): array
    {
        if ($this->record->status === 'closed') {
            return [];
        }

        return parent::getFormActions();
    }

    protected function getHeaderActions(): array
    {
        return [
            CloseCashRegisterAction::make()
                ->after(fn () => $this->redirect(static::getResource()::getUrl('index'))),

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

    // El cierre solo se hace desde el botón "Cerrar caja"; una caja cerrada es de solo lectura.
    protected function beforeSave(): void
    {
        if ($this->record->status === 'closed') {
            Notification::make()
                ->title('Caja cerrada')
                ->body('Los registros de una caja cerrada no se pueden modificar.')
                ->warning()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function afterSave(): void
    {
        $this->record->recalculate();
    }
}

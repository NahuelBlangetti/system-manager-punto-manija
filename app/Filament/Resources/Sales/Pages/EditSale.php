<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected string $originalStatus = '';

    protected function getHeaderActions(): array
    {
        return [
            // A1: acción de borrado personalizada que revierte stock antes de eliminar
            Action::make('delete')
                ->label('Eliminar')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Eliminar venta')
                ->modalDescription(
                    fn () => $this->record->status === 'completed'
                        ? 'Se eliminarán la venta y todos sus ítems. El stock de los productos se repondrá automáticamente.'
                        : 'Se eliminará la venta y todos sus ítems.'
                )
                ->action(function () {
                    try {
                        DB::transaction(function () {
                            if ($this->record->status === 'completed') {
                                $this->revertStock($this->record, 'Reversión por eliminación');
                            }
                            $this->record->delete();
                        });

                        $this->redirect($this->getResource()::getUrl('index'));

                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('No se pudo eliminar la venta')
                            ->body('Revisá el stock manualmente. Error: '.$e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }

    // A2+A3: capturar estado original antes de guardar
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->originalStatus = $this->record->status;

        $items = $data['items'] ?? [];
        $subtotal = collect($items)->sum(fn ($item) => (float) ($item['subtotal'] ?? 0));
        $discount = (float) ($data['discount'] ?? 0);

        $data['subtotal'] = round($subtotal, 2);
        $data['total'] = round(max(0, $subtotal - $discount), 2);

        // A3: protección backend — restaurar campos financieros si la venta estaba completada
        // Impide que alguien bypasee la UI y edite payment_method, caja o montos directamente.
        if ($this->originalStatus === 'completed') {
            $data['payment_method'] = $this->record->payment_method;
            $data['cash_register_id'] = $this->record->cash_register_id;
            $data['discount'] = (string) $this->record->discount;
            $data['subtotal'] = (string) $this->record->subtotal;
            $data['total'] = (string) $this->record->total;
        }

        return $data;
    }

    // A2: revertir stock al cancelar una venta completada
    protected function afterSave(): void
    {
        if ($this->originalStatus === 'completed' && $this->record->status === 'cancelled') {
            try {
                DB::transaction(function () {
                    $this->revertStock($this->record, 'Reversión por cancelación');
                });
            } catch (\Throwable $e) {
                Notification::make()
                    ->title('Venta cancelada, pero hubo un error al reponer stock')
                    ->body('Revisá el inventario manualmente. Error: '.$e->getMessage())
                    ->warning()
                    ->persistent()
                    ->send();
            }
        }
    }

    private function revertStock(Sale $sale, string $reason): void
    {
        foreach ($sale->items as $item) {
            $product = Product::lockForUpdate()->find($item->product_id);
            if (! $product) {
                continue;
            }

            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'type' => 'in',
                'quantity' => $item->quantity,
                'stock_before' => $product->stock,
                'stock_after' => $product->stock + $item->quantity,
                'notes' => "{$reason} de venta {$sale->sale_number}",
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
            ]);

            $product->increment('stock', $item->quantity);
        }
    }
}

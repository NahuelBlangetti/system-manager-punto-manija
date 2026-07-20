<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Filament\Resources\Sales\Actions\PrintTicketAction;
use App\Models\Product;
use App\Services\Stock\ComboStockService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('sale_number')
                    ->label('Nro.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Vendedor')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Medio de pago')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Efectivo',
                        'transfer' => 'Transferencia',
                        'card' => 'Tarjeta',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        'card' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => '$ '.number_format((float) $state, 2, ',', '.'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('payment_method')
                    ->label('Medio de pago')
                    ->options([
                        'cash' => 'Efectivo',
                        'transfer' => 'Transferencia',
                        'card' => 'Tarjeta',
                    ]),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                    ]),
            ])
            ->recordActions([
                PrintTicketAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // A1: bulk delete con reversión de stock para ventas completadas
                    BulkAction::make('delete')
                        ->label('Eliminar seleccionadas')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar ventas seleccionadas')
                        ->modalDescription('Las ventas completadas repondrán su stock automáticamente.')
                        ->action(function (Collection $records) {
                            DB::transaction(function () use ($records) {
                                foreach ($records as $record) {
                                    if ($record->status === 'completed') {
                                        foreach ($record->items as $item) {
                                            $product = Product::lockForUpdate()->find($item->product_id);
                                            if (! $product) {
                                                continue;
                                            }

                                            ComboStockService::restore($product, $item->quantity, "Reversión por eliminación de venta {$record->sale_number}", $record, Auth::id());
                                        }
                                    }

                                    $record->delete();
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}

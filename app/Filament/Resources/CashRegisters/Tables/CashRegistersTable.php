<?php

namespace App\Filament\Resources\CashRegisters\Tables;

use App\Filament\Support\BulkActionHelpers;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CashRegistersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable(),
                TextColumn::make('opening_amount')
                    ->label('Monto apertura')
                    ->formatStateUsing(fn ($state) => '$ '.number_format((float) $state, 2, ',', '.'))
                    ->sortable(),
                TextColumn::make('closing_amount')
                    ->label('Monto cierre')
                    ->formatStateUsing(fn ($state) => $state !== null ? '$ '.number_format((float) $state, 2, ',', '.') : '—')
                    ->sortable(),
                TextColumn::make('expected_amount')
                    ->label('Monto esperado')
                    ->formatStateUsing(fn ($state) => $state !== null ? '$ '.number_format((float) $state, 2, ',', '.') : '—')
                    ->sortable(),
                TextColumn::make('difference')
                    ->label('Diferencia')
                    ->formatStateUsing(function ($state): string {
                        if ($state === null) {
                            return '—';
                        }
                        $val = (float) $state;
                        $formatted = '$ '.number_format(abs($val), 2, ',', '.');
                        if ($val < 0) {
                            return "▼ {$formatted}";
                        }
                        if ($val > 0) {
                            return "▲ {$formatted}";
                        }

                        return $formatted;
                    })
                    ->color(fn ($state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state < 0 => 'danger',
                        (float) $state > 0 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                TextColumn::make('opened_at')
                    ->label('Apertura')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('closed_at')
                    ->label('Cierre')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Abierta',
                        'closed' => 'Cerrada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkActionHelpers::safeDelete(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\WebOrders\Tables;

use App\Models\User;
use App\Models\WebOrder;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WebOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable(),
                TextColumn::make('customer_phone')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('delivery_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'delivery' => 'Envío',
                        'pickup' => 'Retiro',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'delivery' => 'info',
                        'pickup' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('address')
                    ->label('Dirección')
                    ->limit(30)
                    ->url(fn (WebOrder $record): ?string => $record->lat && $record->lng
                        ? "https://www.google.com/maps?q={$record->lat},{$record->lng}"
                        : null)
                    ->openUrlInNewTab(),
                TextColumn::make('distance_km')
                    ->label('Distancia')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 1).' km' : '—'),
                TextColumn::make('shipping_cost')
                    ->label('Envío')
                    ->formatStateUsing(fn ($state) => $state !== null ? '$ '.number_format((float) $state, 2, ',', '.') : 'A coordinar'),
                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => '$ '.number_format((float) $state, 2, ',', '.'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'delivered' => 'Entregado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'delivered' => 'Entregado',
                        'cancelled' => 'Cancelado',
                    ]),
                SelectFilter::make('delivery_type')
                    ->label('Tipo')
                    ->options([
                        'delivery' => 'Envío',
                        'pickup' => 'Retiro',
                    ]),
            ])
            ->recordActions([
                Action::make('confirmar')
                    ->label('Confirmar')
                    ->color('info')
                    ->icon('heroicon-o-check')
                    ->visible(fn (WebOrder $record) => $record->status === 'pending' && ! static::isDeliveryUser())
                    ->action(fn (WebOrder $record) => $record->update(['status' => 'confirmed'])),
                Action::make('entregado')
                    ->label('Marcar entregado')
                    ->color('success')
                    ->icon('heroicon-o-truck')
                    ->visible(fn (WebOrder $record) => $record->status === 'confirmed')
                    ->action(fn (WebOrder $record) => $record->update(['status' => 'delivered'])),
                Action::make('cancelar')
                    ->label('Cancelar')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->requiresConfirmation()
                    ->visible(fn (WebOrder $record) => in_array($record->status, ['pending', 'confirmed'], true) && ! static::isDeliveryUser())
                    ->action(fn (WebOrder $record) => $record->update(['status' => 'cancelled'])),
                EditAction::make(),
            ]);
    }

    protected static function isDeliveryUser(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isDelivery();
    }
}

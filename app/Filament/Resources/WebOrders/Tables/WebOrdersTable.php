<?php

namespace App\Filament\Resources\WebOrders\Tables;

use App\Models\User;
use App\Models\WebOrder;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WebOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->contentGrid([
                'default' => 1,
                'lg' => 2,
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->filtersLayout(FiltersLayout::Dropdown)
            ->columns([
                Stack::make([
                    Split::make([
                        TextColumn::make('customer_name')
                            ->label('Cliente')
                            ->weight(FontWeight::SemiBold)
                            ->size('lg')
                            ->searchable()
                            ->description(fn (?WebOrder $record): string => $record?->created_at?->format('d/m/Y H:i') ?? ''),
                        TextColumn::make('status')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'pending' => 'Pendiente',
                                'confirmed' => 'Confirmado',
                                'delivered' => 'Entregado',
                                'cancelled' => 'Cancelado',
                                default => (string) $state,
                            })
                            ->color(fn (?string $state): string => match ($state) {
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'delivered' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                    ]),
                    Split::make([
                        TextColumn::make('delivery_type')
                            ->label('Tipo')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'delivery' => 'Envío',
                                'pickup' => 'Retiro',
                                default => (string) $state,
                            })
                            ->color(fn (?string $state): string => match ($state) {
                                'delivery' => 'info',
                                'pickup' => 'gray',
                                default => 'gray',
                            }),
                        TextColumn::make('total')
                            ->label('Total')
                            ->alignEnd()
                            ->weight(FontWeight::Bold)
                            ->formatStateUsing(fn ($state) => '$ '.number_format((float) $state, 0, ',', '.')),
                    ]),
                    TextColumn::make('address')
                        ->label('Dirección')
                        ->placeholder('Retiro en el local')
                        ->wrap()
                        ->icon('heroicon-m-map-pin')
                        ->color('gray')
                        ->url(fn (?WebOrder $record): ?string => $record ? static::mapsUrl($record) : null)
                        ->openUrlInNewTab()
                        ->visible(fn (?WebOrder $record): bool => $record !== null && (filled($record->address) || $record->delivery_type === 'delivery')),
                    TextColumn::make('customer_phone')
                        ->label('Teléfono')
                        ->icon('heroicon-m-phone')
                        ->searchable()
                        ->url(fn (?WebOrder $record): ?string => $record ? static::telUrl($record->customer_phone) : null)
                        ->color('primary'),
                    TextColumn::make('distance_km')
                        ->label('Distancia')
                        ->formatStateUsing(function ($state, ?WebOrder $record): string {
                            if (! $record) {
                                return '';
                            }

                            $distance = $state !== null ? number_format((float) $state, 1).' km' : '—';
                            $shipping = $record->shipping_cost !== null
                                ? '$ '.number_format((float) $record->shipping_cost, 0, ',', '.')
                                : 'a coordinar';

                            return "{$distance} · Envío {$shipping}";
                        })
                        ->color('gray')
                        ->visible(fn (?WebOrder $record): bool => $record?->delivery_type === 'delivery'),
                ])->space(2),
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
                Action::make('llamar')
                    ->label('Llamar')
                    ->icon('heroicon-o-phone')
                    ->color('gray')
                    ->button()
                    ->size(Size::Large)
                    ->url(fn (?WebOrder $record): ?string => $record ? static::telUrl($record->customer_phone) : null)
                    ->visible(fn (?WebOrder $record): bool => $record !== null && filled(static::telUrl($record->customer_phone))),
                Action::make('mapa')
                    ->label('Mapa')
                    ->icon('heroicon-o-map')
                    ->color('gray')
                    ->button()
                    ->size(Size::Large)
                    ->url(fn (?WebOrder $record): ?string => $record ? static::mapsUrl($record) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (?WebOrder $record): bool => $record !== null && filled(static::mapsUrl($record))),
                Action::make('confirmar')
                    ->label('Confirmar')
                    ->color('info')
                    ->icon('heroicon-o-check')
                    ->button()
                    ->size(Size::Large)
                    ->visible(fn (?WebOrder $record): bool => $record?->status === 'pending' && ! static::isDeliveryUser())
                    ->action(fn (WebOrder $record) => $record->update(['status' => 'confirmed'])),
                Action::make('entregado')
                    ->label('Entregado')
                    ->color('success')
                    ->icon('heroicon-o-truck')
                    ->button()
                    ->size(Size::Large)
                    ->visible(fn (?WebOrder $record): bool => $record?->status === 'confirmed')
                    ->action(fn (WebOrder $record) => $record->update(['status' => 'delivered'])),
                Action::make('cancelar')
                    ->label('Cancelar')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->button()
                    ->size(Size::Large)
                    ->requiresConfirmation()
                    ->visible(fn (?WebOrder $record): bool => $record !== null && in_array($record->status, ['pending', 'confirmed'], true) && ! static::isDeliveryUser())
                    ->action(fn (WebOrder $record) => $record->update(['status' => 'cancelled'])),
                EditAction::make()
                    ->label('Ver')
                    ->button()
                    ->size(Size::Large)
                    ->color('gray'),
            ]);
    }

    protected static function isDeliveryUser(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isDelivery();
    }

    protected static function telUrl(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return filled($digits) ? 'tel:'.$digits : null;
    }

    protected static function mapsUrl(WebOrder $record): ?string
    {
        if ($record->lat && $record->lng) {
            return "https://www.google.com/maps?q={$record->lat},{$record->lng}";
        }

        if (filled($record->address)) {
            return 'https://www.google.com/maps/search/?api=1&query='.urlencode($record->address);
        }

        return null;
    }
}

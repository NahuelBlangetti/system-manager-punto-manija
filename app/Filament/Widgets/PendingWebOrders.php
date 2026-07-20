<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\WebOrders\WebOrderResource;
use App\Models\WebOrder;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingWebOrders extends TableWidget
{
    protected static ?string $heading = 'Pedidos web pendientes';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WebOrder::query()
                    ->where('status', 'pending')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Cliente'),
                TextColumn::make('customer_phone')
                    ->label('Teléfono'),
                TextColumn::make('delivery_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'delivery' ? 'Envío' : 'Retiro')
                    ->color(fn (string $state): string => $state === 'delivery' ? 'info' : 'gray'),
                TextColumn::make('address')
                    ->label('Dirección')
                    ->limit(30),
                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => '$ '.number_format((float) $state, 2, ',', '.')),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Ver')
                    ->url(fn (WebOrder $record) => WebOrderResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false);
    }
}

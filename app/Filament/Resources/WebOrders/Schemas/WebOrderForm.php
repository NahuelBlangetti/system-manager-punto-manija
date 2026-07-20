<?php

namespace App\Filament\Resources\WebOrders\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WebOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Cliente y entrega')
                ->columns(2)
                ->schema([
                    TextInput::make('customer_name')
                        ->label('Cliente')
                        ->disabled(),
                    TextInput::make('customer_phone')
                        ->label('Teléfono')
                        ->disabled(),
                    TextInput::make('delivery_type')
                        ->label('Tipo de entrega')
                        ->formatStateUsing(fn (?string $state) => $state === 'delivery' ? 'Envío a domicilio' : 'Retiro en el local')
                        ->disabled(),
                    TextInput::make('address')
                        ->label('Dirección')
                        ->disabled(),
                    TextInput::make('distance_km')
                        ->label('Distancia (km)')
                        ->disabled(),
                    TextInput::make('shipping_cost')
                        ->label('Costo de envío')
                        ->prefix('$')
                        ->disabled(),
                ]),

            Section::make('Items del pedido')
                ->schema([
                    Repeater::make('items')
                        ->label('')
                        ->relationship('items')
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columns(4)
                        ->schema([
                            TextInput::make('product_name')
                                ->label('Producto')
                                ->disabled()
                                ->columnSpan(2),
                            TextInput::make('quantity')
                                ->label('Cantidad')
                                ->disabled(),
                            TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->prefix('$')
                                ->disabled(),
                        ]),
                ]),

            Section::make('Gestión')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('Estado')
                        ->options([
                            'pending' => 'Pendiente',
                            'confirmed' => 'Confirmado',
                            'delivered' => 'Entregado',
                            'cancelled' => 'Cancelado',
                        ])
                        ->required(),
                    TextInput::make('total')
                        ->label('Total')
                        ->prefix('$')
                        ->disabled(),
                    Textarea::make('notes')
                        ->label('Notas internas')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}

<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\CashRegister;
use App\Models\Product;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Datos de la venta')
                ->columns(2)
                ->schema([
                    // A3: campos financieros bloqueados para ventas ya completadas
                    Select::make('payment_method')
                        ->label('Medio de pago')
                        ->options([
                            'cash' => 'Efectivo',
                            'transfer' => 'Transferencia',
                            'card' => 'Tarjeta',
                        ])
                        ->required()
                        ->disabled(fn ($record) => $record?->status === 'completed'),

                    Select::make('cash_register_id')
                        ->label('Caja registradora')
                        ->options(fn () => CashRegister::where('status', 'open')
                            ->get()
                            ->mapWithKeys(fn ($cr) => [
                                $cr->id => 'Caja #'.$cr->id.' — abierta '.$cr->opened_at?->format('d/m/Y H:i'),
                            ])
                        )
                        ->searchable()
                        ->nullable()
                        ->placeholder('Sin caja asignada')
                        ->disabled(fn ($record) => $record?->status === 'completed'),

                    TextInput::make('discount')
                        ->label('Descuento')
                        ->numeric()
                        ->default(0)
                        ->minValue(0) // F1
                        ->prefix('$')
                        ->live()
                        ->disabled(fn ($record) => $record?->status === 'completed'),

                    Select::make('status')
                        ->label('Estado')
                        ->options([
                            'completed' => 'Completada',
                            'cancelled' => 'Cancelada',
                        ])
                        ->default('completed')
                        ->required(),

                    Textarea::make('notes')
                        ->label('Notas')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Productos vendidos')
                ->schema([
                    Repeater::make('items')
                        ->label('')
                        ->relationship('items')
                        ->columns(12)
                        ->addActionLabel('+ Agregar producto')
                        ->minItems(1)
                        ->reorderable(false)
                        // A3: ítems no editables en ventas completadas
                        ->addable(fn ($record) => $record?->status !== 'completed')
                        ->deletable(fn ($record) => $record?->status !== 'completed')
                        ->schema([
                            Select::make('product_id')
                                ->label('Producto')
                                ->options(fn () => Product::where('active', true)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn ($p) => [
                                        $p->id => $p->name.' (stock: '.$p->stock.')',
                                    ])
                                )
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    if (! $state) {
                                        return;
                                    }
                                    $product = Product::find($state);
                                    if (! $product) {
                                        return;
                                    }
                                    $set('product_name', $product->name);
                                    $set('unit_price', $product->sale_price);
                                    $qty = max(1, (int) ($get('quantity') ?? 1));
                                    $set('subtotal', round($qty * (float) $product->sale_price, 2));
                                })
                                ->disabled(fn ($record) => $record?->status === 'completed')
                                ->columnSpan(5),

                            Hidden::make('product_name'),

                            TextInput::make('unit_price')
                                ->label('Precio unit.')
                                ->numeric()
                                ->required()
                                ->prefix('$')
                                ->minValue(0)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $qty = max(1, (int) ($get('quantity') ?? 1));
                                    $set('subtotal', round($qty * (float) $state, 2));
                                })
                                ->disabled(fn ($record) => $record?->status === 'completed')
                                ->columnSpan(3),

                            TextInput::make('quantity')
                                ->label('Cantidad')
                                ->integer()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $price = (float) ($get('unit_price') ?? 0);
                                    $set('subtotal', round(max(1, (int) $state) * $price, 2));
                                })
                                ->disabled(fn ($record) => $record?->status === 'completed')
                                ->columnSpan(2),

                            TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated()
                                ->default(0)
                                ->columnSpan(2),
                        ]),
                ]),

            Section::make('Resumen')
                ->columns(3)
                ->schema([
                    TextEntry::make('subtotal_preview')
                        ->label('Subtotal')
                        ->state(function (Get $get): string {
                            $subtotal = collect($get('items') ?? [])
                                ->sum(fn ($item) => (float) ($item['subtotal'] ?? 0));

                            return '$ '.number_format($subtotal, 2, ',', '.');
                        }),

                    TextEntry::make('discount_preview')
                        ->label('Descuento')
                        ->state(function (Get $get): string {
                            return '$ '.number_format((float) ($get('discount') ?? 0), 2, ',', '.');
                        }),

                    TextEntry::make('total_preview')
                        ->label('Total a cobrar')
                        ->state(function (Get $get): string {
                            $subtotal = collect($get('items') ?? [])
                                ->sum(fn ($item) => (float) ($item['subtotal'] ?? 0));
                            $discount = (float) ($get('discount') ?? 0);

                            return '$ '.number_format(max(0, $subtotal - $discount), 2, ',', '.');
                        }),
                ]),
        ]);
    }
}

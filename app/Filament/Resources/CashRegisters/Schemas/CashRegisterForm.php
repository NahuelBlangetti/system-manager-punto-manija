<?php

namespace App\Filament\Resources\CashRegisters\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CashRegisterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Apertura')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Usuario')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => Auth::id())
                            // El empleado solo puede abrir caja a su propio nombre.
                            ->disabled(function (): bool {
                                $user = Auth::user();

                                return $user instanceof User && $user->isEmpleado();
                            })
                            ->dehydrated()
                            ->required(),
                        DateTimePicker::make('opened_at')
                            ->label('Fecha apertura')
                            ->required()
                            ->default(now()),
                        TextInput::make('opening_amount')
                            ->label('Monto apertura')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0) // F1
                            ->prefix('$'),
                    ]),

                Section::make('Movimientos de caja')
                    ->description('Registrá ingresos (dinero que entra sin ser una venta) y egresos (gastos pagados con la caja).')
                    ->schema([
                        Repeater::make('entries')
                            ->label('')
                            ->relationship()
                            ->schema([
                                Select::make('type')
                                    ->label('Tipo')
                                    ->options([
                                        'income' => 'Ingreso',
                                        'expense' => 'Egreso',
                                    ])
                                    ->required()
                                    ->native(false),
                                TextInput::make('amount')
                                    ->label('Monto')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->prefix('$'),
                                TextInput::make('description')
                                    ->label('Descripción')
                                    ->required()
                                    ->columnSpan(2),
                            ])
                            ->columns(3)
                            ->addActionLabel('Agregar movimiento')
                            ->reorderable(false)
                            ->defaultItems(0),
                    ]),

                Section::make('Cierre')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'open' => 'Abierta',
                                'closed' => 'Cerrada',
                            ])
                            ->default('open')
                            ->required()
                            ->native(false),
                        DateTimePicker::make('closed_at')
                            ->label('Fecha cierre')
                            ->after('opened_at'), // F3: cierre debe ser posterior a apertura
                        TextInput::make('closing_amount')
                            ->label('Dinero contado')
                            ->numeric()
                            ->minValue(0) // F1
                            ->prefix('$')
                            ->helperText('Ingresá el total físico contado al cerrar.'),
                        TextInput::make('expected_amount')
                            ->label('Monto esperado')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->helperText('Calculado automáticamente al guardar.'),
                        TextInput::make('difference')
                            ->label('Diferencia')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->helperText('Contado − Esperado.'),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

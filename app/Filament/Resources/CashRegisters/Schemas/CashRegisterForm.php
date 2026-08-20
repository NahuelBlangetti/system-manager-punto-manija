<?php

namespace App\Filament\Resources\CashRegisters\Schemas;

use App\Models\CashRegister;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CashRegisterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Apertura')
                    ->description('Indicá con cuánto efectivo inicia el turno.')
                    ->visible(fn (?CashRegister $record): bool => $record === null)
                    ->columnSpanFull()
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

                        Toggle::make('use_previous_closing')
                            ->label('Usar el efectivo del cierre anterior')
                            ->default(fn (): bool => CashRegister::lastClosed() !== null)
                            ->live()
                            ->visible(fn (): bool => CashRegister::lastClosed() !== null)
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->helperText(function (): ?string {
                                $last = CashRegister::lastClosed();

                                if (! $last) {
                                    return null;
                                }

                                return 'Cierre del '.$last->closed_at->format('d/m/Y H:i')
                                    .': '.CashRegister::formatMoney((float) $last->closing_amount)
                                    .'. Desactivá esta opción si el efectivo inicial es otro.';
                            })
                            ->afterStateUpdated(function (bool $state, Set $set): void {
                                $set('opening_amount', $state
                                    ? CashRegister::suggestedOpeningAmount()
                                    : 0);
                            }),

                        TextInput::make('opening_amount')
                            ->label('Monto apertura')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(fn (): float => CashRegister::suggestedOpeningAmount())
                            ->prefix('$')
                            ->helperText(fn (Get $get): string => $get('use_previous_closing')
                                ? 'Monto tomado del cierre anterior. Desactivá la opción de arriba para ingresar otro valor.'
                                : 'Efectivo inicial en el cajón. Podés ingresar $0 si arrancás sin efectivo.')
                            ->disabled(fn (Get $get): bool => (bool) $get('use_previous_closing')
                                && CashRegister::lastClosed() !== null)
                            ->dehydrated(),
                    ]),

                Section::make('Turno en curso')
                    ->description('El cierre se hace desde el botón "Cerrar caja". Acá solo se registran movimientos de efectivo del turno.')
                    ->visible(fn (?CashRegister $record): bool => $record?->status === 'open')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Usuario')
                            ->relationship('user', 'name')
                            ->disabled(),

                        TextInput::make('opening_amount')
                            ->label('Monto apertura')
                            ->prefix('$')
                            ->disabled(),

                        DateTimePicker::make('opened_at')
                            ->label('Apertura')
                            ->disabled(),

                        Text::make(fn (?CashRegister $record): string => 'Ventas en efectivo: '
                            .CashRegister::formatMoney($record?->cashSalesTotal() ?? 0))
                            ->columnSpanFull(),

                        Text::make(fn (?CashRegister $record): string => 'Monto esperado si cerrás ahora (solo efectivo): '
                            .CashRegister::formatMoney($record?->calculateExpectedAmount() ?? 0))
                            ->columnSpanFull(),
                    ]),

                Section::make('Movimientos de caja')
                    ->description('Registrá ingresos (dinero que entra sin ser una venta) y egresos (gastos pagados con la caja).')
                    ->visible(fn (?CashRegister $record): bool => $record?->status === 'open')
                    ->columnSpanFull()
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

                Section::make('Notas')
                    ->visible(fn (?CashRegister $record): bool => $record?->status === 'open')
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ]),

                Section::make('Cierre')
                    ->description('Resumen del turno cerrado.')
                    ->visible(fn (?CashRegister $record): bool => $record?->status === 'closed')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Usuario')
                            ->relationship('user', 'name')
                            ->disabled(),

                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'open' => 'Abierta',
                                'closed' => 'Cerrada',
                            ])
                            ->disabled(),

                        TextInput::make('opening_amount')
                            ->label('Monto apertura')
                            ->prefix('$')
                            ->disabled(),

                        TextInput::make('closing_amount')
                            ->label('Monto cierre')
                            ->prefix('$')
                            ->disabled(),

                        TextInput::make('expected_amount')
                            ->label('Monto esperado')
                            ->prefix('$')
                            ->disabled(),

                        TextInput::make('difference')
                            ->label('Diferencia')
                            ->prefix('$')
                            ->disabled(),

                        DateTimePicker::make('opened_at')
                            ->label('Apertura')
                            ->disabled(),

                        DateTimePicker::make('closed_at')
                            ->label('Cierre')
                            ->disabled(),

                        Text::make(fn (?CashRegister $record): string => 'Ingresos de caja: '
                            .CashRegister::formatMoney($record?->incomeEntriesTotal() ?? 0)
                            .' · Egresos de caja: '
                            .CashRegister::formatMoney($record?->expenseEntriesTotal() ?? 0))
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Promotions\Schemas;

use App\Enums\PromotionType;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Promoción')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: 2x1 en gaseosas'),
                        Select::make('category_id')
                            ->label('Categoría')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Todas las categorías')
                            ->helperText('Dejalo vacío para que aplique a todo el catálogo.'),
                        Select::make('type')
                            ->label('Tipo de promoción')
                            ->options(PromotionType::class)
                            ->required()
                            ->live()
                            ->native(false),
                        TextInput::make('percentage_value')
                            ->label('Porcentaje de descuento')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required(fn ($get) => $get('type') === PromotionType::Percentage->value)
                            ->visible(fn ($get) => $get('type') === PromotionType::Percentage->value),
                        Toggle::make('active')
                            ->label('Activa')
                            ->default(true),
                    ]),

                Section::make('Vigencia')
                    ->description('Dejá los días vacíos para que aplique todos los días, y los horarios vacíos para que aplique todo el día.')
                    ->columns(2)
                    ->schema([
                        CheckboxList::make('days_of_week')
                            ->label('Días de la semana')
                            ->options([
                                1 => 'Lunes',
                                2 => 'Martes',
                                3 => 'Miércoles',
                                4 => 'Jueves',
                                5 => 'Viernes',
                                6 => 'Sábado',
                                0 => 'Domingo',
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                        TimePicker::make('start_time')
                            ->label('Hora inicio')
                            ->seconds(false),
                        TimePicker::make('end_time')
                            ->label('Hora fin')
                            ->seconds(false)
                            ->helperText('Si es menor a la hora de inicio, se interpreta como que cruza la medianoche (ej: 22:00 a 02:00).'),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\RedZones\Schemas;

use App\Filament\Support\PolygonMapPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class RedZoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la zona')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Ej.: Barrio X (sin cobertura)')
                            ->required()
                            ->maxLength(255),
                        Toggle::make('active')
                            ->label('Activa')
                            ->helperText('Si está apagada, no bloquea pedidos aunque el polígono siga guardado.')
                            ->default(true),
                    ]),

                Section::make('Polígono')
                    ->description('Dibujá en el mapa el área a la que NO se realizan envíos. Cualquier dirección que caiga dentro del polígono queda bloqueada para envío a domicilio, sin importar la distancia.')
                    ->icon(Heroicon::OutlinedMap)
                    ->schema([
                        PolygonMapPicker::make('polygon')
                            ->label('')
                            ->default([])
                            ->required()
                            ->rules(['array', 'min:3'])
                            ->validationMessages([
                                'min' => 'Dibujá un polígono de al menos 3 puntos.',
                            ]),
                    ]),
            ]);
    }
}

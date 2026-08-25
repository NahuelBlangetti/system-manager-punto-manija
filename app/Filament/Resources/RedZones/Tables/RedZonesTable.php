<?php

namespace App\Filament\Resources\RedZones\Tables;

use App\Filament\Support\BulkActionHelpers;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RedZonesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('points_count')
                    ->label('Puntos')
                    ->state(fn ($record) => count($record->polygon ?? [])),
                IconColumn::make('active')
                    ->label('Activa')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('Activa'),
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

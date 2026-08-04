<?php

namespace App\Filament\Resources\Promotions\Tables;

use App\Enums\PromotionType;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PromotionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Todas'),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('percentage_value')
                    ->label('Valor')
                    ->formatStateUsing(fn ($state, $record) => $record->type === PromotionType::Percentage ? $state.'%' : '—'),
                TextColumn::make('days_of_week')
                    ->label('Días')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return 'Todos';
                        }
                        $labels = [0 => 'Dom', 1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb'];

                        return collect($state)->map(fn ($d) => $labels[$d] ?? $d)->join(', ');
                    }),
                TextColumn::make('start_time')
                    ->label('Horario')
                    ->formatStateUsing(fn ($state, $record) => $state && $record->end_time
                        ? substr($state, 0, 5).' – '.substr($record->end_time, 0, 5)
                        : 'Todo el día'),
                IconColumn::make('active')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

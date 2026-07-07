<?php

namespace App\Filament\Resources\Suppliers\Tables;

use App\Filament\Support\BulkActionHelpers;
use App\Filament\Support\ProductPdfExport;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_person')
                    ->label('Contacto'),
                TextColumn::make('phone')
                    ->label('Teléfono'),
                TextColumn::make('payment_terms')
                    ->label('Condición de pago')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'contado' => 'Contado',
                        '15_dias' => '15 días',
                        '30_dias' => '30 días',
                        '60_dias' => '60 días',
                        'consignacion' => 'Consignación',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'contado' => 'success',
                        '15_dias', '30_dias' => 'info',
                        '60_dias' => 'warning',
                        'consignacion' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('products_count')
                    ->label('Productos')
                    ->counts('products'),
                IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('Activo'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('exportarProductos')
                    ->label('Exportar productos')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->visible(fn ($record) => $record->products()->exists())
                    ->action(fn ($record) => ProductPdfExport::download($record->products()->with('category')->get(), 'proveedor')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkActionHelpers::safeDelete(),
                ]),
            ]);
    }
}

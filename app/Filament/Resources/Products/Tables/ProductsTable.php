<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductDiscountType;
use App\Filament\Resources\Products\Actions\PrintLabelAction;
use App\Filament\Support\BulkActionHelpers;
use App\Filament\Support\ProductPdfExport;
use App\Models\Product;
use App\Models\User;
use App\Services\Stock\ComboStockService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Imagen')
                    ->disk('public')
                    ->square()
                    ->size(48),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->badge()
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->visible(fn () => static::userCanManageProducts())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sale_price')
                    ->label('Precio venta')
                    ->formatStateUsing(fn ($state) => '$ '.number_format((float) $state, 2, ',', '.'))
                    ->sortable(),
                TextColumn::make('discount_min_qty')
                    ->label('Descuento por cantidad')
                    ->badge()
                    ->color('success')
                    ->toggleable()
                    ->state(fn (Product $record): ?string => $record->discount_min_qty
                        ? "{$record->discount_min_qty}+ ".($record->discount_type === ProductDiscountType::Percentage
                            ? "-{$record->discount_value}%"
                            : '-$'.number_format((float) $record->discount_value, 2, ',', '.'))
                        : null)
                    ->placeholder('—'),
                TextColumn::make('cost_price')
                    ->label('Precio costo')
                    ->formatStateUsing(fn ($state) => '$ '.number_format((float) $state, 2, ',', '.'))
                    ->visible(fn () => static::userCanManageProducts())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->getStateUsing(fn (Product $record): int => ComboStockService::availableStock($record))
                    ->color(fn (int $state, $record): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= $record->min_stock => 'warning',
                        default => 'success',
                    }),
                IconColumn::make('is_combo')
                    ->label('Combo')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->label('Proveedor')
                    ->placeholder('Todos los proveedores')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => static::userCanManageProducts()),
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Categoría')
                    ->placeholder('Todas las categorías')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_combo')
                    ->label('Combo')
                    ->placeholder('Todos')
                    ->trueLabel('Solo combos')
                    ->falseLabel('Solo productos simples'),
                Filter::make('incomplete')
                    ->label('Sin completar')
                    ->toggle()
                    ->query(function (Builder $query): Builder {
                        return $query->where(function (Builder $query): void {
                            $query->whereNull('image')
                                ->orWhere('image', '')
                                ->orWhereNull('sku')
                                ->orWhere('sku', '')
                                ->orWhere('cost_price', '<=', 0);

                            if (static::userCanManageProducts()) {
                                $query->orWhereNull('supplier_id');
                            }
                        });
                    }),
                TrashedFilter::make()
                    ->visible(fn () => static::userIsAdmin()),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns([
                'default' => 1,
                'sm' => 2,
                'lg' => 3,
            ])
            ->deferFilters(false)
            ->hiddenFilterIndicators()
            ->recordActions([
                PrintLabelAction::make(),
                EditAction::make()
                    ->visible(fn () => static::userCanManageProducts()),
            ])
            ->toolbarActions([
                BulkAction::make('exportarPdf')
                    ->label('Exportar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->schema([
                        Radio::make('tipo')
                            ->label('Tipo de exportación')
                            ->options(function (): array {
                                $options = [
                                    'cliente' => 'Cliente (catálogo con imagen y precio de venta)',
                                ];

                                // Quien gestiona productos puede exportar la lista con costos.
                                if (static::userCanManageProducts()) {
                                    $options['proveedor'] = 'Proveedor (lista con stock y precio de costo)';
                                }

                                return $options;
                            })
                            ->default('cliente')
                            ->required(),
                    ])
                    ->action(fn (Collection $records, array $data) => ProductPdfExport::download($records->loadMissing('supplier', 'category'), $data['tipo']))
                    ->deselectRecordsAfterCompletion(),
                PrintLabelAction::bulk(),
                BulkActionGroup::make([
                    BulkActionHelpers::safeDelete(),
                    BulkActionHelpers::safeForceDelete(),
                    BulkActionHelpers::safeRestore(),
                ]),
            ]);
    }

    protected static function userCanManageProducts(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->canManageProducts();
    }

    protected static function userIsAdmin(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isAdmin();
    }
}

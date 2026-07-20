<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductDiscountType;
use App\Filament\Support\ProductImagePath;
use App\Filament\Support\ProductUnitNormalizer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tipo de producto')
                    ->description('Empezá eligiendo si es un producto suelto o un combo armado.')
                    ->icon(Heroicon::OutlinedSquares2x2)
                    ->schema([
                        ToggleButtons::make('is_combo')
                            ->label('¿Qué estás cargando?')
                            ->options([
                                0 => 'Producto individual',
                                1 => 'Combo / promo',
                            ])
                            ->icons([
                                0 => Heroicon::OutlinedCube,
                                1 => Heroicon::OutlinedGift,
                            ])
                            ->colors([
                                0 => 'gray',
                                1 => 'warning',
                            ])
                            ->grouped()
                            ->inline()
                            ->live()
                            ->default(0)
                            ->dehydrated()
                            ->afterStateHydrated(function (ToggleButtons $component, $state): void {
                                $component->state((int) (bool) $state);
                            })
                            ->dehydrateStateUsing(fn ($state): bool => (bool) $state)
                            ->columnSpanFull(),
                    ]),

                Section::make('Información general')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('category_id')
                            ->label('Categoría')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nombre')
                                    ->required(),
                            ]),
                        Select::make('supplier_id')
                            ->label('Proveedor')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nombre')
                                    ->required(),
                            ]),
                        Toggle::make('active')
                            ->label('Activo')
                            ->default(true),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
                        FileUpload::make('image')
                            ->label('Imagen')
                            ->image()
                            ->disk('public')
                            ->directory(ProductImagePath::DIRECTORY)
                            ->visibility('public')
                            ->maxSize(5120)
                            ->imagePreviewHeight('200')
                            ->fetchFileInformation(false)
                            ->getUploadedFileUsing(function (FileUpload $component, string $file, string|array|null $storedFileNames): ?array {
                                $normalized = ProductImagePath::normalize($file);

                                if (blank($normalized) || ! ProductImagePath::exists($normalized)) {
                                    return null;
                                }

                                $previewUrl = ProductImagePath::adminPreviewUrl($normalized);

                                if (blank($previewUrl)) {
                                    return null;
                                }

                                return [
                                    'name' => ($component->isMultiple() ? ($storedFileNames[$file] ?? null) : $storedFileNames)
                                        ?? basename(parse_url($normalized, PHP_URL_PATH) ?: $normalized),
                                    'size' => 1,
                                    'type' => 'image/jpeg',
                                    'url' => $previewUrl,
                                ];
                            })
                            ->deleteUploadedFileUsing(function (FileUpload $component, string $file): void {
                                if (ProductImagePath::isRemote($file)) {
                                    return;
                                }

                                $normalized = ProductImagePath::normalize($file);

                                if ($normalized) {
                                    $component->getDisk()->delete($normalized);
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Armá el combo')
                    ->description('Sumá los productos que se descuentan del stock al vender este combo.')
                    ->icon(Heroicon::OutlinedGift)
                    ->visible(fn (Get $get): bool => (bool) $get('is_combo'))
                    ->schema([
                        Callout::make('El combo no tiene stock propio')
                            ->description('Al venderlo se restan las cantidades de cada producto de abajo. El stock disponible en ventas es el máximo de combos que se pueden armar.')
                            ->info()
                            ->icon(Heroicon::OutlinedInformationCircle),
                        Repeater::make('comboItems')
                            ->label('Productos incluidos')
                            ->relationship()
                            ->table([
                                TableColumn::make('Producto')
                                    ->markAsRequired(),
                                TableColumn::make('Cantidad')
                                    ->width('8rem')
                                    ->alignment(Alignment::Center)
                                    ->markAsRequired(),
                            ])
                            ->compact()
                            ->schema([
                                Select::make('component_product_id')
                                    ->label('Producto')
                                    ->relationship(
                                        name: 'component',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn (Builder $query) => $query->where('is_combo', false),
                                    )
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->barcode
                                        ? "{$record->name} ({$record->barcode})"
                                        : $record->name)
                                    ->searchable(['name', 'sku', 'barcode'])
                                    ->preload()
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->integer()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),
                            ])
                            ->addActionLabel('Agregar producto')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Identificadores')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(100),
                        TextInput::make('barcode')
                            ->label('Código de barras')
                            ->maxLength(100),
                        TextInput::make('imei')
                            ->label('IMEI')
                            ->maxLength(20),
                    ]),

                Section::make('Precios y stock')
                    ->columns(2)
                    ->schema([
                        TextInput::make('cost_price')
                            ->label('Precio de costo')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('$'),
                        TextInput::make('sale_price')
                            ->label('Precio de venta')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->prefix('$'),
                        Callout::make('Stock calculado automáticamente')
                            ->description('No hace falta cargar stock ni stock mínimo: se toma de los productos del combo.')
                            ->info()
                            ->visible(fn (Get $get): bool => (bool) $get('is_combo'))
                            ->columnSpanFull(),
                        TextInput::make('stock')
                            ->label('Stock actual')
                            ->required(fn (Get $get): bool => ! $get('is_combo'))
                            ->integer()
                            ->default(0)
                            ->minValue(0)
                            ->hidden(fn (Get $get): bool => (bool) $get('is_combo'))
                            ->dehydrated(fn (Get $get): bool => ! $get('is_combo')),
                        TextInput::make('min_stock')
                            ->label('Stock mínimo')
                            ->required(fn (Get $get): bool => ! $get('is_combo'))
                            ->integer()
                            ->default(0)
                            ->minValue(0)
                            ->hidden(fn (Get $get): bool => (bool) $get('is_combo'))
                            ->dehydrated(fn (Get $get): bool => ! $get('is_combo')),
                        Select::make('unit')
                            ->label('Unidad')
                            ->options(array_combine(ProductUnitNormalizer::WHITELIST, ProductUnitNormalizer::WHITELIST))
                            ->default('unidad')
                            ->required(),
                    ]),

                Section::make('Descuento por cantidad')
                    ->description('Aplicá un descuento automático en la venta de mostrador cuando se vende una cantidad mínima de este producto. Dejá "Cantidad mínima" vacío para no aplicar descuento.')
                    ->icon(Heroicon::OutlinedTag)
                    ->columns(3)
                    ->schema([
                        TextInput::make('discount_min_qty')
                            ->label('Cantidad mínima')
                            ->integer()
                            ->minValue(2)
                            ->placeholder('Ej: 5')
                            ->live(),
                        Select::make('discount_type')
                            ->label('Tipo de descuento')
                            ->options(ProductDiscountType::class)
                            ->native(false)
                            ->required(fn (Get $get): bool => filled($get('discount_min_qty')))
                            ->visible(fn (Get $get): bool => filled($get('discount_min_qty')))
                            ->dehydrated(fn (Get $get): bool => filled($get('discount_min_qty'))),
                        TextInput::make('discount_value')
                            ->label('Valor del descuento')
                            ->numeric()
                            ->minValue(0)
                            ->prefix(fn (Get $get): ?string => $get('discount_type') === ProductDiscountType::Fixed->value ? '$' : null)
                            ->suffix(fn (Get $get): ?string => $get('discount_type') === ProductDiscountType::Percentage->value ? '%' : null)
                            ->required(fn (Get $get): bool => filled($get('discount_min_qty')))
                            ->visible(fn (Get $get): bool => filled($get('discount_min_qty')))
                            ->dehydrated(fn (Get $get): bool => filled($get('discount_min_qty'))),
                    ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeComboStock(array $data): array
    {
        if (! empty($data['is_combo'])) {
            $data['stock'] = 0;
            $data['min_stock'] = 0;
        }

        $data['is_combo'] = (bool) ($data['is_combo'] ?? false);

        return $data;
    }
}

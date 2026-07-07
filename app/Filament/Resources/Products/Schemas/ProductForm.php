<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\ProductImagePath;
use App\Filament\Support\ProductUnitNormalizer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                        TextInput::make('stock')
                            ->label('Stock actual')
                            ->required()
                            ->integer()
                            ->default(0)
                            ->minValue(0),
                        TextInput::make('min_stock')
                            ->label('Stock mínimo')
                            ->required()
                            ->integer()
                            ->default(0)
                            ->minValue(0),
                        Select::make('unit')
                            ->label('Unidad')
                            ->options(array_combine(ProductUnitNormalizer::WHITELIST, ProductUnitNormalizer::WHITELIST))
                            ->default('unidad')
                            ->required(),
                    ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\WebOrders;

use App\Filament\Resources\WebOrders\Pages\EditWebOrder;
use App\Filament\Resources\WebOrders\Pages\ListWebOrders;
use App\Filament\Resources\WebOrders\Schemas\WebOrderForm;
use App\Filament\Resources\WebOrders\Tables\WebOrdersTable;
use App\Models\WebOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WebOrderResource extends Resource
{
    protected static ?string $model = WebOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Pedidos Web';

    protected static ?string $modelLabel = 'pedido web';

    protected static ?string $pluralModelLabel = 'pedidos web';

    protected static string|\UnitEnum|null $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return WebOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebOrders::route('/'),
            'edit' => EditWebOrder::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}

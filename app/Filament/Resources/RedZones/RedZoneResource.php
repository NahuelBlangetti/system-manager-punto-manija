<?php

namespace App\Filament\Resources\RedZones;

use App\Filament\Resources\RedZones\Pages\CreateRedZone;
use App\Filament\Resources\RedZones\Pages\EditRedZone;
use App\Filament\Resources\RedZones\Pages\ListRedZones;
use App\Filament\Resources\RedZones\Schemas\RedZoneForm;
use App\Filament\Resources\RedZones\Tables\RedZonesTable;
use App\Models\RedZone;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class RedZoneResource extends Resource
{
    protected static ?string $model = RedZone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static ?string $navigationLabel = 'Zonas sin envío';

    protected static ?string $modelLabel = 'zona sin envío';

    protected static ?string $pluralModelLabel = 'zonas sin envío';

    protected static string|UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return RedZoneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RedZonesTable::configure($table);
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
            'index' => ListRedZones::route('/'),
            'create' => CreateRedZone::route('/create'),
            'edit' => EditRedZone::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}

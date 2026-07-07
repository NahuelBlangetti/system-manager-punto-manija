<?php

namespace App\Filament\Resources\CashRegisters;

use App\Filament\Resources\CashRegisters\Pages\CreateCashRegister;
use App\Filament\Resources\CashRegisters\Pages\EditCashRegister;
use App\Filament\Resources\CashRegisters\Pages\ListCashRegisters;
use App\Filament\Resources\CashRegisters\Schemas\CashRegisterForm;
use App\Filament\Resources\CashRegisters\Tables\CashRegistersTable;
use App\Models\CashRegister;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CashRegisterResource extends Resource
{
    protected static ?string $model = CashRegister::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Caja';

    protected static ?string $modelLabel = 'caja';

    protected static ?string $pluralModelLabel = 'cajas';

    protected static string|\UnitEnum|null $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CashRegisterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashRegistersTable::configure($table);
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
            'index' => ListCashRegisters::route('/'),
            'create' => CreateCashRegister::route('/create'),
            'edit' => EditCashRegister::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // El empleado ve la caja del turno en curso (abierta, sin importar quién
        // la abrió) y, además, el historial de los turnos que él mismo cerró.
        $user = Auth::user();
        if ($user instanceof User && $user->isEmpleado()) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('status', 'open')
                    ->orWhere('user_id', $user->id);
            });
        }

        return $query;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        // El empleado puede operar el turno en curso (caja abierta) o sus propias cajas.
        return $user->isAdmin()
            || $record->getAttribute('status') === 'open'
            || $record->getAttribute('user_id') === $user->id;
    }

    public static function canDelete(Model $record): bool
    {
        return static::userIsAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return static::userIsAdmin();
    }

    protected static function userIsAdmin(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isAdmin();
    }
}

<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('role')
                    ->label('Rol')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $role) => [$role->value => $role->getLabel()]))
                    ->default(UserRole::Empleado->value)
                    ->live()
                    ->required()
                    ->helperText(fn (Get $get): ?string => match ($get('role')) {
                        UserRole::Delivery->value => 'Solo puede ver y gestionar Pedidos Web (confirmar / entregar envíos).',
                        UserRole::Empleado->value => 'Acceso a ventas, caja y catálogo (productos solo si se habilita abajo).',
                        UserRole::Admin->value => 'Acceso total al panel.',
                        default => null,
                    }),
                Toggle::make('can_manage_products')
                    ->label('Puede gestionar productos')
                    ->helperText('Permite al empleado crear, editar y cargar productos (incluida la importación por PDF). Los administradores ya tienen este acceso.')
                    ->visible(fn (Get $get): bool => $get('role') === UserRole::Empleado->value)
                    ->dehydrateStateUsing(fn (?bool $state, Get $get): bool => $get('role') === UserRole::Empleado->value
                        ? (bool) $state
                        : false)
                    ->default(false)
                    ->columnSpanFull(),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->helperText('Dejá vacío para mantener la contraseña actual al editar.')
                    ->maxLength(255),
            ]);
    }
}

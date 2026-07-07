<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@puntomanija.com'],
            [
                'name' => 'Administrador',
                'password' => 'Puntomanija789',
                'role' => UserRole::Admin,
            ],
        );

        User::updateOrCreate(
            ['email' => 'empleado@puntomanija.com'],
            [
                'name' => 'Empleado',
                'password' => 'password',
                'role' => UserRole::Empleado,
            ],
        );

        $this->call(ProductSeeder::class);
    }
}

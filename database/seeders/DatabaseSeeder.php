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
        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@puntomanija.com',
            'password' => 'Puntomanija789',
            'role' => UserRole::Admin,
        ]);

        User::factory()->create([
            'name' => 'Empleado',
            'email' => 'empleado@puntomanija.com',
            'role' => UserRole::Empleado,
        ]);

        $this->call(ProductSeeder::class);
    }
}

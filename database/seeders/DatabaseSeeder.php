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
                'password' => 'Empleadomanija',
                'role' => UserRole::Empleado,
            ],
        );

        User::updateOrCreate(
            ['email' => 'delivery@puntomanija.com'],
            [
                'name' => 'Delivery',
                'password' => 'Deliverymanija',
                'role' => UserRole::Delivery,
                'can_manage_products' => false,
            ],
        );

        $this->call(ProductSeeder::class);
    }
}

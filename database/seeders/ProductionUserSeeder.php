<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ProductionUserSeeder extends Seeder
{
    /**
     * Crear el usuario administrador de producción.
     * Usa firstOrCreate para ser idempotente.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
            ]
        );

        if (!$admin->hasRole('Admin')) {
            $admin->assignRole($adminRole);
        }
    }
}

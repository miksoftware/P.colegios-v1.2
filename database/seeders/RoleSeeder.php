<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'ver dashboard',
            'gestionar colegios', // Crear, editar
            'gestionar usuarios', // Crear, editar, eliminar, asignar roles
            'gestionar roles',    // Crear, editar, eliminar roles
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Admin Role and assign all permissions
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Create Rector Role (example)
        $rectorRole = Role::firstOrCreate(['name' => 'Rector']);
        $rectorRole->givePermissionTo(['ver dashboard', 'gestionar usuarios']);

        // Create Auxiliar Role (example)
        $auxRole = Role::firstOrCreate(['name' => 'Auxiliar']);
        $auxRole->givePermissionTo(['ver dashboard']);
    }
}

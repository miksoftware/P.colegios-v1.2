<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BudgetModificationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'budget_modifications.view' => 'Ver adiciones y reducciones presupuestales',
            'budget_modifications.create' => 'Crear adiciones y reducciones presupuestales',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        // Assign to Admin
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(array_keys($permissions));
        }

        // Assign to Rector
        $rectorRole = Role::where('name', 'Rector')->first();
        if ($rectorRole) {
            $rectorRole->givePermissionTo(array_keys($permissions));
        }

        // Auxiliar: solo lectura
        $auxRole = Role::where('name', 'Auxiliar')->first();
        if ($auxRole) {
            $auxRole->givePermissionTo('budget_modifications.view');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

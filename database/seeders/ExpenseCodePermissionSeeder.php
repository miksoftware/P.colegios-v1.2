<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ExpenseCodePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::firstOrCreate(
            ['name' => 'expense_codes'],
            [
                'display_name' => 'Códigos de Gasto',
                'icon' => 'tag',
                'order' => 35,
            ]
        );

        $permissions = [
            'expense_codes.view' => 'Ver códigos de gasto',
            'expense_codes.create' => 'Crear códigos de gasto',
            'expense_codes.edit' => 'Editar códigos de gasto',
            'expense_codes.delete' => 'Eliminar códigos de gasto',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['display_name' => $displayName, 'module_id' => $module->id]
            );
        }

        $adminRole = \Spatie\Permission\Models\Role::findByName('Admin');
        $adminRole->givePermissionTo(array_keys($permissions));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

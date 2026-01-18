<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ExpensePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::firstOrCreate(
            ['name' => 'expenses'],
            [
                'display_name' => 'Gastos',
                'icon' => 'credit-card',
                'order' => 65,
            ]
        );

        $permissions = [
            'expenses.view' => 'Ver gastos',
            'expenses.distribute' => 'Distribuir presupuesto de gastos',
            'expenses.execute' => 'Ejecutar gastos',
            'expenses.delete' => 'Eliminar distribuciones/ejecuciones',
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

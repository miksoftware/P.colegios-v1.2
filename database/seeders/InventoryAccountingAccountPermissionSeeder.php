<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class InventoryAccountingAccountPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear módulo
        $module = Module::firstOrCreate(
            ['name' => 'inventories'],
            [
                'display_name' => 'Inventarios',
                'icon' => 'cube',
                'order' => 60,
            ]
        );

        // Definir permisos
        $permissions = [
            'inventory_accounting_accounts.view' => 'Ver cuentas contables de inventario',
            'inventory_accounting_accounts.create' => 'Crear cuentas contables de inventario',
            'inventory_accounting_accounts.edit' => 'Editar cuentas contables de inventario',
            'inventory_accounting_accounts.delete' => 'Eliminar cuentas contables de inventario',
        ];

        // Crear permisos
        foreach ($permissions as $name => $displayName) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['display_name' => $displayName, 'module_id' => $module->id]
            );
        }

        // Asignar al Admin
        $adminRole = \Spatie\Permission\Models\Role::findByName('Admin');
        $adminRole->givePermissionTo(array_keys($permissions));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

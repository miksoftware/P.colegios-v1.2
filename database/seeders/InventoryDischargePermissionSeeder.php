<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class InventoryDischargePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Asegurar que el módulo exista
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
            'inventory_discharges.view' => 'Ver bajas de inventario',
            'inventory_discharges.create' => 'Crear bajas de inventario',
            'inventory_discharges.edit' => 'Editar bajas de inventario',
            'inventory_discharges.delete' => 'Eliminar bajas de inventario',
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

<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RetentionConfigPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::firstOrCreate(
            ['name' => 'retention_configs'],
            [
                'display_name' => 'Bases de Retenciones',
                'icon' => 'receipt-percent',
                'order' => 36,
            ]
        );

        $permissions = [
            'retention_configs.view'   => 'Ver configuraciones de retención',
            'retention_configs.create' => 'Crear configuraciones de retención',
            'retention_configs.edit'   => 'Editar configuraciones de retención',
            'retention_configs.delete' => 'Eliminar configuraciones de retención',
            'retention_configs.copy'   => 'Copiar configuraciones a otros colegios',
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

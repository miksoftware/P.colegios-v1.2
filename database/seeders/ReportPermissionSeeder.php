<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::firstOrCreate(
            ['name' => 'reports'],
            [
                'display_name' => 'Reportes',
                'icon' => 'chart-bar',
                'order' => 90,
            ]
        );

        $permissions = [
            'reports.view' => 'Ver reportes',
            'reports.export' => 'Exportar reportes',
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

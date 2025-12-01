<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ModulePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions FIRST
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Clean up old permissions that might cause conflicts
        $oldPermissions = [
            'ver dashboard',
            'gestionar colegios',
            'gestionar usuarios',
            'gestionar roles',
        ];
        Permission::whereIn('name', $oldPermissions)->delete();

        $modules = [
            [
                'name' => 'dashboard',
                'display_name' => 'Dashboard',
                'icon' => 'home',
                'order' => 1,
                'permissions' => [
                    ['name' => 'dashboard.view', 'display_name' => 'Ver Dashboard'],
                ],
            ],
            [
                'name' => 'schools',
                'display_name' => 'Colegios',
                'icon' => 'building-library',
                'order' => 2,
                'permissions' => [
                    ['name' => 'schools.view', 'display_name' => 'Ver Colegios'],
                    ['name' => 'schools.create', 'display_name' => 'Crear Colegios'],
                    ['name' => 'schools.edit', 'display_name' => 'Editar Colegios'],
                    ['name' => 'schools.delete', 'display_name' => 'Eliminar Colegios'],
                ],
            ],
            [
                'name' => 'school_info',
                'display_name' => 'Información del Colegio',
                'icon' => 'information-circle',
                'order' => 3,
                'permissions' => [
                    ['name' => 'school_info.view', 'display_name' => 'Ver Información del Colegio'],
                    ['name' => 'school_info.edit', 'display_name' => 'Editar Información del Colegio'],
                ],
            ],
            [
                'name' => 'users',
                'display_name' => 'Usuarios',
                'icon' => 'users',
                'order' => 4,
                'permissions' => [
                    ['name' => 'users.view', 'display_name' => 'Ver Usuarios'],
                    ['name' => 'users.create', 'display_name' => 'Crear Usuarios'],
                    ['name' => 'users.edit', 'display_name' => 'Editar Usuarios'],
                    ['name' => 'users.delete', 'display_name' => 'Eliminar Usuarios'],
                ],
            ],
            [
                'name' => 'roles',
                'display_name' => 'Roles',
                'icon' => 'shield-check',
                'order' => 5,
                'permissions' => [
                    ['name' => 'roles.view', 'display_name' => 'Ver Roles'],
                    ['name' => 'roles.create', 'display_name' => 'Crear Roles'],
                    ['name' => 'roles.edit', 'display_name' => 'Editar Roles'],
                    ['name' => 'roles.delete', 'display_name' => 'Eliminar Roles'],
                ],
            ],
            [
                'name' => 'accounting_accounts',
                'display_name' => 'Cuentas Contables',
                'icon' => 'calculator',
                'order' => 6,
                'permissions' => [
                    ['name' => 'accounting_accounts.view', 'display_name' => 'Ver Cuentas Contables'],
                    ['name' => 'accounting_accounts.create', 'display_name' => 'Crear Cuentas Contables'],
                    ['name' => 'accounting_accounts.edit', 'display_name' => 'Editar Cuentas Contables'],
                    ['name' => 'accounting_accounts.delete', 'display_name' => 'Eliminar Cuentas Contables'],
                ],
            ],
            [
                'name' => 'activity_logs',
                'display_name' => 'Registro de Actividad',
                'icon' => 'document-text',
                'order' => 7,
                'permissions' => [
                    ['name' => 'activity_logs.view', 'display_name' => 'Ver Registro de Actividad'],
                ],
            ],
        ];

        foreach ($modules as $moduleData) {
            $permissions = $moduleData['permissions'];
            unset($moduleData['permissions']);

            // Create or update module
            $module = Module::updateOrCreate(
                ['name' => $moduleData['name']],
                $moduleData
            );

            // Create permissions for this module
            foreach ($permissions as $permissionData) {
                Permission::updateOrCreate(
                    ['name' => $permissionData['name'], 'guard_name' => 'web'],
                    [
                        'display_name' => $permissionData['display_name'],
                        'module_id' => $module->id,
                    ]
                );
            }
        }

        // Clear cache again before assigning roles
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Get all current permissions
        $allPermissions = Permission::all();

        // Create Admin Role and assign ALL permissions
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions($allPermissions);

        // Create Rector Role
        $rectorRole = Role::firstOrCreate(['name' => 'Rector', 'guard_name' => 'web']);
        $rectorRole->syncPermissions([
            'dashboard.view',
            'school_info.view',
            'school_info.edit',
            'users.view',
            'users.create',
            'users.edit',
            'accounting_accounts.view',
            'accounting_accounts.create',
            'accounting_accounts.edit',
        ]);

        // Create Auxiliar Role
        $auxRole = Role::firstOrCreate(['name' => 'Auxiliar', 'guard_name' => 'web']);
        $auxRole->syncPermissions([
            'dashboard.view',
            'school_info.view',
            'accounting_accounts.view',
        ]);

        // Clear cache one final time
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

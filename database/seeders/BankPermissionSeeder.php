<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class BankPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'banks'],
            [
                'display_name' => 'Bancos',
                'icon' => 'credit-card',
                'order' => 9,
            ]
        );

        $permissions = [
            'banks.view' => 'Ver Bancos',
            'banks.create' => 'Crear Bancos',
            'banks.edit' => 'Editar Bancos',
            'banks.delete' => 'Eliminar Bancos',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [
                    'display_name' => $displayName,
                    'module_id' => $module->id,
                ]
            );
        }

        // Asignar permisos al rol Admin si existe
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(array_keys($permissions));
        }

        // Asignar permisos al rol Contador si existe
        $contadorRole = Role::where('name', 'Contador')->first();
        if ($contadorRole) {
            $contadorRole->givePermissionTo(array_keys($permissions));
        }
    }
}

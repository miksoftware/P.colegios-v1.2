<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ContractualPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear módulo
        $module = Module::firstOrCreate(
            ['name' => 'contractual'],
            [
                'display_name' => 'Etapa Contractual',
                'icon' => 'clipboard-document-check',
                'order' => 65,
            ]
        );

        // Definir permisos
        $permissions = [
            'contractual.view'   => 'Ver contratos y RPs',
            'contractual.create' => 'Crear contratos y RPs',
            'contractual.edit'   => 'Editar contratos y RPs',
            'contractual.delete' => 'Eliminar contratos y RPs',
        ];

        // Crear permisos
        foreach ($permissions as $name => $displayName) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['display_name' => $displayName, 'module_id' => $module->id]
            );
        }

        // Asignar todos al Admin
        $adminRole = \Spatie\Permission\Models\Role::findByName('Admin');
        $adminRole->givePermissionTo(array_keys($permissions));

        // Asignar permisos operativos al Rector
        $rectorRole = \Spatie\Permission\Models\Role::where('name', 'Rector')->first();
        if ($rectorRole) {
            $rectorRole->givePermissionTo([
                'contractual.view',
                'contractual.create',
                'contractual.edit',
            ]);
        }

        // Asignar solo vista al Auxiliar
        $auxiliarRole = \Spatie\Permission\Models\Role::where('name', 'Auxiliar')->first();
        if ($auxiliarRole) {
            $auxiliarRole->givePermissionTo(['contractual.view']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

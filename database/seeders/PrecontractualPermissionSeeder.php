<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PrecontractualPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear módulo
        $module = Module::firstOrCreate(
            ['name' => 'precontractual'],
            [
                'display_name' => 'Etapa Precontractual',
                'icon' => 'document-text',
                'order' => 60,
            ]
        );

        // Definir permisos
        $permissions = [
            'precontractual.view' => 'Ver convocatorias y CDPs',
            'precontractual.create' => 'Crear convocatorias y CDPs',
            'precontractual.edit' => 'Editar convocatorias y CDPs',
            'precontractual.delete' => 'Eliminar convocatorias y CDPs',
            'precontractual.evaluate' => 'Evaluar propuestas',
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
                'precontractual.view',
                'precontractual.create',
                'precontractual.edit',
                'precontractual.evaluate',
            ]);
        }

        // Asignar solo vista al Auxiliar
        $auxiliarRole = \Spatie\Permission\Models\Role::where('name', 'Auxiliar')->first();
        if ($auxiliarRole) {
            $auxiliarRole->givePermissionTo(['precontractual.view']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

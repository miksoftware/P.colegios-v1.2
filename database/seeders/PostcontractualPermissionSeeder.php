<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PostcontractualPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::firstOrCreate(
            ['name' => 'postcontractual'],
            [
                'display_name' => 'Etapa Postcontractual',
                'icon' => 'banknotes',
                'order' => 70,
            ]
        );

        $permissions = [
            'postcontractual.view'    => 'Ver órdenes de pago',
            'postcontractual.create'  => 'Crear órdenes de pago',
            'postcontractual.edit'    => 'Editar órdenes de pago',
            'postcontractual.delete'  => 'Eliminar órdenes de pago',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['display_name' => $displayName, 'module_id' => $module->id]
            );
        }

        $adminRole = \Spatie\Permission\Models\Role::findByName('Admin');
        $adminRole->givePermissionTo(array_keys($permissions));

        $rectorRole = \Spatie\Permission\Models\Role::where('name', 'Rector')->first();
        if ($rectorRole) {
            $rectorRole->givePermissionTo([
                'postcontractual.view',
                'postcontractual.create',
                'postcontractual.edit',
            ]);
        }

        $auxiliarRole = \Spatie\Permission\Models\Role::where('name', 'Auxiliar')->first();
        if ($auxiliarRole) {
            $auxiliarRole->givePermissionTo(['postcontractual.view']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

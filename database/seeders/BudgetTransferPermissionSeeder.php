<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class BudgetTransferPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Usar el módulo de Presupuesto existente
        $module = Module::firstOrCreate(
            ['name' => 'budget'],
            [
                'display_name' => 'Presupuesto',
                'icon' => 'currency-dollar',
                'order' => 50,
            ]
        );

        $permissions = [
            'budget_transfers.view' => 'Ver traslados presupuestales',
            'budget_transfers.create' => 'Crear traslados presupuestales',
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

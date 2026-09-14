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
            'budget_transfers.edit' => 'Editar traslados presupuestales',
            'budget_transfers.delete' => 'Eliminar traslados presupuestales',
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

        // Asignar todos los permisos al rol Admin si existe
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(array_keys($permissions));
        }

        // Asignar solo ver y crear al rol Contador si existe (edit y delete son exclusivos de Admin por defecto)
        $contadorRole = Role::where('name', 'Contador')->first();
        if ($contadorRole) {
            $contadorRole->givePermissionTo(['budget_transfers.view', 'budget_transfers.create']);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class IncomePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'incomes'],
            [
                'display_name' => 'Ingresos Reales',
                'icon' => 'currency-dollar', // Reusamos icono o cambiamos si hay uno mejor
                'order' => 55, // Después de Presupuesto
            ]
        );

        $permissions = [
            'incomes.view' => 'Ver ingresos reales',
            'incomes.create' => 'Registrar ingresos',
            'incomes.edit' => 'Editar ingresos',
            'incomes.delete' => 'Eliminar ingresos',
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

        foreach (['Admin', 'Contador'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo(array_keys($permissions));
            }
        }
    }
}

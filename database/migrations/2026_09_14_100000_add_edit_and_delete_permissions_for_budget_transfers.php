<?php

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Limpiar caché de permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Módulo de Presupuesto
        $module = Module::firstOrCreate(
            ['name' => 'budget'],
            [
                'display_name' => 'Presupuesto',
                'icon' => 'currency-dollar',
                'order' => 50,
            ]
        );

        $permissions = [
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

        // Asignar exclusivamente al rol Admin por defecto
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(array_keys($permissions));
        }

        // Limpiar caché nuevamente para que surta efecto inmediato
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'budget_transfers.edit',
            'budget_transfers.delete',
        ];

        foreach ($permissions as $name) {
            $permission = Permission::where('name', $name)->first();
            if ($permission) {
                $permission->delete();
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};

<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class NewsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $module = Module::firstOrCreate(
            ['name' => 'news'],
            [
                'display_name' => 'Noticias',
                'icon' => 'newspaper',
                'order' => 90,
            ]
        );

        $permissions = [
            'news.view'   => 'Ver noticias',
            'news.create' => 'Publicar noticias',
            'news.edit'   => 'Editar noticias',
            'news.delete' => 'Eliminar noticias',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                [
                    'display_name' => $displayName,
                    'module_id'    => $module->id,
                ]
            );
        }

        // Admin can do everything
        $admin = Role::where('name', 'Admin')->first();
        if ($admin) {
            $admin->givePermissionTo(array_keys($permissions));
        }

        // All other roles can only view news
        $viewerRoles = ['Contador', 'Tesorero', 'Rector', 'Auxiliar'];
        foreach ($viewerRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo('news.view');
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure Admin role exists
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $rectorRole = Role::firstOrCreate(['name' => 'Rector', 'guard_name' => 'web']);

        // Create admin user (can access any school)
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole($adminRole);

        // Get all schools
        $schools = School::all();

        // Create normal users for each school
        if ($schools->count() > 0) {
            // User for first school
            $user1 = User::create([
                'name' => 'Edgar Galvis',
                'email' => 'edgar@sanjuan.edu.co',
                'password' => Hash::make('password'),
            ]);
            $user1->schools()->attach($schools[0]->id);
            $user1->assignRole($rectorRole);

            // User for second school
            if ($schools->count() > 1) {
                $user2 = User::create([
                    'name' => 'María Rodríguez',
                    'email' => 'maria@santamaria.edu.co',
                    'password' => Hash::make('password'),
                ]);
                $user2->schools()->attach($schools[1]->id);
                $user2->assignRole($rectorRole);
            }

            // User for third school
            if ($schools->count() > 2) {
                $user3 = User::create([
                    'name' => 'Juan Martínez',
                    'email' => 'juan@iti.edu.co',
                    'password' => Hash::make('password'),
                ]);
                $user3->schools()->attach($schools[2]->id);
                $user3->assignRole($rectorRole);
            }
        }
    }
}

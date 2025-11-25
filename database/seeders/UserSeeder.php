<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user (can access any school)
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@admin.com',
            'is_admin' => true,
            'password' => Hash::make('password'),
        ]);

        // Get all schools
        $schools = School::all();

        // Create normal users for each school
        if ($schools->count() > 0) {
            // User for first school
            $user1 = User::create([
                'name' => 'Edgar Galvis',
                'email' => 'edgar@sanjuan.edu.co',
                'is_admin' => false,
                'password' => Hash::make('password'),
            ]);
            $user1->schools()->attach($schools[0]->id);

            // User for second school
            if ($schools->count() > 1) {
                $user2 = User::create([
                    'name' => 'María Rodríguez',
                    'email' => 'maria@santamaria.edu.co',
                    'is_admin' => false,
                    'password' => Hash::make('password'),
                ]);
                $user2->schools()->attach($schools[1]->id);
            }

            // User for third school
            if ($schools->count() > 2) {
                $user3 = User::create([
                    'name' => 'Juan Martínez',
                    'email' => 'juan@iti.edu.co',
                    'is_admin' => false,
                    'password' => Hash::make('password'),
                ]);
                $user3->schools()->attach($schools[2]->id);
            }
        }
    }
}

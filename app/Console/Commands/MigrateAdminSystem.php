<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MigrateAdminSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-admin-system 
                            {--fresh : Drop all tables and re-run all migrations}
                            {--force : Force the operation to run in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates from is_admin column to Spatie Roles system with modular permissions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->components->warn('⚠️  IMPORTANTE: Se recomienda hacer un backup de la base de datos antes de continuar.');
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('¿Deseas continuar con la migración del sistema de administración?')) {
            $this->components->info('Operación cancelada.');
            return Command::SUCCESS;
        }

        $this->newLine();

        // Check if this is a fresh install
        if ($this->option('fresh')) {
            return $this->handleFreshInstall();
        }

        return $this->handleMigration();
    }

    /**
     * Handle fresh install - runs all migrations and seeders
     */
    protected function handleFreshInstall(): int
    {
        $this->components->task('Ejecutando migrate:fresh', function () {
            Artisan::call('migrate:fresh', ['--force' => true]);
        });

        $this->components->task('Ejecutando ModulePermissionSeeder', function () {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\ModulePermissionSeeder',
                '--force' => true,
            ]);
        });

        $this->components->task('Ejecutando seeders principales', function () {
            Artisan::call('db:seed', ['--force' => true]);
        });

        $this->components->task('Limpiando caché de permisos', function () {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        });

        $this->newLine();
        $this->components->info('✅ Instalación fresca completada exitosamente.');

        return Command::SUCCESS;
    }

    /**
     * Handle migration from existing is_admin system
     */
    protected function handleMigration(): int
    {
        // Step 1: Run pending migrations (modules, permissions FK)
        $this->components->task('Paso 1/5: Ejecutando migraciones de módulos y permisos', function () {
            // Only run specific migrations, not the is_admin removal yet
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2025_11_29_010000_create_modules_table.php',
                '--force' => true,
            ]);
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2025_11_29_010001_add_module_to_permissions_table.php',
                '--force' => true,
            ]);
        });

        // Step 2: Run ModulePermissionSeeder to create roles and permissions
        $this->components->task('Paso 2/5: Creando módulos y permisos granulares', function () {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\ModulePermissionSeeder',
                '--force' => true,
            ]);
        });

        // Step 3: Migrate users with is_admin=true to Admin role
        $this->components->task('Paso 3/5: Migrando usuarios is_admin a rol Admin', function () {
            $this->migrateAdminUsers();
        });

        // Step 4: Remove is_admin column
        $this->components->task('Paso 4/5: Eliminando columna is_admin', function () {
            if (Schema::hasColumn('users', 'is_admin')) {
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/2025_11_29_010003_remove_is_admin_from_users_table.php',
                    '--force' => true,
                ]);
            }
        });

        // Step 5: Clear permission cache
        $this->components->task('Paso 5/5: Limpiando caché de permisos', function () {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
        });

        $this->newLine();
        $this->displaySummary();

        return Command::SUCCESS;
    }

    /**
     * Migrate users with is_admin=true to Admin role
     */
    protected function migrateAdminUsers(): void
    {
        if (!Schema::hasColumn('users', 'is_admin')) {
            return;
        }

        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        
        $adminUsers = User::where('is_admin', true)->get();
        
        foreach ($adminUsers as $user) {
            if (!$user->hasRole('Admin')) {
                $user->assignRole($adminRole);
            }
        }
    }

    /**
     * Display migration summary
     */
    protected function displaySummary(): void
    {
        $this->components->info('✅ Migración completada exitosamente.');
        
        $this->newLine();
        $this->components->twoColumnDetail('<fg=green>Usuarios con rol Admin</>', User::role('Admin')->count());
        $this->components->twoColumnDetail('<fg=blue>Total de permisos</>', \App\Models\Permission::count());
        $this->components->twoColumnDetail('<fg=blue>Total de módulos</>', \App\Models\Module::count());
        $this->components->twoColumnDetail('<fg=blue>Total de roles</>', Role::count());
        
        $this->newLine();
        $this->line('  <fg=yellow>Recuerda actualizar tu código para usar:</> ');
        $this->line('    • <fg=cyan>$user->hasRole(\'Admin\')</> en lugar de <fg=red>$user->is_admin</>');
        $this->line('    • <fg=cyan>$user->can(\'schools.view\')</> para permisos granulares');
        $this->newLine();
    }
}

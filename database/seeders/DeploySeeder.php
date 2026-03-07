<?php

namespace Database\Seeders;

use App\Models\SeederLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DeploySeeder extends Seeder
{
    /**
     * Seeder maestro para deploys en producción.
     *
     * Todos los seeders aquí son IDEMPOTENTES (seguros de re-ejecutar).
     * Usan firstOrCreate/updateOrCreate, nunca truncan datos.
     *
     * RECOMENDADO: usar `php artisan db:seed-once` en lugar de este seeder
     * para tener control de ejecución tipo migraciones.
     *
     * Si se ejecuta directamente, usa el sistema de tracking automáticamente.
     *
     * Ejecutar: php artisan db:seed --class=DeploySeeder
     */
    public function run(): void
    {
        $hasTracking = Schema::hasTable('seeder_logs');
        $batch = $hasTracking ? SeederLog::nextBatch() : null;
        $executed = 0;
        $skipped = 0;

        $this->command->info('');
        $this->command->info('==========================================');
        $this->command->info('  🚀 Deploy Seeder - Datos del sistema');
        $this->command->info('==========================================');
        if ($hasTracking) {
            $this->command->info('  📋 Sistema de tracking activo (batch #' . $batch . ')');
        }
        $this->command->info('');

        $seeders = [
            // 1. Datos geográficos
            '[1/6] 🗺️  Departamentos y municipios...' => [
                DepartmentSeeder::class,
                MunicipalitySeeder::class,
            ],
            // 2. Roles y permisos base
            '[2/6] 🔐 Roles y permisos...' => [
                ModulePermissionSeeder::class,
            ],
            // 3. Permisos por módulo
            '[3/6] 📋 Permisos de módulos específicos...' => [
                BudgetPermissionSeeder::class,
                BudgetItemPermissionSeeder::class,
                BudgetTransferPermissionSeeder::class,
                BudgetModificationPermissionSeeder::class,
                FundingSourcePermissionSeeder::class,
                IncomePermissionSeeder::class,
                ExpensePermissionSeeder::class,
                ExpenseCodePermissionSeeder::class,
                PrecontractualPermissionSeeder::class,
                ContractualPermissionSeeder::class,
                PostcontractualPermissionSeeder::class,
                BankPermissionSeeder::class,
            ],
            // 4. Datos contables y presupuestales
            '[4/6] 💰 Rubros, fuentes y cuentas contables...' => [
                AccountingAccountSeeder::class,
                RubrosFuentesSeeder::class,
            ],
            // 5. Códigos de gasto
            '[5/6] 📄 Códigos de gasto...' => [
                ExpenseCodeSeeder::class,
            ],
            // 6. Usuario administrador
            '[6/6] 👤 Usuario administrador...' => [
                ProductionUserSeeder::class,
            ],
        ];

        foreach ($seeders as $label => $seederList) {
            $this->command->info($label);

            foreach ($seederList as $seederClass) {
                if ($hasTracking && SeederLog::hasRun($seederClass)) {
                    $this->command->line('  ⏭️  ' . class_basename($seederClass) . ' (ya ejecutado)');
                    $skipped++;
                    continue;
                }

                $this->call($seederClass);

                if ($hasTracking) {
                    SeederLog::log($seederClass, $batch);
                }
                $executed++;
            }
        }

        $this->command->info('');
        $this->command->info('==========================================');
        $this->command->info("  ✅ Deploy Seeder completado");
        $this->command->info("     Ejecutados: {$executed} | Omitidos: {$skipped}");
        $this->command->info('==========================================');
        $this->command->info('');
    }
}

<?php

namespace App\Console\Commands;

use App\Models\SeederLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SeedOnce extends Command
{
    protected $signature = 'db:seed-once
                            {--class= : Ejecutar un seeder específico}
                            {--force : Forzar ejecución en producción}
                            {--status : Mostrar estado de los seeders}
                            {--reset= : Marcar un seeder como no ejecutado para re-ejecutarlo}';

    protected $description = 'Ejecuta seeders de producción solo una vez, similar al sistema de migraciones';

    /**
     * Los seeders de producción en orden de ejecución.
     * Cada seeder se ejecuta solo una vez y queda registrado.
     */
    protected array $productionSeeders = [
        // 1. Datos geográficos
        \Database\Seeders\DepartmentSeeder::class,
        \Database\Seeders\MunicipalitySeeder::class,

        // 2. Roles y permisos base
        \Database\Seeders\ModulePermissionSeeder::class,

        // 3. Permisos por módulo
        \Database\Seeders\BudgetPermissionSeeder::class,
        \Database\Seeders\BudgetItemPermissionSeeder::class,
        \Database\Seeders\BudgetTransferPermissionSeeder::class,
        \Database\Seeders\BudgetModificationPermissionSeeder::class,
        \Database\Seeders\FundingSourcePermissionSeeder::class,
        \Database\Seeders\IncomePermissionSeeder::class,
        \Database\Seeders\ExpensePermissionSeeder::class,
        \Database\Seeders\ExpenseCodePermissionSeeder::class,
        \Database\Seeders\PrecontractualPermissionSeeder::class,
        \Database\Seeders\ContractualPermissionSeeder::class,
        \Database\Seeders\PostcontractualPermissionSeeder::class,
        \Database\Seeders\BankPermissionSeeder::class,
        \Database\Seeders\ReportPermissionSeeder::class,
        \Database\Seeders\NewsPermissionSeeder::class,

        // 3.5 Permisos de inventario
        \Database\Seeders\InventoryAccountingAccountPermissionSeeder::class,
        \Database\Seeders\InventoryItemPermissionSeeder::class,
        \Database\Seeders\InventoryEntryPermissionSeeder::class,
        \Database\Seeders\InventoryDischargePermissionSeeder::class,

        // 4. Datos contables y presupuestales
        \Database\Seeders\AccountingAccountSeeder::class,
        \Database\Seeders\ExpenseAccountingAccountSeeder::class,
        \Database\Seeders\RubrosFuentesSeeder::class,
        \Database\Seeders\RefreshExpenseCodesSeeder::class,

        // 4.5 Catálogo contable de inventario
        \Database\Seeders\InventoryAccountingAccountSeeder::class,

        // 5. Usuario administrador de producción
        \Database\Seeders\ProductionUserSeeder::class,
    ];

    public function handle(): int
    {
        if (!Schema::hasTable('seeder_logs')) {
            $this->components->error('La tabla seeder_logs no existe. Ejecuta las migraciones primero: php artisan migrate');
            return Command::FAILURE;
        }

        if ($this->option('status')) {
            return $this->showStatus();
        }

        if ($resetSeeder = $this->option('reset')) {
            return $this->resetSeeder($resetSeeder);
        }

        if ($specificClass = $this->option('class')) {
            return $this->runSpecificSeeder($specificClass);
        }

        return $this->runPendingSeeders();
    }

    /**
     * Ejecutar todos los seeders pendientes.
     */
    protected function runPendingSeeders(): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->components->error('Usa --force para ejecutar seeders en producción.');
            return Command::FAILURE;
        }

        $pending = $this->getPendingSeeders();

        if (empty($pending)) {
            $this->components->info('No hay seeders pendientes por ejecutar.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->components->info('Seeders pendientes: ' . count($pending));
        $this->newLine();

        $batch = SeederLog::nextBatch();
        $executed = 0;

        foreach ($pending as $seederClass) {
            $shortName = class_basename($seederClass);

            try {
                $this->components->task($shortName, function () use ($seederClass, $batch) {
                    $seeder = app()->make($seederClass);
                    $seeder->setCommand($this);
                    $seeder->__invoke();

                    SeederLog::log($seederClass, $batch);
                });
                $executed++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->components->error("Error ejecutando {$shortName}: {$e->getMessage()}");
                $this->newLine();
                $this->components->warn("Se ejecutaron {$executed} seeders antes del error. Los ya ejecutados no se volverán a correr.");
                return Command::FAILURE;
            }
        }

        $this->newLine();
        $this->components->info("Se ejecutaron {$executed} seeders en batch #{$batch}.");

        return Command::SUCCESS;
    }

    /**
     * Ejecutar un seeder específico.
     */
    protected function runSpecificSeeder(string $class): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->components->error('Usa --force para ejecutar seeders en producción.');
            return Command::FAILURE;
        }

        // Resolver el nombre completo de la clase
        $seederClass = str_contains($class, '\\') ? $class : "Database\\Seeders\\{$class}";

        if (!class_exists($seederClass)) {
            $this->components->error("El seeder {$seederClass} no existe.");
            return Command::FAILURE;
        }

        if (SeederLog::hasRun($seederClass)) {
            $this->components->warn("{$class} ya fue ejecutado.");

            if (!$this->confirm('¿Deseas ejecutarlo de nuevo?')) {
                return Command::SUCCESS;
            }
        }

        $batch = SeederLog::nextBatch();
        $shortName = class_basename($seederClass);

        try {
            $this->components->task($shortName, function () use ($seederClass, $batch) {
                $seeder = app()->make($seederClass);
                $seeder->setCommand($this);
                $seeder->__invoke();

                if (!SeederLog::hasRun($seederClass)) {
                    SeederLog::log($seederClass, $batch);
                }
            });
        } catch (\Throwable $e) {
            $this->components->error("Error: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $this->newLine();
        return Command::SUCCESS;
    }

    /**
     * Mostrar el estado de los seeders.
     */
    protected function showStatus(): int
    {
        $this->newLine();
        $this->components->info('Estado de seeders de producción');
        $this->newLine();

        $rows = [];
        foreach ($this->productionSeeders as $seederClass) {
            $log = SeederLog::where('seeder', $seederClass)->first();
            $rows[] = [
                class_basename($seederClass),
                $log ? "<fg=green>Ejecutado</>" : "<fg=yellow>Pendiente</>",
                $log ? $log->batch : '-',
                $log ? $log->executed_at->format('Y-m-d H:i:s') : '-',
            ];
        }

        $this->table(
            ['Seeder', 'Estado', 'Batch', 'Ejecutado en'],
            $rows
        );

        $this->newLine();
        return Command::SUCCESS;
    }

    /**
     * Resetear un seeder para que pueda volver a ejecutarse.
     */
    protected function resetSeeder(string $class): int
    {
        $seederClass = str_contains($class, '\\') ? $class : "Database\\Seeders\\{$class}";

        $deleted = SeederLog::where('seeder', $seederClass)->delete();

        if ($deleted) {
            $this->components->info("{$class} marcado como pendiente. Se ejecutará en el próximo db:seed-once.");
        } else {
            $this->components->warn("{$class} no estaba registrado como ejecutado.");
        }

        return Command::SUCCESS;
    }

    /**
     * Obtener seeders que no han sido ejecutados.
     */
    protected function getPendingSeeders(): array
    {
        return array_filter($this->productionSeeders, function ($seederClass) {
            return !SeederLog::hasRun($seederClass);
        });
    }
}

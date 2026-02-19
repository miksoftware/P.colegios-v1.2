<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DeploySeeder extends Seeder
{
    /**
     * Seeder maestro para deploys en producción.
     *
     * Todos los seeders aquí son IDEMPOTENTES (seguros de re-ejecutar).
     * Usan firstOrCreate/updateOrCreate, nunca truncan datos.
     *
     * Ejecutar: php artisan db:seed --class=DeploySeeder
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('==========================================');
        $this->command->info('  🚀 Deploy Seeder - Datos del sistema');
        $this->command->info('==========================================');
        $this->command->info('');

        // ── 1. Datos geográficos (departamentos y municipios) ──
        $this->command->info('[1/5] 🗺️  Departamentos y municipios...');
        $this->call(DepartmentSeeder::class);
        $this->call(MunicipalitySeeder::class);

        // ── 2. Roles y permisos base ──
        $this->command->info('[2/5] 🔐 Roles y permisos...');
        $this->call(ModulePermissionSeeder::class);

        // ── 3. Permisos por módulo ──
        $this->command->info('[3/5] 📋 Permisos de módulos específicos...');
        $this->call(BudgetPermissionSeeder::class);
        $this->call(BudgetTransferPermissionSeeder::class);
        $this->call(FundingSourcePermissionSeeder::class);
        $this->call(IncomePermissionSeeder::class);
        $this->call(ExpensePermissionSeeder::class);
        $this->call(ExpenseCodePermissionSeeder::class);
        $this->call(PrecontractualPermissionSeeder::class);
        $this->call(ContractualPermissionSeeder::class);
        $this->call(PostcontractualPermissionSeeder::class);
        $this->call(BankPermissionSeeder::class);

        // ── 4. Datos contables y presupuestales (rubros, fuentes, cuentas) ──
        $this->command->info('[4/5] 💰 Rubros, fuentes y cuentas contables...');
        $this->call(AccountingAccountSeeder::class);
        $this->call(RubrosFuentesSeeder::class);

        // ── 5. Códigos de gasto ──
        $this->command->info('[5/5] 📄 Códigos de gasto...');
        $this->call(ExpenseCodeSeeder::class);

        $this->command->info('');
        $this->command->info('==========================================');
        $this->command->info('  ✅ Deploy Seeder completado');
        $this->command->info('==========================================');
        $this->command->info('');
    }
}

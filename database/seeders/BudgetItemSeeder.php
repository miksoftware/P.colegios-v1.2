<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use App\Models\BudgetItem;
use App\Models\School;
use Illuminate\Database\Seeder;

class BudgetItemSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener el primer colegio
        $school = School::first();
        if (!$school) {
            $this->command->warn('No hay colegios. Ejecuta SchoolSeeder primero.');
            return;
        }

        // Obtener cuentas auxiliares (nivel 5)
        $auxiliaryAccounts = AccountingAccount::where('level', 5)
            ->where('allows_movement', true)
            ->where('is_active', true)
            ->get();

        if ($auxiliaryAccounts->isEmpty()) {
            $this->command->warn('No hay cuentas auxiliares. Ejecuta AccountingAccountSeeder primero.');
            return;
        }

        // Rubros de ejemplo por tipo de cuenta
        $budgetItems = [
            // Ingresos
            ['code' => 'ING-001', 'name' => 'Matrículas', 'description' => 'Ingresos por concepto de matrículas'],
            ['code' => 'ING-002', 'name' => 'Pensiones', 'description' => 'Ingresos por pensiones mensuales'],
            ['code' => 'ING-003', 'name' => 'Certificados', 'description' => 'Ingresos por expedición de certificados'],
            ['code' => 'ING-004', 'name' => 'Otros Cobros Periódicos', 'description' => 'Otros ingresos periódicos'],
            ['code' => 'ING-005', 'name' => 'Transferencias SGP', 'description' => 'Transferencias del Sistema General de Participaciones'],
            
            // Gastos de personal
            ['code' => 'GAS-001', 'name' => 'Salarios Docentes', 'description' => 'Pago de salarios a docentes'],
            ['code' => 'GAS-002', 'name' => 'Salarios Administrativos', 'description' => 'Pago de salarios personal administrativo'],
            ['code' => 'GAS-003', 'name' => 'Prestaciones Sociales', 'description' => 'Pago de prestaciones sociales'],
            ['code' => 'GAS-004', 'name' => 'Aportes Parafiscales', 'description' => 'Aportes a seguridad social y parafiscales'],
            
            // Gastos generales
            ['code' => 'GAS-010', 'name' => 'Servicios Públicos', 'description' => 'Pago de servicios públicos'],
            ['code' => 'GAS-011', 'name' => 'Mantenimiento', 'description' => 'Gastos de mantenimiento de instalaciones'],
            ['code' => 'GAS-012', 'name' => 'Materiales y Suministros', 'description' => 'Compra de materiales y suministros'],
            ['code' => 'GAS-013', 'name' => 'Aseo y Cafetería', 'description' => 'Servicios de aseo y cafetería'],
            ['code' => 'GAS-014', 'name' => 'Vigilancia', 'description' => 'Servicios de vigilancia'],
            ['code' => 'GAS-015', 'name' => 'Comunicaciones', 'description' => 'Gastos de comunicaciones y telefonía'],
            
            // Inversión
            ['code' => 'INV-001', 'name' => 'Equipos de Cómputo', 'description' => 'Adquisición de equipos de cómputo'],
            ['code' => 'INV-002', 'name' => 'Mobiliario', 'description' => 'Adquisición de mobiliario escolar'],
            ['code' => 'INV-003', 'name' => 'Material Didáctico', 'description' => 'Adquisición de material didáctico'],
        ];

        $accountIndex = 0;
        $accountCount = $auxiliaryAccounts->count();

        foreach ($budgetItems as $item) {
            // Rotar entre las cuentas auxiliares disponibles
            $account = $auxiliaryAccounts[$accountIndex % $accountCount];
            
            BudgetItem::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'code' => $item['code'],
                ],
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'accounting_account_id' => $account->id,
                    'is_active' => true,
                ]
            );

            $accountIndex++;
        }

        $this->command->info('Rubros presupuestales creados exitosamente.');
    }
}

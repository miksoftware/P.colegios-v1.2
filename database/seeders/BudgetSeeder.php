<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\BudgetModification;
use App\Models\School;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();
        
        if (!$school) {
            $this->command->warn('No hay colegios. Ejecuta primero el seeder de colegios.');
            return;
        }

        $budgetItems = BudgetItem::forSchool($school->id)->active()->get();

        if ($budgetItems->isEmpty()) {
            $this->command->warn('No hay rubros presupuestales. Ejecuta primero BudgetItemSeeder.');
            return;
        }

        $fiscalYear = (int) date('Y');

        // Crear presupuestos de ejemplo
        $budgetsData = [
            ['type' => 'income', 'initial_amount' => 50000000],
            ['type' => 'income', 'initial_amount' => 30000000],
            ['type' => 'expense', 'initial_amount' => 15000000],
            ['type' => 'expense', 'initial_amount' => 10000000],
            ['type' => 'expense', 'initial_amount' => 8000000],
        ];

        foreach ($budgetItems->take(5) as $index => $budgetItem) {
            if (!isset($budgetsData[$index])) break;

            $data = $budgetsData[$index];
            
            $budget = Budget::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'budget_item_id' => $budgetItem->id,
                    'fiscal_year' => $fiscalYear,
                ],
                [
                    'type' => $data['type'],
                    'initial_amount' => $data['initial_amount'],
                    'current_amount' => $data['initial_amount'],
                    'description' => "Presupuesto {$fiscalYear} - {$budgetItem->name}",
                    'is_active' => true,
                ]
            );

            // Agregar algunas modificaciones de ejemplo
            if ($index < 3 && $budget->modifications()->count() === 0) {
                $modifications = [
                    [
                        'type' => 'addition',
                        'amount' => rand(1000000, 5000000),
                        'reason' => 'Adición presupuestal por recursos adicionales aprobados',
                    ],
                    [
                        'type' => 'reduction',
                        'amount' => rand(500000, 2000000),
                        'reason' => 'Reducción por traslado a otro rubro',
                    ],
                ];

                $currentAmount = $budget->initial_amount;
                $modNumber = 1;

                foreach ($modifications as $mod) {
                    $previousAmount = $currentAmount;
                    $newAmount = $mod['type'] === 'addition' 
                        ? $currentAmount + $mod['amount'] 
                        : $currentAmount - $mod['amount'];

                    BudgetModification::create([
                        'budget_id' => $budget->id,
                        'modification_number' => $modNumber++,
                        'type' => $mod['type'],
                        'amount' => $mod['amount'],
                        'previous_amount' => $previousAmount,
                        'new_amount' => $newAmount,
                        'reason' => $mod['reason'],
                        'document_number' => 'RES-' . str_pad(rand(1, 100), 3, '0', STR_PAD_LEFT),
                        'document_date' => now()->subDays(rand(1, 30)),
                        'created_by' => 1,
                    ]);

                    $currentAmount = $newAmount;
                }

                $budget->update(['current_amount' => $currentAmount]);
            }
        }

        $this->command->info('Presupuestos de ejemplo creados correctamente.');
    }
}

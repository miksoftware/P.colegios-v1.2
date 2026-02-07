<?php

namespace Database\Seeders;

use App\Models\BudgetItem;
use App\Models\FundingSource;
use Illuminate\Database\Seeder;

class FundingSourceSeeder extends Seeder
{
    /**
     * Fuentes de financiación estándar según el Ministerio de Educación Nacional de Colombia
     * Se crean globalmente para cada rubro presupuestal
     */
    protected array $standardSources = [
        [
            'code' => '1',
            'name' => 'Recursos Propios',
            'type' => 'rp',
            'description' => 'Ingresos generados directamente por la institución educativa',
        ],
        [
            'code' => '2',
            'name' => 'SGP - Sistema General de Participaciones',
            'type' => 'sgp',
            'description' => 'Recursos transferidos por el Ministerio de Educación Nacional',
        ],
        [
            'code' => '33',
            'name' => 'RB RP - Recursos de Balance (Propios)',
            'type' => 'rb',
            'description' => 'Superávit fiscal de años anteriores proveniente de Recursos Propios',
        ],
        [
            'code' => '34',
            'name' => 'RB SGP - Recursos de Balance (SGP)',
            'type' => 'rb',
            'description' => 'Superávit fiscal de años anteriores proveniente de SGP',
        ],
    ];

    public function run(): void
    {
        // Obtener todos los rubros presupuestales (ahora globales)
        $budgetItems = BudgetItem::all();

        foreach ($budgetItems as $budgetItem) {
            foreach ($this->standardSources as $source) {
                FundingSource::firstOrCreate(
                    [
                        'budget_item_id' => $budgetItem->id,
                        'code' => $source['code'],
                    ],
                    [
                        'name' => $source['name'],
                        'type' => $source['type'],
                        'description' => $source['description'],
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info("Fuentes de financiación globales creadas para {$budgetItems->count()} rubros.");
    }
}

<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use App\Models\BudgetItem;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $schoolId = 1;

        // Obtener algunas cuentas auxiliares para vincular rubros
        $auxiliaryAccounts = AccountingAccount::where('level', 5)
            ->where('allows_movement', true)
            ->take(10)
            ->get();

        // Rubros de prueba
        $budgetItems = [
            ['code' => 'ING-001', 'name' => 'INGRESOS POR MATRÍCULAS', 'description' => 'Ingresos por concepto de matrículas'],
            ['code' => 'ING-002', 'name' => 'INGRESOS POR CERTIFICADOS', 'description' => 'Ingresos por expedición de certificados'],
            ['code' => 'ING-003', 'name' => 'INGRESOS POR ARRIENDOS', 'description' => 'Ingresos por arrendamiento de espacios'],
            ['code' => 'ING-004', 'name' => 'TRANSFERENCIAS SGP', 'description' => 'Transferencias del Sistema General de Participaciones'],
            ['code' => 'GAS-001', 'name' => 'GASTOS DE PERSONAL', 'description' => 'Gastos relacionados con personal'],
            ['code' => 'GAS-002', 'name' => 'MATERIALES Y SUMINISTROS', 'description' => 'Compra de materiales y suministros'],
            ['code' => 'GAS-003', 'name' => 'SERVICIOS PÚBLICOS', 'description' => 'Pago de servicios públicos'],
            ['code' => 'GAS-004', 'name' => 'MANTENIMIENTO', 'description' => 'Gastos de mantenimiento de infraestructura'],
            ['code' => 'GAS-005', 'name' => 'EQUIPOS Y TECNOLOGÍA', 'description' => 'Adquisición de equipos tecnológicos'],
        ];

        foreach ($budgetItems as $index => $item) {
            $accountId = $auxiliaryAccounts[$index % count($auxiliaryAccounts)]->id ?? null;
            
            BudgetItem::updateOrCreate(
                ['code' => $item['code']],
                [
                    'accounting_account_id' => $accountId,
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'is_active' => true,
                ]
            );
        }

        // Proveedores de prueba
        $suppliers = [
            [
                'document_type' => 'NIT',
                'document_number' => '900123456',
                'first_name' => 'PAPELERÍA EL ESTUDIANTE',
                'first_surname' => 'S.A.S',
                'person_type' => 'juridica',
                'tax_regime' => 'comun',
                'address' => 'Calle 10 # 20-30',
                'email' => 'ventas@papeleriaestudiante.com',
                'phone' => '6012345678',
            ],
            [
                'document_type' => 'NIT',
                'document_number' => '800987654',
                'first_name' => 'TECNOLOGÍA EDUCATIVA',
                'first_surname' => 'LTDA',
                'person_type' => 'juridica',
                'tax_regime' => 'comun',
                'address' => 'Carrera 15 # 45-67',
                'email' => 'info@tecneducativa.com',
                'phone' => '6019876543',
            ],
            [
                'document_type' => 'NIT',
                'document_number' => '901234567',
                'first_name' => 'SERVICIOS GENERALES DEL CARIBE',
                'first_surname' => 'S.A',
                'person_type' => 'juridica',
                'tax_regime' => 'gran_contribuyente',
                'address' => 'Avenida Principal # 100-50',
                'email' => 'contacto@servigeneralcaribe.com',
                'phone' => '6051234567',
            ],
            [
                'document_type' => 'CC',
                'document_number' => '12345678',
                'first_name' => 'JUAN',
                'second_name' => 'CARLOS',
                'first_surname' => 'PÉREZ',
                'second_surname' => 'GÓMEZ',
                'person_type' => 'natural',
                'tax_regime' => 'simplificado',
                'address' => 'Calle 5 # 10-15',
                'email' => 'juanperez@email.com',
                'phone' => '3001234567',
            ],
            [
                'document_type' => 'CC',
                'document_number' => '87654321',
                'first_name' => 'MARÍA',
                'second_name' => 'ELENA',
                'first_surname' => 'RODRÍGUEZ',
                'second_surname' => 'LÓPEZ',
                'person_type' => 'natural',
                'tax_regime' => 'no_responsable',
                'address' => 'Carrera 8 # 25-30',
                'email' => 'mariarodriguez@email.com',
                'phone' => '3109876543',
            ],
            [
                'document_type' => 'NIT',
                'document_number' => '860001234',
                'first_name' => 'FERRETERÍA LA CONSTRUCCIÓN',
                'first_surname' => 'S.A.S',
                'person_type' => 'juridica',
                'tax_regime' => 'comun',
                'address' => 'Diagonal 50 # 30-40',
                'email' => 'ventas@ferreconstruccion.com',
                'phone' => '6014567890',
            ],
        ];

        foreach ($suppliers as $supplier) {
            $dv = null;
            if ($supplier['document_type'] === 'NIT') {
                $dv = Supplier::calculateDv($supplier['document_number']);
            }

            Supplier::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'document_type' => $supplier['document_type'],
                    'document_number' => $supplier['document_number'],
                ],
                array_merge($supplier, [
                    'school_id' => $schoolId,
                    'dv' => $dv,
                    'is_active' => true,
                ])
            );
        }

        // Fuentes de financiación asociadas a los rubros
        $fundingSources = [
            // Fuentes para rubros de ingresos
            ['budget_item_code' => 'ING-001', 'code' => '1', 'name' => 'Recursos Propios - Matrículas', 'type' => 'rp'],
            ['budget_item_code' => 'ING-002', 'code' => '1', 'name' => 'Recursos Propios - Certificados', 'type' => 'rp'],
            ['budget_item_code' => 'ING-003', 'code' => '1', 'name' => 'Recursos Propios - Arriendos', 'type' => 'rp'],
            ['budget_item_code' => 'ING-004', 'code' => '2', 'name' => 'SGP - Transferencias', 'type' => 'sgp'],
            ['budget_item_code' => 'ING-004', 'code' => '2B', 'name' => 'SGP - Calidad Educativa', 'type' => 'sgp'],
            
            // Fuentes para rubros de gastos
            ['budget_item_code' => 'GAS-001', 'code' => '1', 'name' => 'Recursos Propios - Personal', 'type' => 'rp'],
            ['budget_item_code' => 'GAS-001', 'code' => '2', 'name' => 'SGP - Personal', 'type' => 'sgp'],
            ['budget_item_code' => 'GAS-002', 'code' => '1', 'name' => 'Recursos Propios - Materiales', 'type' => 'rp'],
            ['budget_item_code' => 'GAS-002', 'code' => '2', 'name' => 'SGP - Materiales', 'type' => 'sgp'],
            ['budget_item_code' => 'GAS-003', 'code' => '1', 'name' => 'Recursos Propios - Servicios', 'type' => 'rp'],
            ['budget_item_code' => 'GAS-004', 'code' => '1', 'name' => 'Recursos Propios - Mantenimiento', 'type' => 'rp'],
            ['budget_item_code' => 'GAS-004', 'code' => '2', 'name' => 'SGP - Mantenimiento', 'type' => 'sgp'],
            ['budget_item_code' => 'GAS-005', 'code' => '1', 'name' => 'Recursos Propios - Tecnología', 'type' => 'rp'],
            ['budget_item_code' => 'GAS-005', 'code' => '2', 'name' => 'SGP - Tecnología', 'type' => 'sgp'],
        ];

        foreach ($fundingSources as $fs) {
            $budgetItem = BudgetItem::where('code', $fs['budget_item_code'])
                ->first();

            if ($budgetItem) {
                \App\Models\FundingSource::updateOrCreate(
                    [
                        'budget_item_id' => $budgetItem->id,
                        'code' => $fs['code'],
                    ],
                    [
                        'name' => $fs['name'],
                        'type' => $fs['type'],
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('Datos de prueba creados: 9 rubros, 6 proveedores y 14 fuentes de financiación para el colegio ID 1');
    }
}

<?php

namespace App\Imports;

use App\Models\InventoryAccountingAccount;
use App\Models\InventoryEntry;
use App\Models\InventoryItem;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class InitialInventorySheetImport implements ToCollection, WithStartRow, WithChunkReading
{
    protected $schoolId;
    protected $entryId;
    
    // Cache de relaciones para evitar queries en loop
    protected $accountsCache = [];
    protected $suppliersCache = [];

    public function __construct($schoolId)
    {
        $this->schoolId = $schoolId;
        
        // Crear o buscar la Entrada Inicial
        $entry = InventoryEntry::firstOrCreate(
            [
                'school_id' => $schoolId,
                'observations' => 'Carga Inicial de Inventario por Excel',
            ],
            [
                'date' => now(),
                'total_value' => 0,
                'is_active' => true,
            ]
        );
        $this->entryId = $entry->id;
    }

    public function collection(Collection $rows)
    {
        $totalAddedValue = 0;

        foreach ($rows as $row) {
            // Validar que la fila tenga datos básicos
            if (!isset($row[0]) || !isset($row[1])) {
                continue;
            }

            $accountCode = trim($row[0]);
            $stateCheck  = mb_strtoupper(trim($row[3] ?? ''));

            // Saltar filas de encabezado, metadatos y totales de categoría:
            // - El código contable debe ser numérico/puntual (ej: 1.6.55.05)
            // - El estado debe ser B, R o M (artículos reales)
            if (!preg_match('/^\d[\d\.]+\d$/', $accountCode) || !in_array($stateCheck, ['B', 'R', 'M'])) {
                continue;
            }

            // 1. Obtener o crear Cuenta Contable
            if (!isset($this->accountsCache[$accountCode])) {
                $account = InventoryAccountingAccount::firstOrCreate(
                    ['code' => $accountCode],
                    [
                        'name' => 'Cuenta Autogenerada ' . $accountCode,
                        'depreciation_years' => $this->resolveDepreciationYears($accountCode),
                        'is_active' => true,
                    ]
                );
                $this->accountsCache[$accountCode] = $account->id;
            }
            $accountId = $this->accountsCache[$accountCode];

            // 2. Obtener o crear Proveedor
            $supplierNameRaw = isset($row[10]) && trim($row[10]) !== '' ? trim($row[10]) : 'PROVEEDOR DESCONOCIDO';
            $supplierName = mb_substr($supplierNameRaw, 0, 50);
            $supplierKey = mb_strtoupper($supplierName);

            if (!isset($this->suppliersCache[$supplierKey])) {
                // Buscar si existe por nombre o apellido
                $supplier = Supplier::where('school_id', $this->schoolId)
                    ->where(function($q) use ($supplierName) {
                        $q->where('first_name', 'like', "%{$supplierName}%")
                          ->orWhere('first_surname', 'like', "%{$supplierName}%");
                    })->first();

                if (!$supplier) {
                    // Si no existe, crearlo con un NIT aleatorio único
                    $supplier = Supplier::create([
                        'school_id' => $this->schoolId,
                        'first_surname' => $supplierName,
                        'document_type' => 'NIT',
                        // Se genera un NIT aleatorio que empiece por 999 para identificar que fue autogenerado
                        'document_number' => '999' . mt_rand(100000, 999999), 
                        'person_type' => 'juridica',
                        'address' => 'NO REGISTRA',
                        'is_active' => true,
                    ]);
                }

                $this->suppliersCache[$supplierKey] = $supplier->id;
            }
            $supplierId = $this->suppliersCache[$supplierKey];

            // 3. Mapeo de campos
            // Columnas (0-indexed, col A=0): A=Cód.Contable, B=Descripción, C=Calco Actual,
            // D=Estado, E=Valor Inicial, F=Depreciación, G=Acta Baja, H=Val.Baja, I=Saldo,
            // J=Fecha Adquisición, K=Proveedor, L=Procedencia Recursos, M=Sede Ubicación, N=Tipo Inventario
            $stateRaw = mb_strtoupper(trim($row[3] ?? 'B'));
            $state = match($stateRaw) {
                'R' => 'regular',
                'M' => 'malo',
                default => 'bueno',
            };

            $typeRaw = mb_strtoupper(trim($row[13] ?? 'DEVOLUTIVO'));
            $inventoryType = str_contains($typeRaw, 'CONSUMO') ? 'consumo' : 'devolutivo';

            // Valor inicial de compra (columna E = índice 4)
            $initialValue = isset($row[4]) && is_numeric($row[4]) ? (float) $row[4] : 0;
            
            $acquisitionDate = now();
            if (isset($row[9]) && is_numeric($row[9])) {
                try {
                    $acquisitionDate = Date::excelToDateTimeObject($row[9]);
                } catch (\Exception $e) {
                    // Ignorar fecha inválida
                }
            }

            // CALCO ACTUAL (placa) está en columna C = índice 2
            $currentTag = isset($row[2]) && trim($row[2]) !== '' && trim($row[2]) !== 'ND' ? trim($row[2]) : null;

            // 4. Crear Artículo
            InventoryItem::create([
                'school_id' => $this->schoolId,
                'inventory_accounting_account_id' => $accountId,
                'inventory_entry_id' => $this->entryId,
                'name' => mb_substr(trim($row[1]), 0, 255),
                'initial_value' => $initialValue,
                'acquisition_date' => $acquisitionDate,
                'supplier_id' => $supplierId,
                'state' => $state,
                'current_tag' => $currentTag,
                'location' => isset($row[12]) && trim($row[12]) !== '' ? mb_substr(trim($row[12]), 0, 100) : null,
                'funding_source' => isset($row[11]) && trim($row[11]) !== '' ? mb_substr(trim($row[11]), 0, 100) : null,
                'inventory_type' => $inventoryType,
                'is_active' => true,
            ]);

            $totalAddedValue += $initialValue;
        }

        // Actualizar el valor total de la entrada
        $entry = InventoryEntry::find($this->entryId);
        $entry->total_value += $totalAddedValue;
        $entry->save();
    }

    /**
     * Determina los años de depreciación correctos según el código contable.
     * Aplica las tablas de Contaduría General de la Nación colombiana.
     */
    private function resolveDepreciationYears(string $code): int
    {
        // Normalizar: quitar puntos para comparar con los códigos de referencia
        $n = str_replace('.', '', $code);

        // Códigos específicos que difieren del valor por defecto de su grupo
        $exact = [
            // Equipos de comunicación y computación (padre = 10 años)
            '167002' => 5,   // Equipo de computación
            '167090' => 5,   // Otros equipos de comunicación y computación
            // Intangibles
            '1970'   => 5,
            '197007' => 5,   // Licencias
            '197008' => 5,   // Software
            '197090' => 5,   // Otros intangibles
            // Sin depreciación
            '830617' => 0,
            '830618' => 0,
            '830690' => 0,
        ];

        if (isset($exact[$n])) {
            return $exact[$n];
        }

        // Prefijos (de más específico a más general)
        $prefixes = [
            '1610' => 5,    // Semovientes
            '1640' => 50,   // Edificaciones
            '1970' => 5,    // Intangibles
            '8306' => 0,    // Bienes en custodia
            // Grupos con 10 años (por defecto)
            '1655' => 10,   // Maquinaria y equipo
            '1665' => 10,   // Muebles y enseres
            '1670' => 10,   // Comunicación y computación
            '1675' => 10,   // Transporte
            '1680' => 10,   // Comedor y cocina
        ];

        foreach ($prefixes as $prefix => $years) {
            if (str_starts_with($n, $prefix)) {
                return $years;
            }
        }

        return 10; // Por defecto
    }

    public function startRow(): int
    {
        // Los encabezados de columna están en la fila 13 del formato AP-AI-RG-170.
        // Los datos reales comienzan en la fila 14.
        return 14;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}

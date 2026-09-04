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
            // Detectar automáticamente el desplazamiento de columnas:
            // Caso 1: Columna B (índice 1) es Código Contable y Columna E (índice 4) es Estado (Offset 1)
            // Caso 2: Columna A (índice 0) es Código Contable y Columna D (índice 3) es Estado (Offset 0)
            $colOffset = null;
            $acc1 = trim((string)($row[1] ?? ''));
            $st1  = mb_strtoupper(trim((string)($row[4] ?? '')));

            if (preg_match('/^\d[\d\.]+\d$/', $acc1) && in_array($st1, ['B', 'R', 'M'])) {
                $colOffset = 1;
            } else {
                $acc0 = trim((string)($row[0] ?? ''));
                $st0  = mb_strtoupper(trim((string)($row[3] ?? '')));
                if (preg_match('/^\d[\d\.]+\d$/', $acc0) && in_array($st0, ['B', 'R', 'M'])) {
                    $colOffset = 0;
                }
            }

            // Saltar filas que no sean artículos válidos (ej: encabezados en fila 13, títulos de categoría, subtotales)
            if ($colOffset === null) {
                continue;
            }

            $accountCode = trim((string)$row[0 + $colOffset]);
            $rawItemName = trim((string)($row[1 + $colOffset] ?? ''));
            if ($rawItemName === '') {
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
            $supplierNameRaw = isset($row[10 + $colOffset]) && trim((string)$row[10 + $colOffset]) !== '' 
                ? trim((string)$row[10 + $colOffset]) 
                : 'PROVEEDOR DESCONOCIDO';
            $supplierName = mb_substr($supplierNameRaw, 0, 50);
            $supplierKey = mb_strtoupper($supplierName);

            if (!isset($this->suppliersCache[$supplierKey])) {
                $supplier = Supplier::where('school_id', $this->schoolId)
                    ->where(function($q) use ($supplierName) {
                        $q->where('first_name', 'like', "%{$supplierName}%")
                          ->orWhere('first_surname', 'like', "%{$supplierName}%");
                    })->first();

                if (!$supplier) {
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
            $stateRaw = mb_strtoupper(trim((string)($row[3 + $colOffset] ?? 'B')));
            $state = match($stateRaw) {
                'R' => 'regular',
                'M' => 'malo',
                default => 'bueno',
            };

            $typeRaw = mb_strtoupper(trim((string)($row[13 + $colOffset] ?? 'DEVOLUTIVO')));
            $inventoryType = str_contains($typeRaw, 'CONSUMO') ? 'consumo' : 'devolutivo';

            // Valor inicial de compra
            $valRaw = $row[4 + $colOffset] ?? 0;
            $initialValue = is_numeric($valRaw) ? (float) $valRaw : 0;
            
            // Fecha de adquisición (número serial de Excel o fecha en texto)
            $acquisitionDate = now();
            $dateRaw = $row[9 + $colOffset] ?? null;
            if ($dateRaw && is_numeric($dateRaw)) {
                try {
                    $acquisitionDate = Date::excelToDateTimeObject($dateRaw);
                } catch (\Exception $e) {
                }
            } elseif ($dateRaw && !empty($dateRaw)) {
                try {
                    $acquisitionDate = \Carbon\Carbon::parse(str_replace('/', '-', $dateRaw));
                } catch (\Exception $e) {
                }
            }

            // CALCO ACTUAL / placa
            $currentTagRaw = isset($row[2 + $colOffset]) ? trim((string)$row[2 + $colOffset]) : '';
            $currentTag = ($currentTagRaw !== '' && $currentTagRaw !== 'ND') ? $currentTagRaw : null;

            // Ubicación y Fuente de Recursos
            $locationRaw = isset($row[12 + $colOffset]) && trim((string)$row[12 + $colOffset]) !== '' 
                ? mb_substr(trim((string)$row[12 + $colOffset]), 0, 100) 
                : null;

            $fundingRaw = isset($row[11 + $colOffset]) && trim((string)$row[11 + $colOffset]) !== '' 
                ? mb_substr(trim((string)$row[11 + $colOffset]), 0, 100) 
                : null;

            // 4. Crear Artículo
            InventoryItem::create([
                'school_id' => $this->schoolId,
                'inventory_accounting_account_id' => $accountId,
                'inventory_entry_id' => $this->entryId,
                'name' => mb_substr($rawItemName, 0, 255),
                'initial_value' => $initialValue,
                'acquisition_date' => $acquisitionDate,
                'supplier_id' => $supplierId,
                'state' => $state,
                'current_tag' => $currentTag,
                'location' => $locationRaw,
                'funding_source' => $fundingRaw,
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
        // El proceso inicia leyendo desde la fila 13 (la cabecera se salta automáticamente).
        return 13;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}

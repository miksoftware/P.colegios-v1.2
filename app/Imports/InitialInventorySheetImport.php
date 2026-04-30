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
            // Validar que la fila tenga datos básicos (al menos la descripción y el código)
            if (!isset($row[1]) || !isset($row[2])) {
                continue;
            }

            // 1. Obtener o crear Cuenta Contable
            $accountCode = trim($row[1]);
            if (!isset($this->accountsCache[$accountCode])) {
                $account = InventoryAccountingAccount::firstOrCreate(
                    ['code' => $accountCode],
                    [
                        'name' => 'Cuenta Autogenerada ' . $accountCode,
                        'depreciation_years' => 10,
                        'is_active' => true,
                    ]
                );
                $this->accountsCache[$accountCode] = $account->id;
            }
            $accountId = $this->accountsCache[$accountCode];

            // 2. Obtener o crear Proveedor
            $supplierNameRaw = isset($row[11]) && trim($row[11]) !== '' ? trim($row[11]) : 'PROVEEDOR DESCONOCIDO';
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
            $stateRaw = mb_strtoupper(trim($row[4] ?? 'B'));
            $state = match($stateRaw) {
                'R' => 'regular',
                'M' => 'malo',
                default => 'bueno',
            };

            $typeRaw = mb_strtoupper(trim($row[14] ?? 'DEVOLUTIVO'));
            $inventoryType = str_contains($typeRaw, 'CONSUMO') ? 'consumo' : 'devolutivo';

            $initialValue = isset($row[6]) ? (float) $row[6] : (isset($row[5]) ? (float) $row[5] : 0);
            
            $acquisitionDate = now();
            if (isset($row[10]) && is_numeric($row[10])) {
                try {
                    $acquisitionDate = Date::excelToDateTimeObject($row[10]);
                } catch (\Exception $e) {
                    // Ignorar fecha inválida
                }
            }

            $currentTag = isset($row[3]) && trim($row[3]) !== '' && trim($row[3]) !== 'ND' ? trim($row[3]) : null;

            // 4. Crear Artículo
            InventoryItem::create([
                'school_id' => $this->schoolId,
                'inventory_accounting_account_id' => $accountId,
                'inventory_entry_id' => $this->entryId,
                'name' => mb_substr(trim($row[2]), 0, 255),
                'initial_value' => $initialValue,
                'acquisition_date' => $acquisitionDate,
                'supplier_id' => $supplierId,
                'state' => $state,
                'current_tag' => $currentTag,
                'location' => isset($row[12]) ? mb_substr(trim($row[12]), 0, 100) : null,
                'funding_source' => isset($row[13]) ? mb_substr(trim($row[13]), 0, 100) : null,
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

    public function startRow(): int
    {
        return 5; // Comenzar en la fila 5 (asumiendo que las cabeceras son las primeras 4)
    }

    public function chunkSize(): int
    {
        return 500;
    }
}

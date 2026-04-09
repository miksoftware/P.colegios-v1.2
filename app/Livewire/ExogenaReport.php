<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\PaymentOrder;
use App\Models\School;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\Attributes\Layout;

class ExogenaReport extends Component
{
    public $schoolId;
    public $school;
    public $filterYear;

    // Datos del reporte
    public $rows = [];
    public $totals = [];
    public $supplierCount = 0;

    /**
     * Mapeo de concepto de retención a código de concepto exógena DIAN (Formato 1001).
     */
    const CONCEPT_MAP = [
        'compras'                  => ['code' => '5007', 'name' => 'Compra de activos movibles'],
        'servicios'                => ['code' => '5004', 'name' => 'Servicios'],
        'honorarios'               => ['code' => '5002', 'name' => 'Honorarios'],
        'arrendamiento_sitios_web' => ['code' => '5005', 'name' => 'Arrendamientos'],
        'arrendamiento_inmuebles'  => ['code' => '5005', 'name' => 'Arrendamientos'],
        'transporte_pasajeros'     => ['code' => '5004', 'name' => 'Servicios de transporte'],
    ];

    /**
     * Tipos de documento DIAN para exógena.
     */
    const DIAN_DOC_TYPES = [
        'CC'   => '13',
        'CE'   => '22',
        'NIT'  => '31',
        'TI'   => '12',
        'PA'   => '41',
        'RC'   => '11',
        'NUIP' => '13',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('reports.view'), 403);

        $this->schoolId = session('selected_school_id');
        if (!$this->schoolId) {
            session()->flash('error', 'Seleccione un colegio.');
            $this->redirect(route('dashboard'));
            return;
        }

        $this->school = School::find($this->schoolId);
        $this->filterYear = $this->school->current_validity ?? now()->year;
        $this->loadReport();
    }

    public function updatedFilterYear()
    {
        $this->loadReport();
    }

    public function loadReport()
    {
        $year = (int) $this->filterYear;

        // Obtener todas las órdenes de pago aprobadas/pagadas del colegio en la vigencia
        $paymentOrders = PaymentOrder::forSchool($this->schoolId)
            ->forYear($year)
            ->whereIn('status', ['approved', 'paid'])
            ->with([
                'contract.supplier.department',
                'contract.supplier.municipality',
            ])
            ->get();

        // Agrupar por proveedor + concepto de retención
        $grouped = [];

        foreach ($paymentOrders as $po) {
            $supplier = $po->contract?->supplier;
            if (!$supplier) continue;

            $concept = $po->retention_concept ?? 'servicios';
            $key = $supplier->id . '|' . $concept;

            if (!isset($grouped[$key])) {
                $dianDocType = self::DIAN_DOC_TYPES[$supplier->document_type] ?? '13';
                $conceptInfo = self::CONCEPT_MAP[$concept] ?? ['code' => '5016', 'name' => 'Otros costos y deducciones'];

                $grouped[$key] = [
                    'supplier_id' => $supplier->id,
                    'concept_code' => $conceptInfo['code'],
                    'concept_name' => $conceptInfo['name'],
                    'retention_concept' => $concept,
                    'dian_doc_type' => $dianDocType,
                    'document_number' => $supplier->document_number,
                    'dv' => $supplier->dv ?? '',
                    'first_surname' => $supplier->first_surname ?? '',
                    'second_surname' => $supplier->second_surname ?? '',
                    'first_name' => $supplier->first_name ?? '',
                    'second_name' => $supplier->second_name ?? '',
                    'person_type' => $supplier->person_type,
                    'full_name' => $supplier->full_name,
                    'address' => $supplier->address ?? '',
                    'department_code' => $supplier->department?->dian_code ?? '',
                    'municipality_code' => $supplier->municipality?->dian_code ?? '',
                    'department_name' => $supplier->department?->name ?? '',
                    'municipality_name' => $supplier->municipality?->name ?? '',
                    'email' => $supplier->email ?? '',
                    'country_code' => '169', // Colombia
                    'payment_deductible' => 0,
                    'payment_non_deductible' => 0,
                    'iva_greater_value' => 0,
                    'retefuente' => 0,
                    'reteiva' => 0,
                    'payment_count' => 0,
                ];
            }

            $subtotal = (float) $po->subtotal;
            $iva = (float) $po->iva;
            $retefuente = (float) $po->retefuente;
            $reteiva = (float) $po->reteiva;

            // Pago o abono en cuenta deducible = subtotal (base gravable)
            $grouped[$key]['payment_deductible'] += $subtotal;
            // IVA mayor valor del costo
            $grouped[$key]['iva_greater_value'] += $iva;
            // Retención en la fuente practicada
            $grouped[$key]['retefuente'] += $retefuente;
            // ReteIVA practicado
            $grouped[$key]['reteiva'] += $reteiva;
            $grouped[$key]['payment_count']++;
        }

        // Convertir a array indexado y ordenar
        $this->rows = collect($grouped)
            ->sortBy(['concept_code', 'full_name'])
            ->values()
            ->map(function ($row) {
                return array_merge($row, [
                    'payment_deductible' => round($row['payment_deductible'], 2),
                    'payment_non_deductible' => round($row['payment_non_deductible'], 2),
                    'iva_greater_value' => round($row['iva_greater_value'], 2),
                    'retefuente' => round($row['retefuente'], 2),
                    'reteiva' => round($row['reteiva'], 2),
                ]);
            })
            ->toArray();

        // Totales
        $c = collect($this->rows);
        $this->totals = [
            'payment_deductible' => $c->sum('payment_deductible'),
            'payment_non_deductible' => $c->sum('payment_non_deductible'),
            'iva_greater_value' => $c->sum('iva_greater_value'),
            'retefuente' => $c->sum('retefuente'),
            'reteiva' => $c->sum('reteiva'),
        ];

        $this->supplierCount = $c->unique('supplier_id')->count();

        $this->dispatch('reportLoaded');
    }

    public function getPeriodLabelProperty(): string
    {
        return "AÑO GRAVABLE {$this->filterYear}";
    }

    public function exportExcel()
    {
        if (!auth()->user()->can('reports.export')) {
            $this->dispatch('toast', message: 'No tienes permisos para exportar.', type: 'error');
            return;
        }
        $this->dispatch('export-exogena');
    }

    public function exportCsv()
    {
        if (!auth()->user()->can('reports.export')) {
            $this->dispatch('toast', message: 'No tienes permisos para exportar.', type: 'error');
            return;
        }
        $this->dispatch('export-exogena-csv');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.exogena-report');
    }
}

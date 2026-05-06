<?php

namespace App\Livewire;

use App\Models\PaymentOrder;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

class RetentionLiquidationReport extends Component
{
    public $schoolId;
    public $school;
    public $filterYear;
    public $filterMonth = '';

    public $reportData = [];
    public $grandTotals = [];

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

    public function updatedFilterMonth()
    {
        $this->loadReport();
    }

    public function loadReport()
    {
        $query = PaymentOrder::forSchool($this->schoolId)
            ->forYear((int) $this->filterYear)
            ->whereIn('status', ['approved', 'paid'])
            ->with([
                'contract.supplier',
                'contract.convocatoria.cdps.fundingSources.fundingSource',
                'contract.rps.fundingSources.fundingSource',
                'supplier',
                'cdp.fundingSources.fundingSource',
                'contractRp.fundingSources.fundingSource',
                'expenseLines',
            ]);

        if ($this->filterMonth) {
            $query->whereMonth('payment_date', (int) $this->filterMonth);
        }

        $paymentOrders = $query->get();

        // Group by funding source, then by retention concept and person type
        $grouped = [];

        foreach ($paymentOrders as $po) {
            $contract = $po->contract;
            $supplier = $po->resolved_supplier;
            $personType = $supplier?->person_type ?? 'natural'; // natural or juridica

            // Determine funding source name
            $fundingSourceName = $this->getFundingSourceName($contract);

            if (!isset($grouped[$fundingSourceName])) {
                $grouped[$fundingSourceName] = [
                    'servicios' => ['juridica_base' => 0, 'juridica_retention' => 0, 'natural_base' => 0, 'natural_retention' => 0],
                    'compras' => ['juridica_base' => 0, 'juridica_retention' => 0, 'natural_base' => 0, 'natural_retention' => 0],
                    'honorarios' => ['juridica_base' => 0, 'juridica_retention' => 0, 'natural_base' => 0, 'natural_retention' => 0],
                    'reteiva' => ['juridica_base' => 0, 'juridica_retention' => 0, 'natural_base' => 0, 'natural_retention' => 0],
                ];
            }

            // El sistema puede guardar retenciones de dos formas:
            // 1) A nivel PO (retefuente/reteiva) con retention_concept → pagos antiguos o single-mode
            // 2) A nivel expense_lines con retention_concept por línea → pagos multi-concepto
            // Recolectamos TODAS las retenciones para no perder datos.
            $retentions = []; // [['concept' => x, 'base' => y, 'retefuente' => z, 'reteiva' => w]]

            // Siempre incluir los agregados a nivel PO si tienen valor
            if ((float) $po->retefuente > 0 || (float) $po->reteiva > 0) {
                $retentions[] = [
                    'concept'    => $po->retention_concept,
                    'base'       => (float) $po->subtotal,
                    'retefuente' => (float) $po->retefuente,
                    'reteiva'    => (float) $po->reteiva,
                ];
            }

            // Recorrer expense_lines: si una línea tiene retención, usarla.
            // ATENCIÓN: si PO ya tiene retefuente/reteiva (caso 1) las líneas podrían duplicar;
            // pero en el modelo actual, cuando se usan líneas, el PO.retefuente es la SUMA
            // de las líneas (ver computeTotals). Por tanto, preferimos el dato del PO ya
            // agregado y descartamos las líneas cuando el PO tiene retención. Solo leemos
            // líneas si el PO NO tiene retención agregada (caso raro).
            if ((float) $po->retefuente == 0 && (float) $po->reteiva == 0) {
                foreach ($po->expenseLines as $line) {
                    if ((float) $line->retefuente > 0 || (float) $line->reteiva > 0) {
                        $retentions[] = [
                            'concept'    => $line->retention_concept,
                            'base'       => (float) $line->subtotal,
                            'retefuente' => (float) $line->retefuente,
                            'reteiva'    => (float) $line->reteiva,
                        ];
                    }
                }
            }

            foreach ($retentions as $ret) {
                $concept    = $ret['concept'];
                $subtotal   = $ret['base'];
                $retefuente = $ret['retefuente'];
                $reteiva    = $ret['reteiva'];

                $reportConcept = match ($concept) {
                    'compras' => 'compras',
                    'honorarios' => 'honorarios',
                    'servicios', 'arrendamiento_sitios_web', 'arrendamiento_inmuebles', 'transporte_pasajeros' => 'servicios',
                    default => null,
                };

                if ($reportConcept && $retefuente > 0) {
                    if ($personType === 'juridica') {
                        $grouped[$fundingSourceName][$reportConcept]['juridica_base']      += $subtotal;
                        $grouped[$fundingSourceName][$reportConcept]['juridica_retention'] += $retefuente;
                    } else {
                        $grouped[$fundingSourceName][$reportConcept]['natural_base']       += $subtotal;
                        $grouped[$fundingSourceName][$reportConcept]['natural_retention']  += $retefuente;
                    }
                }

                if ($reteiva > 0) {
                    if ($personType === 'juridica') {
                        $grouped[$fundingSourceName]['reteiva']['juridica_retention'] += $reteiva;
                    } else {
                        $grouped[$fundingSourceName]['reteiva']['natural_retention']  += $reteiva;
                    }
                }
            }
        }

        // Build report data with totals per funding source
        $this->reportData = [];
        $this->grandTotals = ['total_retentions' => 0];

        foreach ($grouped as $fsName => $concepts) {
            $fsTotal = 0;
            $rows = [];

            foreach (['servicios', 'compras', 'honorarios', 'reteiva'] as $conceptKey) {
                $data = $concepts[$conceptKey];
                $totalRetention = $data['juridica_retention'] + $data['natural_retention'];
                $fsTotal += $totalRetention;

                $rows[$conceptKey] = [
                    'juridica_base' => $data['juridica_base'],
                    'juridica_retention' => $data['juridica_retention'],
                    'natural_base' => $data['natural_base'],
                    'natural_retention' => $data['natural_retention'],
                    'total_retention' => $totalRetention,
                ];
            }

            $this->reportData[] = [
                'funding_source' => $fsName,
                'rows' => $rows,
                'total' => $fsTotal,
            ];

            $this->grandTotals['total_retentions'] += $fsTotal;
        }

        // Sort by funding source name
        usort($this->reportData, fn($a, $b) => strcmp($a['funding_source'], $b['funding_source']));

        $this->dispatch('reportLoaded');
    }

    private function getFundingSourceName($contract): string
    {
        if (!$contract) return 'Sin fuente';

        // Try from RPs first (more direct)
        $rp = $contract->rps->first();
        if ($rp && $rp->fundingSources->isNotEmpty()) {
            $fs = $rp->fundingSources->first()->fundingSource;
            if ($fs) return $fs->name;
        }

        // Fallback to CDP funding sources
        $convocatoria = $contract->convocatoria;
        if ($convocatoria) {
            $cdp = $convocatoria->cdps->first();
            if ($cdp && $cdp->fundingSources->isNotEmpty()) {
                $fs = $cdp->fundingSources->first()->fundingSource;
                if ($fs) return $fs->name;
            }
        }

        return 'Sin fuente';
    }

    public function getPeriodLabelProperty(): string
    {
        $months = [
            1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
            5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
            9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
        ];

        if ($this->filterMonth) {
            $m = $months[(int) $this->filterMonth] ?? '';
            return "{$m} DE {$this->filterYear}";
        }

        return "CONSOLIDADO {$this->filterYear}";
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.retention-liquidation-report');
    }
}

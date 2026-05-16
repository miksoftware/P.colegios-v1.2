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
    public $monthlySummary = []; // Resumen mensual consolidado

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
        // === 1) RESUMEN MENSUAL CONSOLIDADO (todo el año, no se filtra por mes) ===
        // Suma directa a nivel PO por mes. Cubre retefuente, reteiva, estampillas e ICA.
        $monthlyQuery = PaymentOrder::forSchool($this->schoolId)
            ->forYear((int) $this->filterYear)
            ->whereIn('status', ['approved', 'paid'])
            ->selectRaw(
                'MONTH(payment_date) as m, '
                . 'SUM(retefuente) AS retefuente, '
                . 'SUM(reteiva) AS reteiva, '
                . 'SUM(estampilla_procultura) AS estampilla_procultura, '
                . 'SUM(estampilla_produlto_mayor) AS estampilla_produlto, '
                . 'SUM(retencion_ica) AS retencion_ica'
            )
            ->groupBy('m')
            ->get();

        $monthlySummary = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlySummary[$m] = [
                'month'                => $m,
                'retefuente'           => 0.0,
                'reteiva'              => 0.0,
                'estampilla_procultura'=> 0.0,
                'estampilla_produlto'  => 0.0,
                'retencion_ica'        => 0.0,
                'total'                => 0.0,
            ];
        }
        foreach ($monthlyQuery as $row) {
            $m = (int) $row->m;
            if ($m < 1 || $m > 12) continue;
            $monthlySummary[$m] = [
                'month'                => $m,
                'retefuente'           => (float) $row->retefuente,
                'reteiva'              => (float) $row->reteiva,
                'estampilla_procultura'=> (float) $row->estampilla_procultura,
                'estampilla_produlto'  => (float) $row->estampilla_produlto,
                'retencion_ica'        => (float) $row->retencion_ica,
                'total'                => (float) $row->retefuente
                                          + (float) $row->reteiva
                                          + (float) $row->estampilla_procultura
                                          + (float) $row->estampilla_produlto
                                          + (float) $row->retencion_ica,
            ];
        }
        $this->monthlySummary = array_values($monthlySummary);

        // === 2) FORMATO POR FUENTE DE FINANCIACIÓN (según mes filtrado) ===
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
            $personType = $supplier?->person_type ?? 'natural';

            $fundingSourceName = $this->getFundingSourceName($po, $contract);

            if (!isset($grouped[$fundingSourceName])) {
                $grouped[$fundingSourceName] = [
                    'servicios'            => $this->emptyConceptRow(),
                    'compras'              => $this->emptyConceptRow(),
                    'honorarios'           => $this->emptyConceptRow(),
                    'reteiva'              => $this->emptyConceptRow(),
                    'estampilla_procultura'=> $this->emptyConceptRow(),
                    'estampilla_produlto'  => $this->emptyConceptRow(),
                    'retencion_ica'        => $this->emptyConceptRow(),
                ];
            }

            // Expense lines: fuente de verdad para retefuente/reteiva cuando existen.
            // Solo si NO hay líneas (o ninguna aportó) se usa el agregado del PO.
            $retentions = [];

            if ($po->expenseLines->isNotEmpty()) {
                foreach ($po->expenseLines as $line) {
                    if ((float) $line->retefuente == 0 && (float) $line->reteiva == 0) {
                        continue;
                    }
                    $retentions[] = [
                        'concept'    => $line->retention_concept ?? $po->retention_concept,
                        'base'       => (float) $line->subtotal,
                        'retefuente' => (float) $line->retefuente,
                        'reteiva'    => (float) $line->reteiva,
                    ];
                }
            }

            if (empty($retentions) && ((float) $po->retefuente > 0 || (float) $po->reteiva > 0)) {
                $retentions[] = [
                    'concept'    => $po->retention_concept,
                    'base'       => (float) $po->subtotal,
                    'retefuente' => (float) $po->retefuente,
                    'reteiva'    => (float) $po->reteiva,
                ];
            }

            foreach ($retentions as $ret) {
                $concept    = $ret['concept'];
                $subtotal   = $ret['base'];
                $retefuente = $ret['retefuente'];
                $reteiva    = $ret['reteiva'];

                $reportConcept = match ($concept) {
                    'compras'     => 'compras',
                    'honorarios'  => 'honorarios',
                    'servicios', 'arrendamiento_sitios_web', 'arrendamiento_inmuebles', 'transporte_pasajeros' => 'servicios',
                    default       => null,
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

            // Estampillas e ICA: viven únicamente a nivel PO, no por expense_line.
            // Se atribuyen a la fuente del contrato y al tipo de persona del proveedor.
            $estamCultura = (float) $po->estampilla_procultura;
            $estamProd    = (float) $po->estampilla_produlto_mayor;
            $ica          = (float) $po->retencion_ica;

            foreach ([
                'estampilla_procultura' => $estamCultura,
                'estampilla_produlto'   => $estamProd,
                'retencion_ica'         => $ica,
            ] as $key => $amount) {
                if ($amount <= 0) continue;
                if ($personType === 'juridica') {
                    $grouped[$fundingSourceName][$key]['juridica_retention'] += $amount;
                } else {
                    $grouped[$fundingSourceName][$key]['natural_retention']  += $amount;
                }
            }
        }

        // Armar datos por fuente
        $this->reportData = [];
        $this->grandTotals = ['total_retentions' => 0];

        $conceptOrder = ['servicios', 'compras', 'honorarios', 'reteiva', 'estampilla_procultura', 'estampilla_produlto', 'retencion_ica'];

        foreach ($grouped as $fsName => $concepts) {
            $fsTotal = 0;
            $rows = [];

            foreach ($conceptOrder as $conceptKey) {
                $data = $concepts[$conceptKey];
                $totalRetention = $data['juridica_retention'] + $data['natural_retention'];
                $fsTotal += $totalRetention;

                $rows[$conceptKey] = [
                    'juridica_base'      => $data['juridica_base'],
                    'juridica_retention' => $data['juridica_retention'],
                    'natural_base'       => $data['natural_base'],
                    'natural_retention'  => $data['natural_retention'],
                    'total_retention'    => $totalRetention,
                ];
            }

            $this->reportData[] = [
                'funding_source' => $fsName,
                'rows'           => $rows,
                'total'          => $fsTotal,
            ];

            $this->grandTotals['total_retentions'] += $fsTotal;
        }

        usort($this->reportData, fn($a, $b) => strcmp($a['funding_source'], $b['funding_source']));

        $this->dispatch('reportLoaded');
    }

    private function emptyConceptRow(): array
    {
        return [
            'juridica_base'      => 0,
            'juridica_retention' => 0,
            'natural_base'       => 0,
            'natural_retention'  => 0,
        ];
    }

    private function getFundingSourceName($po, $contract): string
    {
        // 1. RP específico del PO (cubre contratos multi-fuente y POs sin contract_id)
        $rp = $po->contractRp;
        if ($rp && $rp->fundingSources->isNotEmpty()) {
            $fs = $rp->fundingSources->first()->fundingSource;
            if ($fs) return $fs->name;
        }

        // 2. Primer RP del contrato (fallback para POs sin contract_rp_id)
        if ($contract) {
            $rp = $contract->rps->first();
            if ($rp && $rp->fundingSources->isNotEmpty()) {
                $fs = $rp->fundingSources->first()->fundingSource;
                if ($fs) return $fs->name;
            }

            // 3. CDP de la convocatoria
            $convocatoria = $contract->convocatoria;
            if ($convocatoria) {
                $cdp = $convocatoria->cdps->first();
                if ($cdp && $cdp->fundingSources->isNotEmpty()) {
                    $fs = $cdp->fundingSources->first()->fundingSource;
                    if ($fs) return $fs->name;
                }
            }
        }

        // 4. CDP directo del PO (pago directo)
        $cdp = $po->cdp;
        if ($cdp && $cdp->fundingSources->isNotEmpty()) {
            $fs = $cdp->fundingSources->first()->fundingSource;
            if ($fs) return $fs->name;
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

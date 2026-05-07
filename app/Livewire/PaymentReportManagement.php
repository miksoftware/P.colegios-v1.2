<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\PaymentOrder;
use App\Models\School;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;

class PaymentReportManagement extends Component
{
    public $schoolId;
    public $school;

    // Filtros
    public $filterYear;
    public $filterPeriodType = 'annual'; // monthly | quarterly | semiannual | annual
    public $filterMonth = '';
    public $filterQuarter = '';
    public $filterSemester = '';
    public $filterSupplier = '';
    public $filterFundingSource = '';

    // Datos
    public $payments = [];
    public $summary = [];

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

    public function updatedFilterPeriodType()
    {
        // Reset sub-filters when period type changes
        $this->filterMonth   = '';
        $this->filterQuarter = '';
        $this->filterSemester = '';
        $this->loadReport();
    }

    public function updatedFilterMonth()
    {
        $this->loadReport();
    }

    public function updatedFilterQuarter()
    {
        $this->loadReport();
    }

    public function updatedFilterSemester()
    {
        $this->loadReport();
    }

    public function updatedFilterSupplier()
    {
        $this->loadReport();
    }

    public function updatedFilterFundingSource()
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
                'contract.convocatoria.cdps.contractRp',
                'contract.rps.cdp',
                'contract.rps.fundingSources.fundingSource',
                'contract.rps.fundingSources.budget.budgetItem',
                'supplier',
                'cdp.fundingSources.fundingSource',
                'contractRp.cdp',
                'contractRp.fundingSources.fundingSource',
                'contractRp.fundingSources.budget.budgetItem',
                'budgetItem',
                'expenseLines.expenseDistribution.budget.budgetItem',
                'expenseLines.expenseDistribution.budget.fundingSource',
                'expenseLines.expenseCode',
            ]);

        if ($this->filterPeriodType === 'monthly' && $this->filterMonth) {
            $query->whereMonth('payment_date', (int) $this->filterMonth);
        } elseif ($this->filterPeriodType === 'quarterly' && $this->filterQuarter) {
            $months = match ((int) $this->filterQuarter) {
                1 => [1, 2, 3],
                2 => [4, 5, 6],
                3 => [7, 8, 9],
                4 => [10, 11, 12],
                default => [],
            };
            if ($months) {
                $query->whereIn(DB::raw('MONTH(payment_date)'), $months);
            }
        } elseif ($this->filterPeriodType === 'semiannual' && $this->filterSemester) {
            $months = (int) $this->filterSemester === 1 ? [1, 2, 3, 4, 5, 6] : [7, 8, 9, 10, 11, 12];
            $query->whereIn(DB::raw('MONTH(payment_date)'), $months);
        }
        // annual: no month filter needed

        if ($this->filterSupplier) {
            $query->where(function ($q) {
                $q->whereHas('contract.supplier', function ($sq) {
                    $sq->where('first_surname', 'like', "%{$this->filterSupplier}%")
                      ->orWhere('first_name', 'like', "%{$this->filterSupplier}%")
                      ->orWhere('document_number', 'like', "%{$this->filterSupplier}%");
                })->orWhereHas('supplier', function ($sq) {
                    $sq->where('first_surname', 'like', "%{$this->filterSupplier}%")
                      ->orWhere('first_name', 'like', "%{$this->filterSupplier}%")
                      ->orWhere('document_number', 'like', "%{$this->filterSupplier}%");
                });
            });
        }

        $paymentOrders = $query->orderBy('payment_number')->get();

        $rows = [];

        foreach ($paymentOrders as $po) {
            $contract = $po->contract;
            $supplier = $po->resolved_supplier;
            $convocatoria = $contract?->convocatoria;

            // Determinar RP y CDP de la orden de pago
            $rp  = $po->contractRp ?? $contract?->rps->first();
            $cdp = $rp?->cdp ?? $po->cdp ?? $convocatoria?->cdps->first();

            // Datos comunes a todas las filas que se generen para este PO
            $baseData = [
                'id'                => $po->id,
                'payment_number'    => $po->payment_number,
                'formatted_number'  => $po->formatted_number,
                'payment_date'      => $po->payment_date?->format('Y/m/d'),
                'invoice_number'    => $po->invoice_number,
                'invoice_date'      => $po->invoice_date?->format('Y/m/d'),
                'supplier_name'     => $supplier?->full_name ?? '',
                'supplier_document' => $supplier?->document_number ?? '',
                'supplier_address'  => $supplier?->address ?? '',
                'detail'            => $contract?->object ?? $po->description ?? '',
                'sede'              => 'RECTORÍA',
                'contract_number'   => $contract ? "CONTRATO No. {$contract->formatted_number}" : ($po->payment_type === 'direct' ? 'PAGO DIRECTO' : ''),
                'contract_date'     => $contract?->start_date?->format('Y/m/d'),
                'cdp_number'        => $cdp?->cdp_number ?? '',
                'rp_number'         => $rp?->rp_number ?? '',
                'status'            => $po->status,
            ];

            // Fuentes del RP (para mapear budget_id → fuente como fallback)
            $rpSources = collect();
            if ($rp) {
                $rpSources = $rp->fundingSources;
            }
            $totalRpAmount = (float) $rpSources->sum('amount');

            // Expense lines del PO (una por rubro/código de gasto)
            $expenseLines = $po->expenseLines;

            // Caso A: hay expense_lines → UNA FILA POR CADA RUBRO (línea)
            // Cada línea ya tiene sus valores exactos (total, retenciones, neto)
            // y su fuente de financiación viene de expense_distribution.budget.funding_source.
            // No se prorratea entre fuentes del RP (eso producía filas duplicadas/fragmentadas).
            if ($expenseLines->isNotEmpty()) {
                foreach ($expenseLines as $line) {
                    $ec   = $line->expenseCode;
                    $dist = $line->expenseDistribution;
                    $bi   = $dist?->budget?->budgetItem;

                    // Fuente de financiación: preferir la del budget de la distribución.
                    $fs = $dist?->budget?->fundingSource;

                    // Fallback: buscar en el RP la fuente cuyo budget_id coincida con el de la distribución.
                    if (!$fs && $rpSources->isNotEmpty() && $dist?->budget_id) {
                        $matchingRpFs = $rpSources->firstWhere('budget_id', $dist->budget_id);
                        $fs = $matchingRpFs?->fundingSource;
                    }

                    $rows[] = array_merge($baseData, [
                        'funding_source' => $fs ? "{$fs->name} ({$fs->code})" : '',
                        'rubro_code'     => $ec?->code ?? $bi?->code ?? '',
                        'rubro_name'     => $ec?->name ?? $bi?->name ?? '',
                        'total'          => (float) $line->total,
                        'retefuente'     => (float) $line->retefuente,
                        'reteiva'        => (float) $line->reteiva,
                        'estampillas'    => (float) $line->estampilla_produlto_mayor + (float) $line->estampilla_procultura,
                        'otros_impuestos'=> (float) $line->retencion_ica,
                        'net_payment'    => (float) $line->net_payment,
                    ]);
                }
                continue;
            }

            // Caso C: no hay expense_lines (pago viejo o impuesto) → una fila por fuente del RP
            if ($rpSources->isNotEmpty()) {
                $poTotal = (float) $po->total;
                $poNet   = (float) $po->net_payment;
                foreach ($rpSources as $rpFs) {
                    $fs    = $rpFs->fundingSource;
                    $bi    = $rpFs->budget?->budgetItem;
                    $ratio = $totalRpAmount > 0 ? (float) $rpFs->amount / $totalRpAmount : 1;
                    $rows[] = array_merge($baseData, [
                        'funding_source' => $fs ? "{$fs->name} ({$fs->code})" : '',
                        'rubro_code'     => $bi?->code ?? '',
                        'rubro_name'     => $bi?->name ?? '',
                        'total'          => round($poTotal * $ratio, 2),
                        'retefuente'     => round((float) $po->retefuente * $ratio, 2),
                        'reteiva'        => round((float) $po->reteiva * $ratio, 2),
                        'estampillas'    => round(((float) $po->estampilla_produlto_mayor + (float) $po->estampilla_procultura) * $ratio, 2),
                        'otros_impuestos'=> round((float) $po->retencion_ica * $ratio, 2),
                        'net_payment'    => round($poNet * $ratio, 2),
                    ]);
                }
                continue;
            }

            // Caso D: sin contrato/RP y sin expense_lines → una sola fila con datos de la orden
            $bi = $po->budgetItem;
            $rows[] = array_merge($baseData, [
                'funding_source' => '',
                'rubro_code'     => $bi?->code ?? '',
                'rubro_name'     => $bi?->name ?? '',
                'total'          => (float) $po->total,
                'retefuente'     => (float) $po->retefuente,
                'reteiva'        => (float) $po->reteiva,
                'estampillas'    => (float) ($po->estampilla_produlto_mayor + $po->estampilla_procultura),
                'otros_impuestos'=> (float) $po->retencion_ica,
                'net_payment'    => (float) $po->net_payment,
            ]);
        }

        $this->payments = $rows;

        // Filtro por fuente de financiación (post-query)
        if ($this->filterFundingSource) {
            $this->payments = collect($this->payments)
                ->filter(fn($p) => str_contains(strtolower($p['funding_source']), strtolower($this->filterFundingSource)))
                ->values()
                ->toArray();
        }

        // Calcular resumen
        $collection = collect($this->payments);
        $this->summary = [
            'total_payments' => $collection->count(),
            'total_amount' => $collection->sum('total'),
            'total_retentions' => $collection->sum(fn($p) => $p['retefuente'] + $p['reteiva'] + $p['estampillas'] + $p['otros_impuestos']),
            'total_net' => $collection->sum('net_payment'),
            'by_funding_source' => $collection->groupBy('funding_source')->map(fn($group, $key) => [
                'name' => $key ?: 'Sin fuente',
                'count' => $group->count(),
                'total' => $group->sum('total'),
                'net' => $group->sum('net_payment'),
            ])->values()->toArray(),
            'by_month' => $collection->groupBy(fn($p) => substr($p['payment_date'] ?? '', 0, 7))->map(fn($group, $key) => [
                'month' => $key,
                'count' => $group->count(),
                'total' => $group->sum('total'),
                'net' => $group->sum('net_payment'),
            ])->sortKeys()->values()->toArray(),
        ];

        $this->dispatch('reportLoaded');
    }

    public function getPeriodLabelProperty(): string
    {
        $months = [
            1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
            5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
            9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
        ];

        $y = $this->filterYear;

        if ($this->filterPeriodType === 'monthly' && $this->filterMonth) {
            $m   = $months[(int) $this->filterMonth] ?? '';
            $last = \Carbon\Carbon::create((int) $y, (int) $this->filterMonth)->endOfMonth()->day;
            return "DE {$m} 01 AL {$last} DE {$m} DE {$y}";
        }

        if ($this->filterPeriodType === 'quarterly' && $this->filterQuarter) {
            $q = (int) $this->filterQuarter;
            $starts = [1 => ['ENERO',    '01'], 2 => ['ABRIL',  '01'], 3 => ['JULIO',  '01'], 4 => ['OCTUBRE', '01']];
            $ends   = [1 => ['MARZO',    '31'], 2 => ['JUNIO',  '30'], 3 => ['SEPTIEMBRE', '30'], 4 => ['DICIEMBRE', '31']];
            return "DE {$starts[$q][0]} {$starts[$q][1]} AL {$ends[$q][1]} DE {$ends[$q][0]} DE {$y} (TRIMESTRE {$q})";
        }

        if ($this->filterPeriodType === 'semiannual' && $this->filterSemester) {
            if ((int) $this->filterSemester === 1) {
                return "DE ENERO 01 AL 30 DE JUNIO DE {$y} (PRIMER SEMESTRE)";
            }
            return "DE JULIO 01 AL 31 DE DICIEMBRE DE {$y} (SEGUNDO SEMESTRE)";
        }

        return "DE ENERO 01 AL 31 DE DICIEMBRE DE {$y} CONSOLIDADO";
    }

    public function exportExcel()
    {
        if (!auth()->user()->can('reports.export')) {
            $this->dispatch('toast', message: 'No tienes permisos para exportar.', type: 'error');
            return;
        }

        $this->dispatch('export-payment-report');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.payment-report-management');
    }
}

<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\ExpenseDistribution;
use App\Models\PaymentOrderExpenseLine;
use App\Models\RpFundingSource;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

class ExpenseExecutionReport extends Component
{
    public $schoolId;
    public $school;
    public $filterYear;
    public $filterQuarter = '';

    public $rows = [];
    public $totals = [];

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

    public function updatedFilterQuarter()
    {
        $this->loadReport();
    }

    public function loadReport()
    {
        $year = (int) $this->filterYear;
        $quarter = $this->filterQuarter ? (int) $this->filterQuarter : null;

        // Rango de fechas para el trimestre
        $dateFrom = null;
        $dateTo = null;
        if ($quarter) {
            $dateFrom = "{$year}-" . str_pad(($quarter - 1) * 3 + 1, 2, '0', STR_PAD_LEFT) . "-01";
            $lastMonth = $quarter * 3;
            $dateTo = \Carbon\Carbon::parse("{$year}-{$lastMonth}-01")->endOfMonth()->format('Y-m-d');
        }

        // Get all expense budgets for this school/year with their funding source
        $budgets = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->byType('expense')
            ->with([
                'budgetItem',
                'fundingSource',
                'modifications',
                'outgoingTransfers',
                'incomingTransfers',
                'distributions.expenseCode',
            ])
            ->orderBy('budget_item_id')
            ->get();

        // Pre-load commitments (RP amounts) per expense_distribution
        // Cadena correcta: rp_funding_sources → contract_rps → contracts → convocatoria_distributions → expense_distribution
        // Esto evita el prorrateo incorrecto a nivel de Budget y usa los RPs reales de cada distribución.
        $distIds = $budgets->flatMap(fn($b) => $b->distributions->pluck('id'))->toArray();

        $commitmentsByDist = [];
        if (!empty($distIds)) {
            $query = \Illuminate\Support\Facades\DB::table('rp_funding_sources')
                ->join('contract_rps', 'contract_rps.id', '=', 'rp_funding_sources.contract_rp_id')
                ->join('contracts', 'contracts.id', '=', 'contract_rps.contract_id')
                ->join('convocatoria_distributions', 'convocatoria_distributions.convocatoria_id', '=', 'contracts.convocatoria_id')
                ->whereIn('convocatoria_distributions.expense_distribution_id', $distIds)
                ->where('contract_rps.status', '!=', 'cancelled')
                ->whereNotNull('contracts.convocatoria_id');
            if ($dateFrom && $dateTo) {
                $query->whereBetween('contract_rps.created_at', [$dateFrom, $dateTo . ' 23:59:59']);
            }
            $commitmentsByDist = $query
                ->selectRaw('convocatoria_distributions.expense_distribution_id, SUM(rp_funding_sources.amount) as total')
                ->groupBy('convocatoria_distributions.expense_distribution_id')
                ->pluck('total', 'expense_distribution_id')
                ->toArray();
        }

        // Compromisos a nivel de Budget para presupuestos SIN distribuciones (pagos directos puros)
        $commitmentsByBudget = [];
        $budgetsWithoutDist = $budgets->filter(fn($b) => $b->distributions->isEmpty());
        if ($budgetsWithoutDist->isNotEmpty()) {
            $emptyBudgetIds = $budgetsWithoutDist->pluck('id')->toArray();
            $q2 = RpFundingSource::whereIn('budget_id', $emptyBudgetIds)
                ->whereHas('contractRp', function ($q) use ($dateFrom, $dateTo) {
                    $q->where('status', '!=', 'cancelled');
                    if ($dateFrom && $dateTo) {
                        $q->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);
                    }
                });
            $commitmentsByBudget = $q2->selectRaw('budget_id, SUM(amount) as total')
                ->groupBy('budget_id')
                ->pluck('total', 'budget_id')
                ->toArray();
        }

        $paymentsByDist = [];
        if (!empty($distIds)) {
            $query = PaymentOrderExpenseLine::whereIn('expense_distribution_id', $distIds)
                ->whereHas('paymentOrder', function ($q) use ($dateFrom, $dateTo) {
                    $q->whereIn('status', ['approved', 'paid']);
                    if ($dateFrom && $dateTo) {
                        $q->whereBetween('payment_date', [$dateFrom, $dateTo]);
                    }
                });
            $paymentsByDist = $query->selectRaw('expense_distribution_id, SUM(total) as total_paid')
                ->groupBy('expense_distribution_id')
                ->pluck('total_paid', 'expense_distribution_id')
                ->toArray();
        }

        // Pre-load direct payments (pagos sin contrato) per budget_item
        $directPaymentsByBudgetItem = [];
        $budgetItemIds = $budgets->pluck('budget_item_id')->unique()->toArray();
        if (!empty($budgetItemIds)) {
            $query = \App\Models\PaymentOrder::where('school_id', $this->schoolId)
                ->where('fiscal_year', $year)
                ->where('payment_type', 'direct')
                ->whereIn('budget_item_id', $budgetItemIds)
                ->whereIn('status', ['approved', 'paid']);
            if ($dateFrom && $dateTo) {
                $query->whereBetween('payment_date', [$dateFrom, $dateTo]);
            }
            $directPaymentsByBudgetItem = $query->selectRaw('budget_item_id, SUM(total) as total_paid')
                ->groupBy('budget_item_id')
                ->pluck('total_paid', 'budget_item_id')
                ->toArray();
        }

        $this->rows = [];

        foreach ($budgets as $budget) {
            $initial = (float) $budget->initial_amount;
            $additions = (float) $budget->modifications->where('type', 'addition')->sum('amount');
            $reductions = (float) $budget->modifications->where('type', 'reduction')->sum('amount');
            $credits = (float) $budget->incomingTransfers->sum('amount');
            $contracredits = (float) $budget->outgoingTransfers->sum('amount');
            $definitive = (float) $budget->current_amount;
            $totalBudgetCommitments = (float) ($commitmentsByBudget[$budget->id] ?? 0); // solo para presupuestos sin distribuciones
            $directPayments = (float) ($directPaymentsByBudgetItem[$budget->budget_item_id] ?? 0);
            $fundingCode = $budget->fundingSource?->code ?? '';
            $fundingName = $budget->fundingSource?->name ?? '';

            $distributions = $budget->distributions;

            if ($distributions->isEmpty()) {
                $totalObligations = $directPayments;
                $this->rows[] = [
                    'budget_id' => $budget->id,
                    'rubro_code' => $budget->budgetItem?->code ?? '',
                    'rubro_name' => $budget->budgetItem?->name ?? '',
                    'funding_source_code' => $fundingCode,
                    'funding_source_name' => $fundingName,
                    'initial' => $initial,
                    'additions' => $additions,
                    'reductions' => $reductions,
                    'credits' => $credits,
                    'contracredits' => $contracredits,
                    'definitive' => $definitive,
                    'commitments' => $totalBudgetCommitments + $directPayments,
                    'obligations' => $totalObligations,
                    'payments' => $totalObligations,
                    'pending' => $definitive - $totalBudgetCommitments - $directPayments,
                ];
            } else {
                $totalDistAmount = $distributions->sum('amount');

                foreach ($distributions as $dist) {
                    $expCode = $dist->expenseCode;
                    $distPayments = (float) ($paymentsByDist[$dist->id] ?? 0);
                    // Compromisos reales de esta distribución (via RPs de sus contratos)
                    $distCommitments = (float) ($commitmentsByDist[$dist->id] ?? 0);
                    // Los pagos directos se siguen prorrateando porque no tienen distribución específica
                    $ratio = $totalDistAmount > 0 ? (float) $dist->amount / $totalDistAmount : 0;
                    $directProrated = round($directPayments * $ratio, 2);
                    $totalObligations = $distPayments + $directProrated;
                    $totalCommitmentsRow = $distCommitments + $directProrated;

                    $this->rows[] = [
                        'budget_id' => $budget->id,
                        'rubro_code' => $expCode?->code ?? '',
                        'rubro_name' => $expCode?->name ?? '',
                        'funding_source_code' => $fundingCode,
                        'funding_source_name' => $fundingName,
                        'initial' => round($initial * $ratio, 2),
                        'additions' => round($additions * $ratio, 2),
                        'reductions' => round($reductions * $ratio, 2),
                        'credits' => round($credits * $ratio, 2),
                        'contracredits' => round($contracredits * $ratio, 2),
                        'definitive' => round($definitive * $ratio, 2),
                        'commitments' => $totalCommitmentsRow,
                        'obligations' => $totalObligations,
                        'payments' => $totalObligations,
                        'pending' => round($definitive * $ratio, 2) - $totalCommitmentsRow,
                    ];
                }
            }
        }

        // Totales
        $c = collect($this->rows);
        $this->totals = [
            'initial' => $c->sum('initial'),
            'additions' => $c->sum('additions'),
            'reductions' => $c->sum('reductions'),
            'credits' => $c->sum('credits'),
            'contracredits' => $c->sum('contracredits'),
            'definitive' => $c->sum('definitive'),
            'commitments' => $c->sum('commitments'),
            'obligations' => $c->sum('obligations'),
            'payments' => $c->sum('payments'),
            'pending' => $c->sum('pending'),
        ];

        $this->dispatch('reportLoaded');
    }

    public function getPeriodLabelProperty(): string
    {
        if ($this->filterQuarter) {
            $labels = [1 => 'PRIMER', 2 => 'SEGUNDO', 3 => 'TERCER', 4 => 'CUARTO'];
            return "{$labels[(int)$this->filterQuarter]} TRIMESTRE DE {$this->filterYear}";
        }
        return "A DICIEMBRE 31 DE {$this->filterYear} CONSOLIDADO";
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.expense-execution-report');
    }
}

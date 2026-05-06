<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\ExpenseDistribution;
use App\Models\PaymentOrderExpenseLine;
use App\Models\RpFundingSource;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

class PacExpenseReport extends Component
{
    public $schoolId;
    public $school;
    public $filterYear;

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

    public function loadReport()
    {
        $year = (int) $this->filterYear;

        // Get all expense budgets for this school/year
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

        // Pre-load commitments (RP amounts) per budget
        $budgetIds = $budgets->pluck('id')->toArray();

        $commitmentsByBudget = [];
        if (!empty($budgetIds)) {
            $commitmentsByBudget = RpFundingSource::whereIn('budget_id', $budgetIds)
                ->whereHas('contractRp', fn($q) => $q->where('status', '!=', 'cancelled'))
                ->selectRaw('budget_id, SUM(amount) as total')
                ->groupBy('budget_id')
                ->pluck('total', 'budget_id')
                ->toArray();
        }

        // Pre-load payments per expense_distribution grouped by RP month (no payment month)
        // El PAC refleja el MOMENTO DEL COMPROMISO (RP), no el pago.
        // Cadena: rp_funding_sources → contract_rps → contracts → convocatoria_distributions
        $distIds = $budgets->flatMap(fn($b) => $b->distributions->pluck('id'))->toArray();

        $paymentsByDistMonth = [];
        if (!empty($distIds)) {
            $rawRps = \Illuminate\Support\Facades\DB::table('rp_funding_sources as rfs')
                ->join('contract_rps as cr', 'cr.id', '=', 'rfs.contract_rp_id')
                ->join('contracts as c', 'c.id', '=', 'cr.contract_id')
                ->join('convocatoria_distributions as cd', 'cd.convocatoria_id', '=', 'c.convocatoria_id')
                ->join('expense_distributions as ed', function ($join) {
                    $join->on('ed.id', '=', 'cd.expense_distribution_id')
                         ->on('ed.budget_id', '=', 'rfs.budget_id');
                })
                ->whereIn('cd.expense_distribution_id', $distIds)
                ->where('cr.status', '!=', 'cancelled')
                ->where('c.status', '!=', 'annulled')
                ->selectRaw('cd.expense_distribution_id, MONTH(COALESCE(cr.otrosi_date, cr.created_at)) as pay_month, SUM(rfs.amount) as total_amount')
                ->groupBy('cd.expense_distribution_id', 'pay_month')
                ->get();

            foreach ($rawRps as $rp) {
                $paymentsByDistMonth[$rp->expense_distribution_id][$rp->pay_month] = (float) $rp->total_amount;
            }

            // Pagos directos con expense_lines: atribuir al mes del payment_date
            // (no tienen RP propio de contrato, representan compromiso y pago simultáneos)
            $rawDirectLines = \Illuminate\Support\Facades\DB::table('payment_order_expense_lines as pol')
                ->join('payment_orders as po', 'po.id', '=', 'pol.payment_order_id')
                ->whereIn('pol.expense_distribution_id', $distIds)
                ->where('po.payment_type', 'direct')
                ->whereIn('po.status', ['approved', 'paid'])
                ->selectRaw('pol.expense_distribution_id, MONTH(po.payment_date) as pay_month, SUM(pol.total) as total_paid')
                ->groupBy('pol.expense_distribution_id', 'pay_month')
                ->get();

            foreach ($rawDirectLines as $dp) {
                $m = (int) $dp->pay_month;
                $paymentsByDistMonth[$dp->expense_distribution_id][$m] = ($paymentsByDistMonth[$dp->expense_distribution_id][$m] ?? 0) + (float) $dp->total_paid;
            }
        }

        // Also get total payments per distribution (for summary) — usar mismos datos
        $totalPaymentsByDist = [];
        foreach ($paymentsByDistMonth as $distId => $months) {
            $totalPaymentsByDist[$distId] = array_sum($months);
        }

        // Pre-load direct payments per budget_item grouped by month (pagos directos puros).
        // Los pagos directos sin CDP/RP representan compromiso+pago simultáneos,
        // por lo que usamos payment_date como mes del compromiso.
        $budgetItemIds = $budgets->pluck('budget_item_id')->unique()->toArray();
        $directPaymentsByItemMonth = [];
        $directPaymentsTotalByItem = [];
        if (!empty($budgetItemIds)) {
            $rawDirect = \App\Models\PaymentOrder::where('school_id', $this->schoolId)
                ->where('fiscal_year', $year)
                ->where('payment_type', 'direct')
                ->whereNull('cdp_id')
                ->whereIn('budget_item_id', $budgetItemIds)
                ->whereIn('status', ['approved', 'paid'])
                ->whereDoesntHave('expenseLines')
                ->whereDoesntHave('taxLines')
                ->selectRaw('budget_item_id, MONTH(payment_date) as pay_month, SUM(total) as total_paid')
                ->groupBy('budget_item_id', 'pay_month')
                ->get();

            foreach ($rawDirect as $dp) {
                $directPaymentsByItemMonth[$dp->budget_item_id][$dp->pay_month] = (float) $dp->total_paid;
                $directPaymentsTotalByItem[$dp->budget_item_id] = ($directPaymentsTotalByItem[$dp->budget_item_id] ?? 0) + (float) $dp->total_paid;
            }
        }

        // Build rows: aggregate by expense code across all budgets
        $codeRows = [];

        foreach ($budgets as $budget) {
            $initial = (float) $budget->initial_amount;
            $additions = (float) $budget->modifications->where('type', 'addition')->sum('amount');
            $reductions = (float) $budget->modifications->where('type', 'reduction')->sum('amount');
            $credits = (float) $budget->incomingTransfers->sum('amount');
            $contracredits = (float) $budget->outgoingTransfers->sum('amount');
            $definitive = (float) $budget->current_amount;

            $distributions = $budget->distributions;

            if ($distributions->isEmpty()) {
                $code = $budget->budgetItem?->code ?? '';
                $name = $budget->budgetItem?->name ?? '';
                $key = $code ?: 'no-code-' . $budget->id;

                if (!isset($codeRows[$key])) {
                    $codeRows[$key] = $this->emptyRow($code, $name);
                }

                $codeRows[$key]['initial'] += $initial;
                $codeRows[$key]['additions'] += $additions;
                $codeRows[$key]['reductions'] += $reductions;
                $codeRows[$key]['credits'] += $credits;
                $codeRows[$key]['contracredits'] += $contracredits;
                $codeRows[$key]['definitive'] += $definitive;

                // Add direct payments by month for this budget item
                $directMonthly = $directPaymentsByItemMonth[$budget->budget_item_id] ?? [];
                for ($m = 1; $m <= 12; $m++) {
                    $codeRows[$key]['months'][$m] += (float) ($directMonthly[$m] ?? 0);
                }
            } else {
                $totalDistAmount = $distributions->sum('amount');

                foreach ($distributions as $dist) {
                    $expCode = $dist->expenseCode;
                    $code = $expCode?->code ?? '';
                    $name = $expCode?->name ?? '';
                    $key = $code ?: 'no-code-dist-' . $dist->id;

                    if (!isset($codeRows[$key])) {
                        $codeRows[$key] = $this->emptyRow($code, $name);
                    }

                    $ratio = $totalDistAmount > 0 ? (float) $dist->amount / $totalDistAmount : 0;

                    $codeRows[$key]['initial'] += round($initial * $ratio, 2);
                    $codeRows[$key]['additions'] += round($additions * $ratio, 2);
                    $codeRows[$key]['reductions'] += round($reductions * $ratio, 2);
                    $codeRows[$key]['credits'] += round($credits * $ratio, 2);
                    $codeRows[$key]['contracredits'] += round($contracredits * $ratio, 2);
                    $codeRows[$key]['definitive'] += round($definitive * $ratio, 2);

                    // Monthly commitments: RPs (via contrato) + pagos directos con línea
                    $monthlyData = $paymentsByDistMonth[$dist->id] ?? [];
                    for ($m = 1; $m <= 12; $m++) {
                        $codeRows[$key]['months'][$m] += (float) ($monthlyData[$m] ?? 0);
                    }
                    // Nota: los pagos directos sin expense_lines (budgetItem-level)
                    // se agregan solo al presupuesto sin distribuciones (caso superior).
                }
            }
        }

        // Calculate executed and pending for each row
        foreach ($codeRows as &$row) {
            $row['executed'] = array_sum($row['months']);
            $row['pending'] = $row['definitive'] - $row['executed'];
        }
        unset($row);

        // Sort by code
        ksort($codeRows);
        $this->rows = array_values($codeRows);

        // Totals
        $c = collect($this->rows);
        $monthTotals = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthTotals[$m] = $c->sum(fn($r) => $r['months'][$m]);
        }

        $this->totals = [
            'initial' => $c->sum('initial'),
            'additions' => $c->sum('additions'),
            'reductions' => $c->sum('reductions'),
            'credits' => $c->sum('credits'),
            'contracredits' => $c->sum('contracredits'),
            'definitive' => $c->sum('definitive'),
            'months' => $monthTotals,
            'executed' => $c->sum('executed'),
            'pending' => $c->sum('pending'),
        ];

        $this->dispatch('reportLoaded');
    }

    private function emptyRow(string $code, string $name): array
    {
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = 0;
        }

        return [
            'code' => $code,
            'name' => $name,
            'initial' => 0,
            'additions' => 0,
            'reductions' => 0,
            'credits' => 0,
            'contracredits' => 0,
            'definitive' => 0,
            'months' => $months,
            'executed' => 0,
            'pending' => 0,
        ];
    }

    public function getApprovalDateProperty(): string
    {
        return $this->school->budget_approval_date
            ? \Carbon\Carbon::parse($this->school->budget_approval_date)->format('d/m/Y')
            : 'N/A';
    }

    public function getPeriodLabelProperty(): string
    {
        return "VIGENCIA {$this->filterYear}";
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.pac-expense-report');
    }
}

<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\ExpenseDistribution;
use App\Models\Income;
use App\Models\PaymentOrderExpenseLine;
use App\Models\RpFundingSource;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

class SifseReport extends Component
{
    public $schoolId;
    public $school;
    public $filterYear;
    public $filterTrimester = '';
    public $activeTab = 'expenses';

    public $expenseRows = [];
    public $incomeRows = [];
    public $expenseTotals = [];
    public $incomeTotals = [];

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
        $this->filterTrimester = (string) ceil(now()->month / 3);
        $this->loadReport();
    }

    public function updatedFilterYear()
    {
        $this->loadReport();
    }

    public function updatedFilterTrimester()
    {
        $this->loadReport();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    /**
     * Calcula la fecha de corte (último día del trimestre seleccionado).
     */
    private function getTrimesterCutoffDate(): string
    {
        $year = (int) $this->filterYear;
        $trimester = (int) ($this->filterTrimester ?: ceil(now()->month / 3));
        $endMonth = $trimester * 3;

        return \Carbon\Carbon::create($year, $endMonth, 1)->endOfMonth()->toDateString();
    }

    public function loadReport()
    {
        $this->loadExpenses();
        $this->loadIncomes();
        $this->dispatch('reportLoaded');
    }

    private function loadExpenses()
    {
        $year = (int) $this->filterYear;
        $daneCode = $this->school->dane_code ?? '';
        $cutoff = $this->getTrimesterCutoffDate();

        // Get all expense budgets for this school/year
        $budgets = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->byType('expense')
            ->with([
                'budgetItem',
                'fundingSource',
                'distributions.expenseCode',
            ])
            ->get();

        $budgetIds = $budgets->pluck('id')->toArray();

        // --- Modifications up to cutoff per budget ---
        $additionsByBudget = [];
        $reductionsByBudget = [];
        if (!empty($budgetIds)) {
            $mods = \App\Models\BudgetModification::whereIn('budget_id', $budgetIds)
                ->where(function ($q) use ($cutoff) {
                    $q->where('document_date', '<=', $cutoff)
                      ->orWhere(function ($q2) use ($cutoff) {
                          $q2->whereNull('document_date')
                             ->where('created_at', '<=', $cutoff . ' 23:59:59');
                      });
                })
                ->get();

            foreach ($mods as $mod) {
                if ($mod->type === 'addition') {
                    $additionsByBudget[$mod->budget_id] = ($additionsByBudget[$mod->budget_id] ?? 0) + (float) $mod->amount;
                } else {
                    $reductionsByBudget[$mod->budget_id] = ($reductionsByBudget[$mod->budget_id] ?? 0) + (float) $mod->amount;
                }
            }
        }

        // --- Transfers up to cutoff per budget ---
        $creditsByBudget = [];
        $contracreditsByBudget = [];
        if (!empty($budgetIds)) {
            $transfers = \App\Models\BudgetTransfer::where(function ($q) use ($budgetIds) {
                    $q->whereIn('source_budget_id', $budgetIds)
                      ->orWhereIn('destination_budget_id', $budgetIds);
                })
                ->where(function ($q) use ($cutoff) {
                    $q->where('document_date', '<=', $cutoff)
                      ->orWhere(function ($q2) use ($cutoff) {
                          $q2->whereNull('document_date')
                             ->where('created_at', '<=', $cutoff . ' 23:59:59');
                      });
                })
                ->get();

            foreach ($transfers as $t) {
                if (in_array($t->destination_budget_id, $budgetIds)) {
                    $creditsByBudget[$t->destination_budget_id] = ($creditsByBudget[$t->destination_budget_id] ?? 0) + (float) $t->amount;
                }
                if (in_array($t->source_budget_id, $budgetIds)) {
                    $contracreditsByBudget[$t->source_budget_id] = ($contracreditsByBudget[$t->source_budget_id] ?? 0) + (float) $t->amount;
                }
            }
        }

        // --- Commitments (RP) up to cutoff per budget ---
        // La fecha correcta es la del ContractRp (otrosi_date cuando exista, si no created_at).
        $commitmentsByBudget = [];
        if (!empty($budgetIds)) {
            $commitmentsByBudget = RpFundingSource::whereIn('budget_id', $budgetIds)
                ->whereHas('contractRp', function ($q) use ($cutoff) {
                    $q->where('status', '!=', 'cancelled')
                      ->where(function ($qq) use ($cutoff) {
                          $qq->where('otrosi_date', '<=', $cutoff)
                             ->orWhere(function ($qqq) use ($cutoff) {
                                 $qqq->whereNull('otrosi_date')
                                     ->where('created_at', '<=', $cutoff . ' 23:59:59');
                             });
                      });
                })
                ->selectRaw('budget_id, SUM(amount) as total')
                ->groupBy('budget_id')
                ->pluck('total', 'budget_id')
                ->toArray();
        }

        // --- Payments up to cutoff per expense_distribution ---
        $distIds = $budgets->flatMap(fn($b) => $b->distributions->pluck('id'))->toArray();
        $paymentsByDist = [];
        if (!empty($distIds)) {
            $paymentsByDist = PaymentOrderExpenseLine::whereIn('expense_distribution_id', $distIds)
                ->whereHas('paymentOrder', fn($q) => $q
                    ->whereIn('status', ['approved', 'paid'])
                    ->where('payment_date', '<=', $cutoff)
                )
                ->selectRaw('expense_distribution_id, SUM(total) as total_paid')
                ->groupBy('expense_distribution_id')
                ->pluck('total_paid', 'expense_distribution_id')
                ->toArray();
        }

        // --- Direct payments up to cutoff per budget_item ---
        $budgetItemIds = $budgets->pluck('budget_item_id')->unique()->toArray();
        $directPaymentsByItem = [];
        if (!empty($budgetItemIds)) {
            $directPaymentsByItem = \App\Models\PaymentOrder::where('school_id', $this->schoolId)
                ->where('fiscal_year', $year)
                ->where('payment_type', 'direct')
                ->whereIn('budget_item_id', $budgetItemIds)
                ->whereIn('status', ['approved', 'paid'])
                ->where('payment_date', '<=', $cutoff)
                ->selectRaw('budget_item_id, SUM(total) as total_paid')
                ->groupBy('budget_item_id')
                ->pluck('total_paid', 'budget_item_id')
                ->toArray();
        }

        // Build raw rows per funding_source + sifse_code
        $rawRows = [];

        foreach ($budgets as $budget) {
            $initial = (float) $budget->initial_amount;
            $additions = (float) ($additionsByBudget[$budget->id] ?? 0);
            $reductions = (float) ($reductionsByBudget[$budget->id] ?? 0);
            $credits = (float) ($creditsByBudget[$budget->id] ?? 0);
            $contracredits = (float) ($contracreditsByBudget[$budget->id] ?? 0);
            $definitive = $initial + $additions - $reductions + $credits - $contracredits;
            $totalCommitments = (float) ($commitmentsByBudget[$budget->id] ?? 0);
            $fundingCode = $budget->fundingSource?->code ?? '';

            $distributions = $budget->distributions;

            if ($distributions->isEmpty()) {
                continue;
            }

            $totalDistAmount = $distributions->sum('amount');
            $directPayments = (float) ($directPaymentsByItem[$budget->budget_item_id] ?? 0);

            foreach ($distributions as $dist) {
                $expCode = $dist->expenseCode;
                $sifseCode = $expCode?->sifse_code ?? '';
                $distPayments = (float) ($paymentsByDist[$dist->id] ?? 0);
                $ratio = $totalDistAmount > 0 ? (float) $dist->amount / $totalDistAmount : 0;
                $directProrated = round($directPayments * $ratio, 2);

                $key = "{$fundingCode}|{$sifseCode}";

                if (!isset($rawRows[$key])) {
                    $rawRows[$key] = [
                        'funding_source_code' => $fundingCode,
                        'sifse_code' => $sifseCode,
                        'initial' => 0,
                        'definitive' => 0,
                        'commitments' => 0,
                        'obligations' => 0,
                        'payments' => 0,
                    ];
                }

                $rawRows[$key]['initial'] += round($initial * $ratio, 2);
                $rawRows[$key]['definitive'] += round($definitive * $ratio, 2);
                $rawRows[$key]['commitments'] += round($totalCommitments * $ratio, 2) + $directProrated;
                $rawRows[$key]['obligations'] += $distPayments + $directProrated;
                $rawRows[$key]['payments'] += $distPayments + $directProrated;
            }
        }

        // Sort by funding_source_code, then sifse_code
        $sorted = collect($rawRows)->sortBy([
            ['funding_source_code', 'asc'],
            ['sifse_code', 'asc'],
        ])->values();

        $trimester = $this->filterTrimester ?: (string) ceil(now()->month / 3);

        $this->expenseRows = $sorted->map(fn($row) => [
            'dane_code' => $daneCode,
            'year' => $year,
            'trimester' => $trimester,
            'funding_source_code' => $row['funding_source_code'],
            'sifse_code' => $row['sifse_code'],
            'initial' => $row['initial'],
            'definitive' => $row['definitive'],
            'commitments' => $row['commitments'],
            'obligations' => $row['obligations'],
            'payments' => $row['payments'],
        ])->toArray();

        $c = collect($this->expenseRows);
        $this->expenseTotals = [
            'initial' => $c->sum('initial'),
            'definitive' => $c->sum('definitive'),
            'commitments' => $c->sum('commitments'),
            'obligations' => $c->sum('obligations'),
            'payments' => $c->sum('payments'),
        ];
    }

    private function loadIncomes()
    {
        $year = (int) $this->filterYear;
        $daneCode = $this->school->dane_code ?? '';
        $trimester = $this->filterTrimester ?: (string) ceil(now()->month / 3);
        $cutoff = $this->getTrimesterCutoffDate();

        // Get all income budgets for this school/year
        $budgets = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->byType('income')
            ->with(['fundingSource'])
            ->get();

        $budgetIds = $budgets->pluck('id')->toArray();

        // --- Modifications up to cutoff per budget ---
        $additionsByBudget = [];
        $reductionsByBudget = [];
        if (!empty($budgetIds)) {
            $mods = \App\Models\BudgetModification::whereIn('budget_id', $budgetIds)
                ->where(function ($q) use ($cutoff) {
                    $q->where('document_date', '<=', $cutoff)
                      ->orWhere(function ($q2) use ($cutoff) {
                          $q2->whereNull('document_date')
                             ->where('created_at', '<=', $cutoff . ' 23:59:59');
                      });
                })
                ->get();

            foreach ($mods as $mod) {
                if ($mod->type === 'addition') {
                    $additionsByBudget[$mod->budget_id] = ($additionsByBudget[$mod->budget_id] ?? 0) + (float) $mod->amount;
                } else {
                    $reductionsByBudget[$mod->budget_id] = ($reductionsByBudget[$mod->budget_id] ?? 0) + (float) $mod->amount;
                }
            }
        }

        // --- Transfers up to cutoff per budget ---
        $creditsByBudget = [];
        $contracreditsByBudget = [];
        if (!empty($budgetIds)) {
            $transfers = \App\Models\BudgetTransfer::where(function ($q) use ($budgetIds) {
                    $q->whereIn('source_budget_id', $budgetIds)
                      ->orWhereIn('destination_budget_id', $budgetIds);
                })
                ->where(function ($q) use ($cutoff) {
                    $q->where('document_date', '<=', $cutoff)
                      ->orWhere(function ($q2) use ($cutoff) {
                          $q2->whereNull('document_date')
                             ->where('created_at', '<=', $cutoff . ' 23:59:59');
                      });
                })
                ->get();

            foreach ($transfers as $t) {
                if (in_array($t->destination_budget_id, $budgetIds)) {
                    $creditsByBudget[$t->destination_budget_id] = ($creditsByBudget[$t->destination_budget_id] ?? 0) + (float) $t->amount;
                }
                if (in_array($t->source_budget_id, $budgetIds)) {
                    $contracreditsByBudget[$t->source_budget_id] = ($contracreditsByBudget[$t->source_budget_id] ?? 0) + (float) $t->amount;
                }
            }
        }

        // --- Incomes up to cutoff grouped by funding_source_id ---
        $incomesByFunding = Income::forSchool($this->schoolId)
            ->forYear($year)
            ->where('date', '<=', $cutoff)
            ->selectRaw('funding_source_id, SUM(amount) as total_collected')
            ->groupBy('funding_source_id')
            ->pluck('total_collected', 'funding_source_id')
            ->toArray();

        // Group budgets by funding_source code
        $grouped = [];

        foreach ($budgets as $budget) {
            $fundingCode = $budget->fundingSource?->code ?? '';
            $fundingSourceId = $budget->funding_source_id;

            if (!isset($grouped[$fundingCode])) {
                $grouped[$fundingCode] = [
                    'funding_source_code' => $fundingCode,
                    'initial' => 0,
                    'definitive' => 0,
                    'collected' => 0,
                ];
            }

            $initial = (float) $budget->initial_amount;
            $additions = (float) ($additionsByBudget[$budget->id] ?? 0);
            $reductions = (float) ($reductionsByBudget[$budget->id] ?? 0);
            $credits = (float) ($creditsByBudget[$budget->id] ?? 0);
            $contracredits = (float) ($contracreditsByBudget[$budget->id] ?? 0);
            $definitive = $initial + $additions - $reductions + $credits - $contracredits;

            $grouped[$fundingCode]['initial'] += $initial;
            $grouped[$fundingCode]['definitive'] += $definitive;

            // Accumulate incomes for this funding source (avoid double-counting)
            if (!isset($grouped[$fundingCode]['_fs_counted'][$fundingSourceId])) {
                $grouped[$fundingCode]['collected'] += (float) ($incomesByFunding[$fundingSourceId] ?? 0);
                $grouped[$fundingCode]['_fs_counted'][$fundingSourceId] = true;
            }
        }

        // Also check for funding sources that have incomes but no budget
        foreach ($incomesByFunding as $fsId => $amount) {
            $fs = \App\Models\FundingSource::find($fsId);
            if (!$fs) continue;
            $code = $fs->code;
            if (!isset($grouped[$code])) {
                $grouped[$code] = [
                    'funding_source_code' => $code,
                    'initial' => 0,
                    'definitive' => 0,
                    'collected' => (float) $amount,
                ];
            }
        }

        $sorted = collect($grouped)
            ->map(function ($row) {
                unset($row['_fs_counted']);
                return $row;
            })
            ->sortBy('funding_source_code')
            ->values();

        $this->incomeRows = $sorted->map(fn($row) => [
            'dane_code' => $daneCode,
            'year' => $year,
            'trimester' => $trimester,
            'funding_source_code' => $row['funding_source_code'],
            'initial' => $row['initial'],
            'definitive' => $row['definitive'],
            'collected' => $row['collected'],
        ])->toArray();

        $c = collect($this->incomeRows);
        $this->incomeTotals = [
            'initial' => $c->sum('initial'),
            'definitive' => $c->sum('definitive'),
            'collected' => $c->sum('collected'),
        ];
    }

    public function getPeriodLabelProperty(): string
    {
        $trimesters = [
            '1' => 'PRIMER TRIMESTRE (Ene-Mar)',
            '2' => 'SEGUNDO TRIMESTRE (Abr-Jun)',
            '3' => 'TERCER TRIMESTRE (Jul-Sep)',
            '4' => 'CUARTO TRIMESTRE (Oct-Dic)',
        ];

        $t = $trimesters[$this->filterTrimester] ?? 'CONSOLIDADO';
        return "{$t} - {$this->filterYear}";
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.sifse-report');
    }
}

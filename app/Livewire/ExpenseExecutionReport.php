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

        // Pre-load payments per expense_distribution
        $distIds = $budgets->flatMap(fn($b) => $b->distributions->pluck('id'))->toArray();

        $paymentsByDist = [];
        if (!empty($distIds)) {
            $paymentsByDist = PaymentOrderExpenseLine::whereIn('expense_distribution_id', $distIds)
                ->whereHas('paymentOrder', fn($q) => $q->whereIn('status', ['approved', 'paid']))
                ->selectRaw('expense_distribution_id, SUM(total) as total_paid')
                ->groupBy('expense_distribution_id')
                ->pluck('total_paid', 'expense_distribution_id')
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
            $totalCommitments = (float) ($commitmentsByBudget[$budget->id] ?? 0);
            $fundingCode = $budget->fundingSource?->code ?? '';
            $fundingName = $budget->fundingSource?->name ?? '';

            $distributions = $budget->distributions;

            if ($distributions->isEmpty()) {
                // Budget sin distribuciones: mostrar con datos del budget item
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
                    'commitments' => $totalCommitments,
                    'obligations' => 0,
                    'payments' => 0,
                    'pending' => $definitive - $totalCommitments,
                ];
            } else {
                // Una fila por cada distribución (código de gasto)
                $totalDistAmount = $distributions->sum('amount');

                foreach ($distributions as $dist) {
                    $expCode = $dist->expenseCode;
                    $distPayments = (float) ($paymentsByDist[$dist->id] ?? 0);

                    // Prorratear los datos del presupuesto según proporción de la distribución
                    $ratio = $totalDistAmount > 0 ? (float) $dist->amount / $totalDistAmount : 0;

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
                        'commitments' => round($totalCommitments * $ratio, 2),
                        'obligations' => $distPayments,
                        'payments' => $distPayments,
                        'pending' => round($definitive * $ratio, 2) - round($totalCommitments * $ratio, 2),
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
        return "A DICIEMBRE 31 DE {$this->filterYear} CONSOLIDADO";
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.expense-execution-report');
    }
}

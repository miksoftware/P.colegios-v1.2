<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\Income;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

class IncomeExecutionReport extends Component
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

        // Get all income budgets for this school/year
        $budgets = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->byType('income')
            ->with([
                'budgetItem',
                'fundingSource',
                'modifications',
            ])
            ->orderBy('budget_item_id')
            ->get();

        // Pre-load total incomes (recaudos) per budget
        $budgetIds = $budgets->pluck('id')->toArray();

        // Income model links to funding_source_id, not budget_id directly.
        // We need to sum incomes per funding_source for this school/year
        $incomesByFundingSource = [];
        if (!empty($budgetIds)) {
            $fundingSourceIds = $budgets->pluck('funding_source_id')->unique()->filter()->toArray();
            if (!empty($fundingSourceIds)) {
                $incomesByFundingSource = Income::forSchool($this->schoolId)
                    ->forYear($year)
                    ->whereIn('funding_source_id', $fundingSourceIds)
                    ->selectRaw('funding_source_id, SUM(amount) as total')
                    ->groupBy('funding_source_id')
                    ->pluck('total', 'funding_source_id')
                    ->toArray();
            }
        }

        $this->rows = [];

        foreach ($budgets as $budget) {
            $initial = (float) $budget->initial_amount;
            $additions = (float) $budget->modifications->where('type', 'addition')->sum('amount');
            $reductions = (float) $budget->modifications->where('type', 'reduction')->sum('amount');
            $definitive = (float) $budget->current_amount;

            $fundingSourceId = $budget->funding_source_id;
            $recaudos = (float) ($incomesByFundingSource[$fundingSourceId] ?? 0);
            $pending = $definitive - $recaudos;

            $this->rows[] = [
                'budget_id' => $budget->id,
                'rubro_code' => $budget->budgetItem?->code ?? '',
                'rubro_name' => $budget->budgetItem?->name ?? '',
                'funding_source_code' => $budget->fundingSource?->code ?? '',
                'funding_source_name' => $budget->fundingSource?->name ?? '',
                'initial' => $initial,
                'additions' => $additions,
                'reductions' => $reductions,
                'definitive' => $definitive,
                'recaudos' => $recaudos,
                'pending' => $pending,
            ];
        }

        // Totals
        $c = collect($this->rows);
        $this->totals = [
            'initial' => $c->sum('initial'),
            'additions' => $c->sum('additions'),
            'reductions' => $c->sum('reductions'),
            'definitive' => $c->sum('definitive'),
            'recaudos' => $c->sum('recaudos'),
            'pending' => $c->sum('pending'),
        ];

        $this->dispatch('reportLoaded');
    }

    public function getPeriodLabelProperty(): string
    {
        return "A DICIEMBRE 31 DE {$this->filterYear}";
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.income-execution-report');
    }
}

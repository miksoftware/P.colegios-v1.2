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

        $budgetIds = $budgets->pluck('id')->toArray();

        $incomesByFundingSource = [];
        if (!empty($budgetIds)) {
            $fundingSourceIds = $budgets->pluck('funding_source_id')->unique()->filter()->toArray();
            if (!empty($fundingSourceIds)) {
                $query = Income::forSchool($this->schoolId)
                    ->whereIn('funding_source_id', $fundingSourceIds);

                if ($dateFrom && $dateTo) {
                    $query->whereBetween('date', [$dateFrom, $dateTo]);
                } else {
                    $query->forYear($year);
                }

                $incomesByFundingSource = $query->selectRaw('funding_source_id, SUM(amount) as total')
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
        if ($this->filterQuarter) {
            $labels = [1 => 'PRIMER', 2 => 'SEGUNDO', 3 => 'TERCER', 4 => 'CUARTO'];
            return "{$labels[(int)$this->filterQuarter]} TRIMESTRE DE {$this->filterYear}";
        }
        return "A DICIEMBRE 31 DE {$this->filterYear}";
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.income-execution-report');
    }
}

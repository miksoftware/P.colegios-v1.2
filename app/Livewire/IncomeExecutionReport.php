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
    public $filterSemester = '';

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
        $this->filterSemester = '';
        $this->loadReport();
    }

    public function updatedFilterSemester()
    {
        $this->filterQuarter = '';
        $this->loadReport();
    }

    public function loadReport()
    {
        $year = (int) $this->filterYear;
        $quarter = $this->filterQuarter ? (int) $this->filterQuarter : null;
        $semester = $this->filterSemester ? (int) $this->filterSemester : null;

        // Rango de fechas ACUMULADO: siempre desde enero 1 hasta el fin del período.
        $dateFrom = null;
        $dateTo = null;
        if ($quarter) {
            $dateFrom = "{$year}-01-01";
            $lastMonth = $quarter * 3;
            $dateTo = \Carbon\Carbon::parse("{$year}-{$lastMonth}-01")->endOfMonth()->format('Y-m-d');
        } elseif ($semester) {
            $dateFrom = "{$year}-01-01";
            $lastMonth = $semester * 6;
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
            $q = (int) $this->filterQuarter;
            $endMonths = [1 => 'MARZO', 2 => 'JUNIO', 3 => 'SEPTIEMBRE', 4 => 'DICIEMBRE'];
            $endDays   = [1 => '31', 2 => '30', 3 => '30', 4 => '31'];
            return "DE ENERO 01 AL {$endDays[$q]} DE {$endMonths[$q]} DE {$this->filterYear} (ACUMULADO AL {$q}\u00b0 TRIMESTRE)";
        }
        if ($this->filterSemester) {
            $s = (int) $this->filterSemester;
            $label = $s === 1 ? 'DE ENERO 01 AL 30 DE JUNIO' : 'DE ENERO 01 AL 31 DE DICIEMBRE';
            $sem   = $s === 1 ? 'PRIMER' : 'SEGUNDO';
            return "{$label} DE {$this->filterYear} (ACUMULADO AL {$sem} SEMESTRE)";
        }
        return "A DICIEMBRE 31 DE {$this->filterYear}";
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.income-execution-report');
    }
}

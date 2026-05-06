<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\Income;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

class PacIncomeReport extends Component
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

        // Pre-load incomes del año, agrupados por fuente de financiación y mes
        $incomes = Income::forSchool($this->schoolId)
            ->forYear($year)
            ->with('fundingSource.budgetItem')
            ->get();

        $incomesByFsMonth = [];
        $incomeObservations = [];

        foreach ($incomes as $income) {
            $fsId  = $income->funding_source_id;
            $month = (int) $income->date->format('m');

            if (!isset($incomesByFsMonth[$fsId])) {
                $incomesByFsMonth[$fsId] = [];
            }
            $incomesByFsMonth[$fsId][$month] = ($incomesByFsMonth[$fsId][$month] ?? 0) + (float) $income->amount;

            if (!empty($income->description)) {
                $incomeObservations[$fsId][] = $income->description;
            }
        }

        // Traer presupuestos de ingresos del colegio para construir las filas por rubro
        $budgets = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->byType('income')
            ->with(['budgetItem', 'fundingSource'])
            ->orderBy('budget_item_id')
            ->get();

        // Mapa fundingSourceId → {code, name} (del budgetItem), para cubrir el caso
        // de ingresos con fuentes que no tienen presupuesto registrado.
        $fsToRubro = [];
        foreach ($budgets as $budget) {
            if ($budget->funding_source_id) {
                $fsToRubro[$budget->funding_source_id] = [
                    'code' => $budget->budgetItem?->code ?? '',
                    'name' => $budget->budgetItem?->name ?? '',
                ];
            }
        }

        $conceptRows = [];

        // Construir filas: una por rubro (budgetItem) del presupuesto de ingresos
        foreach ($budgets as $budget) {
            $budgetItem = $budget->budgetItem;
            $code = $budgetItem?->code ?? '';
            $name = $budgetItem?->name ?? '';
            $key = $code ?: 'no-code-' . $budget->id;

            $fsId = $budget->funding_source_id;
            $monthlyData = $incomesByFsMonth[$fsId] ?? [];

            if (!isset($conceptRows[$key])) {
                $months = [];
                for ($m = 1; $m <= 12; $m++) {
                    $months[$m] = 0;
                }
                $conceptRows[$key] = [
                    'code' => $code,
                    'name' => $name,
                    'months' => $months,
                    'total' => 0,
                    'observations' => '',
                ];
            }

            for ($m = 1; $m <= 12; $m++) {
                $conceptRows[$key]['months'][$m] += (float) ($monthlyData[$m] ?? 0);
            }

            if (!empty($incomeObservations[$fsId])) {
                $existing = $conceptRows[$key]['observations'];
                $new = implode('. ', array_unique($incomeObservations[$fsId]));
                $conceptRows[$key]['observations'] = $existing
                    ? $existing . '. ' . $new
                    : $new;
            }
        }

        // Cubrir ingresos cuya fuente NO tiene presupuesto (no aparecen en la iteración anterior)
        // Los agrupamos bajo el rubro del BudgetItem de la fuente de financiación.
        foreach ($incomesByFsMonth as $fsId => $monthly) {
            if (isset($fsToRubro[$fsId])) continue; // Ya cubiertos

            $income  = $incomes->firstWhere('funding_source_id', $fsId);
            $fs      = $income?->fundingSource;
            $bi      = $fs?->budgetItem;
            $code    = $bi?->code ?? '';
            $name    = $bi?->name ?? ($fs?->name ?? 'Sin rubro');
            $key     = $code ?: 'fs-' . $fsId;

            if (!isset($conceptRows[$key])) {
                $months = [];
                for ($m = 1; $m <= 12; $m++) $months[$m] = 0;
                $conceptRows[$key] = [
                    'code' => $code,
                    'name' => $name,
                    'months' => $months,
                    'total' => 0,
                    'observations' => '',
                ];
            }
            for ($m = 1; $m <= 12; $m++) {
                $conceptRows[$key]['months'][$m] += (float) ($monthly[$m] ?? 0);
            }
            if (!empty($incomeObservations[$fsId])) {
                $existing = $conceptRows[$key]['observations'];
                $new = implode('. ', array_unique($incomeObservations[$fsId]));
                $conceptRows[$key]['observations'] = $existing ? $existing . '. ' . $new : $new;
            }
        }

        // Calculate totals per row
        foreach ($conceptRows as &$row) {
            $row['total'] = array_sum($row['months']);
        }
        unset($row);

        ksort($conceptRows);
        $this->rows = array_values($conceptRows);

        // Totals
        $c = collect($this->rows);
        $monthTotals = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthTotals[$m] = $c->sum(fn($r) => $r['months'][$m]);
        }

        $this->totals = [
            'months' => $monthTotals,
            'total' => $c->sum('total'),
        ];

        $this->dispatch('reportLoaded');
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
        return view('livewire.pac-income-report');
    }
}

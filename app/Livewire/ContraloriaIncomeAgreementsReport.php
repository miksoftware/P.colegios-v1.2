<?php

namespace App\Livewire;

use App\Models\BudgetModification;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class ContraloriaIncomeAgreementsReport extends Component
{
    public $schoolId;
    public $school;

    public $filterYear;

    public $rows = [];

    public function mount()
    {
        abort_if(!auth()->user()->can('reports.view'), 403);

        $this->schoolId = session('selected_school_id');
        if (!$this->schoolId) {
            session()->flash('error', 'Seleccione un colegio.');
            $this->redirect(route('dashboard'));
            return;
        }

        $this->school     = School::find($this->schoolId);
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

        $modifications = BudgetModification::whereHas('budget', function ($q) use ($year) {
                $q->where('school_id', $this->schoolId)
                  ->where('fiscal_year', $year)
                  ->where('type', 'income');
            })
            ->with(['budget.budgetItem'])
            ->orderBy('document_date')
            ->orderBy('created_at')
            ->get();

        $this->rows = $modifications->map(fn($mod) => [
            'codigo_rubro' => $mod->budget->budgetItem->code ?? 'N/A',
            'nombre_rubro' => $mod->budget->budgetItem->name ?? 'N/A',
            'acto_adm'     => $mod->document_number ?: 'REGISTRO SISTEMA',
            'fecha'        => $mod->document_date
                ? $mod->document_date->format('Y/m/d')
                : $mod->created_at->format('Y/m/d'),
            'adicion'      => $mod->type === 'addition' ? (float) $mod->amount : 0,
            'reduccion'    => $mod->type === 'reduction' ? (float) $mod->amount : 0,
            'motivo'       => $mod->reason,
        ])->toArray();
    }

    public function render()
    {
        return view('livewire.contraloria-income-agreements-report');
    }
}

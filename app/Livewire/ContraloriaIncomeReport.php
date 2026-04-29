<?php

namespace App\Livewire;

use App\Models\Income;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class ContraloriaIncomeReport extends Component
{
    public $schoolId;
    public $school;

    public $filterYear;

    public $rows = [];

    public function mount(): void
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

    public function updatedFilterYear(): void
    {
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $year = (int) $this->filterYear;

        $incomes = Income::forSchool($this->schoolId)
            ->forYear($year)
            ->with([
                'fundingSource.budgetItem',
                'bankAccounts.bankAccount',
            ])
            ->orderBy('date')
            ->get();

        $this->rows = $incomes->map(function (Income $income) {
            $fundingSource = $income->fundingSource;
            $budgetItem    = $fundingSource?->budgetItem;

            $codigoPresupuestal = $budgetItem?->code ?? $fundingSource?->code ?? '';

            $firstBank   = $income->bankAccounts->first();
            $cuentaBancaria = $firstBank?->bankAccount?->account_number ?? 'N/D';

            return [
                'codigo_presupuestal' => $codigoPresupuestal,
                'fecha_recaudo'       => $income->date?->format('Y/m/d') ?? '',
                'numero_recibo'       => 0,
                'recibido_de'         => strtoupper($income->name ?? ''),
                'concepto_recaudo'    => strtoupper($income->description ?? $fundingSource?->name ?? ''),
                'valor'               => (float) $income->amount,
                'cuenta_bancaria'     => $cuentaBancaria,
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.contraloria-income-report');
    }
}

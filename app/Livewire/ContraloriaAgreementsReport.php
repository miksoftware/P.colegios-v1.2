<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\BudgetModification;
use App\Models\BudgetTransfer;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class ContraloriaAgreementsReport extends Component
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

        // ── Modificaciones sobre presupuestos de GASTO ──────────────────────
        $modifications = BudgetModification::whereHas('budget', function ($q) use ($year) {
                $q->where('school_id', $this->schoolId)
                  ->where('fiscal_year', $year)
                  ->where('type', 'expense');
            })
            ->with(['budget.budgetItem'])
            ->orderBy('document_date')
            ->orderBy('created_at')
            ->get();

        // ── Traslados (crédito / contracrédito) de presupuestos de GASTO ───
        $transfers = BudgetTransfer::where('school_id', $this->schoolId)
            ->where('fiscal_year', $year)
            ->with([
                'sourceBudget.budgetItem',
                'destinationBudget.budgetItem',
            ])
            ->orderBy('document_date')
            ->orderBy('created_at')
            ->get();

        $rows = [];

        foreach ($modifications as $mod) {
            $sortDate = $mod->document_date ?? $mod->created_at->toDate();
            $rows[] = [
                'fecha'          => $mod->document_date
                    ? $mod->document_date->format('Y/m/d')
                    : $mod->created_at->format('Y/m/d'),
                'fecha_sort'     => $sortDate,
                'codigo_rubro'   => $mod->budget->budgetItem->code ?? 'N/A',
                'nombre_rubro'   => $mod->budget->budgetItem->name ?? 'N/A',
                'acto_adm'       => $mod->document_number ?: 'REGISTRO SISTEMA',
                'adicion'        => $mod->type === 'addition' ? (float) $mod->amount : 0,
                'reduccion'      => $mod->type === 'reduction' ? (float) $mod->amount : 0,
                'credito'        => 0,
                'contracredito'  => 0,
                'aplazamiento'   => 0,
                'desaplazamiento'=> 0,
                'motivo'         => $mod->reason,
            ];
        }

        foreach ($transfers as $transfer) {
            $sortDate = $transfer->document_date ?? $transfer->created_at->toDate();
            $acto     = $transfer->document_number ?: 'REGISTRO SISTEMA';
            $fecha    = $transfer->document_date
                ? $transfer->document_date->format('Y/m/d')
                : $transfer->created_at->format('Y/m/d');

            // Fila del ORIGEN → Contracrédito (sale del rubro)
            $rows[] = [
                'fecha'          => $fecha,
                'fecha_sort'     => $sortDate,
                'codigo_rubro'   => $transfer->sourceBudget->budgetItem->code ?? 'N/A',
                'nombre_rubro'   => $transfer->sourceBudget->budgetItem->name ?? 'N/A',
                'acto_adm'       => $acto,
                'adicion'        => 0,
                'reduccion'      => 0,
                'credito'        => 0,
                'contracredito'  => (float) $transfer->amount,
                'aplazamiento'   => 0,
                'desaplazamiento'=> 0,
                'motivo'         => $transfer->reason,
            ];

            // Fila del DESTINO → Crédito (entra al rubro)
            $rows[] = [
                'fecha'          => $fecha,
                'fecha_sort'     => $sortDate,
                'codigo_rubro'   => $transfer->destinationBudget->budgetItem->code ?? 'N/A',
                'nombre_rubro'   => $transfer->destinationBudget->budgetItem->name ?? 'N/A',
                'acto_adm'       => $acto,
                'adicion'        => 0,
                'reduccion'      => 0,
                'credito'        => (float) $transfer->amount,
                'contracredito'  => 0,
                'aplazamiento'   => 0,
                'desaplazamiento'=> 0,
                'motivo'         => $transfer->reason,
            ];
        }

        // Ordenar por fecha
        usort($rows, fn($a, $b) => $a['fecha_sort'] <=> $b['fecha_sort']);

        // Quitar clave de sort (no se necesita en la vista)
        $this->rows = array_map(function ($r) {
            unset($r['fecha_sort']);
            return $r;
        }, $rows);
    }

    public function render()
    {
        return view('livewire.contraloria-agreements-report');
    }
}

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
            ->with([
                'budget.budgetItem',
                'budget.distributions.expenseCode',
            ])
            ->orderBy('document_date')
            ->orderBy('created_at')
            ->get();

        // ── Traslados (crédito / contracrédito) de presupuestos de GASTO ───
        $transfers = BudgetTransfer::where('school_id', $this->schoolId)
            ->where('fiscal_year', $year)
            ->with([
                'sourceBudget.budgetItem',
                'sourceBudget.distributions.expenseCode',
                'destinationBudget.budgetItem',
                'destinationBudget.distributions.expenseCode',
            ])
            ->orderBy('document_date')
            ->orderBy('created_at')
            ->get();

        $rows = [];

        foreach ($modifications as $mod) {
            $sortDate = $mod->document_date ?? $mod->created_at->toDate();
            $fecha    = $mod->document_date
                ? $mod->document_date->format('Y/m/d')
                : $mod->created_at->format('Y/m/d');
            $acto     = $mod->document_number ?: 'REGISTRO SISTEMA';
            $adicion  = $mod->type === 'addition' ? (float) $mod->amount : 0;
            $reduccion = $mod->type === 'reduction' ? (float) $mod->amount : 0;

            // Desglosar por cada distribución (expense_code) del budget afectado,
            // prorrateando el monto proporcional al amount de la distribución.
            foreach ($this->splitByDistributions($mod->budget, (float) $mod->amount) as $piece) {
                $rows[] = [
                    'fecha'           => $fecha,
                    'fecha_sort'      => $sortDate,
                    'codigo_rubro'    => $piece['code'],
                    'nombre_rubro'    => $piece['name'],
                    'acto_adm'        => $acto,
                    'adicion'         => $adicion > 0 ? $piece['amount'] : 0,
                    'reduccion'       => $reduccion > 0 ? $piece['amount'] : 0,
                    'credito'         => 0,
                    'contracredito'   => 0,
                    'aplazamiento'    => 0,
                    'desaplazamiento' => 0,
                    'motivo'          => $mod->reason,
                ];
            }
        }

        foreach ($transfers as $transfer) {
            $sortDate = $transfer->document_date ?? $transfer->created_at->toDate();
            $acto     = $transfer->document_number ?: 'REGISTRO SISTEMA';
            $fecha    = $transfer->document_date
                ? $transfer->document_date->format('Y/m/d')
                : $transfer->created_at->format('Y/m/d');
            $amount   = (float) $transfer->amount;

            // Contracrédito: sale del rubro origen → desglosar por sus distribuciones
            foreach ($this->splitByDistributions($transfer->sourceBudget, $amount) as $piece) {
                $rows[] = [
                    'fecha'           => $fecha,
                    'fecha_sort'      => $sortDate,
                    'codigo_rubro'    => $piece['code'],
                    'nombre_rubro'    => $piece['name'],
                    'acto_adm'        => $acto,
                    'adicion'         => 0,
                    'reduccion'       => 0,
                    'credito'         => 0,
                    'contracredito'   => $piece['amount'],
                    'aplazamiento'    => 0,
                    'desaplazamiento' => 0,
                    'motivo'          => $transfer->reason,
                ];
            }

            // Crédito: entra al rubro destino → desglosar por sus distribuciones
            foreach ($this->splitByDistributions($transfer->destinationBudget, $amount) as $piece) {
                $rows[] = [
                    'fecha'           => $fecha,
                    'fecha_sort'      => $sortDate,
                    'codigo_rubro'    => $piece['code'],
                    'nombre_rubro'    => $piece['name'],
                    'acto_adm'        => $acto,
                    'adicion'         => 0,
                    'reduccion'       => 0,
                    'credito'         => $piece['amount'],
                    'contracredito'   => 0,
                    'aplazamiento'    => 0,
                    'desaplazamiento' => 0,
                    'motivo'          => $transfer->reason,
                ];
            }
        }

        // Ordenar por fecha
        usort($rows, fn($a, $b) => $a['fecha_sort'] <=> $b['fecha_sort']);

        // Quitar clave de sort (no se necesita en la vista)
        $this->rows = array_map(function ($r) {
            unset($r['fecha_sort']);
            return $r;
        }, $rows);
    }

    /**
     * Parte un monto de un budget entre sus distribuciones (rubros de gasto),
     * devolviendo una colección [['code','name','amount']].
     * Si el budget no tiene distribuciones, retorna una sola entrada con el
     * budget_item como fallback.
     */
    private function splitByDistributions(?Budget $budget, float $amount): array
    {
        if (!$budget) {
            return [[
                'code'   => 'N/A',
                'name'   => 'N/A',
                'amount' => $amount,
            ]];
        }

        $distributions = $budget->distributions ?? collect();
        $totalDistAmount = (float) $distributions->sum('amount');

        if ($distributions->isEmpty() || $totalDistAmount <= 0) {
            return [[
                'code'   => $budget->budgetItem->code ?? 'N/A',
                'name'   => $budget->budgetItem->name ?? 'N/A',
                'amount' => $amount,
            ]];
        }

        $pieces = [];
        foreach ($distributions as $dist) {
            $ec    = $dist->expenseCode;
            $ratio = (float) $dist->amount / $totalDistAmount;
            $pieces[] = [
                'code'   => $ec?->code ?? ($budget->budgetItem->code ?? 'N/A'),
                'name'   => $ec?->name ?? ($budget->budgetItem->name ?? 'N/A'),
                'amount' => round($amount * $ratio, 2),
            ];
        }
        return $pieces;
    }

    public function render()
    {
        return view('livewire.contraloria-agreements-report');
    }
}

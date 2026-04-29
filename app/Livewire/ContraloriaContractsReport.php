<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\School;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class ContraloriaContractsReport extends Component
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

        $contracts = Contract::forSchool($this->schoolId)
            ->forYear($year)
            ->with([
                'supplier',
                'convocatoria.distributionDetails.expenseDistribution.expenseCode',
                'rps' => fn($q) => $q->where('status', 'active')->orderBy('rp_number'),
                'rps.cdp',
            ])
            ->orderBy('contract_number')
            ->get();

        $this->rows = $contracts->map(function (Contract $contract) {
            $rps  = $contract->rps; // already filtered to active
            $conv = $contract->convocatoria;

            // ── Expense codes ─────────────────────────────────────────
            $expenseCodes = collect();
            if ($conv && $conv->distributionDetails->isNotEmpty()) {
                $expenseCodes = $conv->distributionDetails
                    ->map(fn($dd) => $dd->expenseDistribution?->expenseCode)
                    ->filter()
                    ->unique('id');
            }

            if ($expenseCodes->isEmpty()) {
                // Fallback: use first RP's CDP budget item code
                $firstCdp = $rps->first()?->cdp;
                if ($firstCdp?->budgetItem) {
                    // Build a simple object to unify the interface
                    $bi = $firstCdp->budgetItem;
                    $codigoRubro = $bi->code ?? '';
                    $nombreRubro = $bi->name ?? '';
                } else {
                    $codigoRubro = '';
                    $nombreRubro = '';
                }
            } else {
                $codigoRubro = $expenseCodes->pluck('code')->filter()->implode(', ');
                $nombreRubro = $expenseCodes->pluck('name')->filter()->implode(', ');
            }

            // ── CDPs (from each active RP's CDP, deduplicated) ───────
            $cdps = $rps
                ->map(fn($rp) => $rp->cdp)
                ->filter()
                ->unique('id')
                ->sortBy('cdp_number')
                ->values();

            $numCdp   = $cdps->pluck('cdp_number')->implode(', ');
            $fechaCdp = $cdps->first()?->created_at?->format('Y/m/d') ?? 'N/D';
            $valorCdp = (float) $cdps->sum('total_amount');

            // ── RPs ───────────────────────────────────────────────────
            $numRp   = $rps->pluck('rp_number')->implode(', ');
            $fechaRp = $rps->first()?->created_at?->format('Y/m/d') ?? 'N/D';
            $valorRp = (float) $rps->sum('total_amount');

            // ── Supplier ─────────────────────────────────────────────
            $supplier     = $contract->supplier;
            $beneficiario = $supplier?->full_name ?? 'N/D';
            $nit          = $supplier?->document_number ?? '';

            return [
                'codigo_rubro'  => $codigoRubro,
                'nombre_rubro'  => $nombreRubro,
                'num_cdp'       => $numCdp,
                'fecha_cdp'     => $fechaCdp,
                'valor_cdp'     => $valorCdp,
                'num_rp'        => $numRp,
                'fecha_rp'      => $fechaRp,
                'valor_rp'      => $valorRp,
                'beneficiario'  => $beneficiario,
                'nit'           => $nit,
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.contraloria-contracts-report');
    }
}

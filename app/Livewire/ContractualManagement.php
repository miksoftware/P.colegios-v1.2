<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\Cdp;
use App\Models\CdpFundingSource;
use App\Models\Contract;
use App\Models\ContractRp;
use App\Models\Convocatoria;
use App\Models\FundingSource;
use App\Models\RpFundingSource;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class ContractualManagement extends Component
{
    use WithPagination;

    public $schoolId;
    public $filterYear;
    public $filterStatus = '';
    public $search = '';

    // Vista principal
    public $currentView = 'list'; // list | create | detail

    // Detalle
    public $contractId = null;
    public $contract = null;

    // ── Formulario de creación ────────────────────────────────
    public $selectedConvocatoriaId = '';
    public $contractNumber = '';
    public $contractingModality = '';
    public $executionPlace = '';
    public $startDate = '';
    public $endDate = '';
    public $durationDays = 0;
    public $contractObject = '';
    public $contractJustification = '';
    public $paymentMethod = 'single';
    public $supervisorId = '';
    public $contractSubtotal = '';
    public $contractIva = '';
    public $contractTotal = '';

    // Datos del proveedor (auto-fill)
    public $supplierData = [];

    // CDPs y asignación de RPs
    public $cdpsData = [];
    public $rpAssignments = []; // array indexed by cdp_id

    // Datos auxiliares
    public $awardedConvocatorias = [];
    public $supervisors = [];

    // Modal Cambio de Estado
    public $showStatusModal = false;
    public $newStatus = '';

    // Modal Eliminar
    public $showDeleteModal = false;

    protected $queryString = [
        'filterYear'   => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'search'       => ['except' => ''],
    ];

    // ── Mount ────────────────────────────────────────────────
    public function mount($convocatoria_id = null)
    {
        abort_if(!auth()->user()->can('contractual.view'), 403);

        $this->schoolId = session('selected_school_id');
        if (!$this->schoolId) {
            session()->flash('error', 'Debe seleccionar un colegio.');
            $this->redirect(route('dashboard'));
            return;
        }

        $this->filterYear = date('Y');

        // Si viene del precontractual con convocatoria preseleccionada
        if ($convocatoria_id) {
            $this->openCreateView($convocatoria_id);
        }
    }

    public function updatingSearch()   { $this->resetPage(); }
    public function updatingFilterYear() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }

    // ══════════════════════════════════════════════════════════
    // LISTADO
    // ══════════════════════════════════════════════════════════

    public function getContractsProperty()
    {
        return Contract::with(['convocatoria', 'supplier', 'supervisor', 'rps'])
            ->forSchool($this->schoolId)
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear))
            ->when($this->filterStatus, fn($q) => $q->byStatus($this->filterStatus))
            ->when($this->search, fn($q) => $q->search($this->search))
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function getSummaryProperty()
    {
        $base = Contract::forSchool($this->schoolId)
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear));

        return [
            'total'        => (clone $base)->count(),
            'draft'        => (clone $base)->byStatus('draft')->count(),
            'active'       => (clone $base)->byStatus('active')->count(),
            'in_execution' => (clone $base)->byStatus('in_execution')->count(),
            'completed'    => (clone $base)->byStatus('completed')->count(),
            'total_value'  => (clone $base)->sum('total'),
        ];
    }

    // ══════════════════════════════════════════════════════════
    // DETALLE
    // ══════════════════════════════════════════════════════════

    public function viewDetail($id)
    {
        // Verificar que el contrato existe y pertenece al colegio
        $exists = Contract::forSchool($this->schoolId)->where('id', $id)->exists();
        if (!$exists) {
            $this->dispatch('toast', message: 'Contrato no encontrado.', type: 'error');
            return;
        }

        $this->contractId = $id;
        $this->currentView = 'detail';
    }

    public function backToList()
    {
        $this->currentView = 'list';
        $this->contract = null;
        $this->contractId = null;
        $this->resetCreateForm();
    }

    // ══════════════════════════════════════════════════════════
    // CREAR CONTRATO
    // ══════════════════════════════════════════════════════════

    public function openCreateView($convocatoriaId = null)
    {
        if (!auth()->user()->can('contractual.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear contratos.', type: 'error');
            return;
        }

        $this->resetCreateForm();
        $this->loadAuxiliaryData();

        $this->contractNumber = Contract::getNextContractNumber($this->schoolId, (int) $this->filterYear);

        if ($convocatoriaId) {
            $this->selectedConvocatoriaId = $convocatoriaId;
            $this->onConvocatoriaSelected();
        }

        $this->currentView = 'create';
    }

    public function loadAuxiliaryData()
    {
        // Convocatorias adjudicadas que NO tienen contrato aún
        $this->awardedConvocatorias = Convocatoria::with(['selectedProposal.supplier', 'cdps.budgetItem', 'cdps.fundingSources.fundingSource'])
            ->forSchool($this->schoolId)
            ->forYear((int) $this->filterYear)
            ->byStatus('awarded')
            ->doesntHave('contract')
            ->orderBy('convocatoria_number')
            ->get()
            ->map(fn($c) => [
                'id'     => $c->id,
                'number' => $c->formatted_number,
                'object' => $c->object,
                'total'  => $c->selectedProposal?->total ?? 0,
            ])
            ->toArray();

        // Supervisores (usuarios del colegio)
        $this->supervisors = User::whereHas('schools', fn($q) => $q->where('schools.id', $this->schoolId))
            ->orderBy('name')
            ->get()
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name])
            ->toArray();
    }

    public function onConvocatoriaSelected()
    {
        if (!$this->selectedConvocatoriaId) {
            $this->supplierData = [];
            $this->cdpsData = [];
            $this->rpAssignments = [];
            $this->contractObject = '';
            $this->contractJustification = '';
            $this->contractSubtotal = '';
            $this->contractIva = '';
            $this->contractTotal = '';
            return;
        }

        $convocatoria = Convocatoria::with([
            'selectedProposal.supplier.department',
            'selectedProposal.supplier.municipality',
            'cdps.budgetItem',
            'cdps.fundingSources.fundingSource',
        ])->findOrFail($this->selectedConvocatoriaId);

        // Auto-fill objeto y justificación
        $this->contractObject = $convocatoria->object;
        $this->contractJustification = $convocatoria->justification ?? '';

        // Auto-fill proveedor (del ganador)
        $proposal = $convocatoria->selectedProposal;
        if ($proposal && $proposal->supplier) {
            $supplier = $proposal->supplier;
            $this->supplierData = [
                'id'         => $supplier->id,
                'name'       => $supplier->full_name,
                'document'   => $supplier->full_document,
                'address'    => $supplier->address ?? 'No registrada',
                'municipality' => $supplier->city ?? 'No registrado',
                'phone'      => $supplier->phone ?? $supplier->mobile ?? 'No registrado',
                'tax_regime' => $supplier->tax_regime ? (Supplier::TAX_REGIMES[$supplier->tax_regime] ?? $supplier->tax_regime) : 'No registrado',
            ];

            // Auto-fill valor del contrato
            $this->contractSubtotal = $proposal->subtotal;
            $this->contractIva = $proposal->iva ?? 0;
            $this->contractTotal = $proposal->total;
        }

        // Cargar CDPs con sus fuentes
        $this->cdpsData = [];
        $this->rpAssignments = [];

        foreach ($convocatoria->cdps as $cdp) {
            $cdpFundingSources = [];
            foreach ($cdp->fundingSources as $fs) {
                $cdpFundingSources[] = [
                    'id'              => $fs->id,
                    'funding_source_id' => $fs->funding_source_id,
                    'name'            => $fs->fundingSource->name ?? 'Sin nombre',
                    'amount'          => (float) $fs->amount,
                    'budget_id'       => $fs->budget_id,
                ];
            }

            $this->cdpsData[] = [
                'id'              => $cdp->id,
                'cdp_number'      => $cdp->formatted_number,
                'budget_item'     => $cdp->budgetItem->name ?? 'N/A',
                'budget_item_code' => $cdp->budgetItem->code ?? '',
                'total_amount'    => (float) $cdp->total_amount,
                'funding_sources' => $cdpFundingSources,
            ];

            // Pre-inicializar la asignación de RP para este CDP
            $rpFundingSources = [];
            foreach ($cdpFundingSources as $fs) {
                $rpFundingSources[] = [
                    'funding_source_id' => $fs['funding_source_id'],
                    'name'              => $fs['name'],
                    'available'         => $fs['amount'],
                    'amount'            => $fs['amount'], // por defecto, asignar todo
                    'budget_id'         => $fs['budget_id'],
                    'bank_account_number' => '',
                    'bank_name'         => '',
                ];
            }

            $this->rpAssignments[$cdp->id] = [
                'rp_number'       => ContractRp::getNextRpNumber($this->schoolId, (int) $this->filterYear),
                'funding_sources' => $rpFundingSources,
            ];
        }
    }

    public function updatedStartDate()
    {
        $this->calculateDuration();
    }

    public function updatedEndDate()
    {
        $this->calculateDuration();
    }

    public function calculateDuration()
    {
        if ($this->startDate && $this->endDate) {
            try {
                $start = \Carbon\Carbon::parse($this->startDate);
                $end   = \Carbon\Carbon::parse($this->endDate);
                $this->durationDays = max(0, $start->diffInDays($end));
            } catch (\Exception $e) {
                $this->durationDays = 0;
            }
        } else {
            $this->durationDays = 0;
        }
    }

    public function saveContract()
    {
        if (!auth()->user()->can('contractual.create')) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }

        // Validación
        $this->validate([
            'selectedConvocatoriaId' => 'required|exists:convocatorias,id',
            'contractingModality'    => 'required|in:' . implode(',', array_keys(Contract::MODALITIES)),
            'executionPlace'         => 'nullable|string|max:255',
            'startDate'              => 'required|date',
            'endDate'                => 'required|date|after_or_equal:startDate',
            'paymentMethod'          => 'required|in:single,partial',
            'contractSubtotal'       => 'required|numeric|min:0',
            'contractIva'            => 'nullable|numeric|min:0',
            'contractTotal'          => 'required|numeric|min:0',
        ], [
            'selectedConvocatoriaId.required' => 'Debe seleccionar una convocatoria.',
            'contractingModality.required'    => 'Debe seleccionar la modalidad de contratación.',
            'startDate.required'              => 'La fecha de inicio es obligatoria.',
            'endDate.required'                => 'La fecha de terminación es obligatoria.',
            'endDate.after_or_equal'          => 'La fecha de terminación debe ser posterior o igual a la de inicio.',
            'paymentMethod.required'          => 'Debe seleccionar la forma de pago.',
            'contractSubtotal.required'       => 'El subtotal es obligatorio.',
            'contractTotal.required'          => 'El total es obligatorio.',
        ]);

        // Validar que cada RP tiene al menos una fuente con monto > 0
        foreach ($this->rpAssignments as $cdpId => $rpData) {
            $totalRp = collect($rpData['funding_sources'])->sum(fn($fs) => (float) ($fs['amount'] ?? 0));
            if ($totalRp <= 0) {
                $this->dispatch('toast', message: 'Cada RP debe tener al menos un monto asignado por fuente de financiación.', type: 'error');
                return;
            }

            // Validar que no exceda el disponible del CDP
            foreach ($rpData['funding_sources'] as $fs) {
                $amount = (float) ($fs['amount'] ?? 0);
                $available = (float) ($fs['available'] ?? 0);
                if ($amount > $available) {
                    $this->dispatch('toast', message: "El monto del RP para la fuente \"{$fs['name']}\" excede el disponible del CDP.", type: 'error');
                    return;
                }
            }
        }

        DB::beginTransaction();
        try {
            $year = (int) $this->filterYear;

            // Crear contrato
            $contract = Contract::create([
                'school_id'            => $this->schoolId,
                'convocatoria_id'      => $this->selectedConvocatoriaId,
                'contract_number'      => Contract::getNextContractNumber($this->schoolId, $year),
                'fiscal_year'          => $year,
                'contracting_modality' => $this->contractingModality,
                'execution_place'      => $this->executionPlace ?? '',
                'start_date'           => $this->startDate,
                'end_date'             => $this->endDate,
                'duration_days'        => $this->durationDays,
                'object'               => $this->contractObject,
                'justification'        => $this->contractJustification,
                'supplier_id'          => $this->supplierData['id'],
                'supervisor_id'        => $this->supervisorId ?: null,
                'subtotal'             => (float) $this->contractSubtotal,
                'iva'                  => (float) ($this->contractIva ?? 0),
                'total'                => (float) $this->contractTotal,
                'payment_method'       => $this->paymentMethod,
                'status'               => 'draft',
                'created_by'           => auth()->id(),
            ]);

            // Crear RPs para cada CDP
            foreach ($this->rpAssignments as $cdpId => $rpData) {
                $totalRp = collect($rpData['funding_sources'])->sum(fn($fs) => (float) ($fs['amount'] ?? 0));

                $rp = ContractRp::create([
                    'contract_id'  => $contract->id,
                    'cdp_id'       => $cdpId,
                    'rp_number'    => ContractRp::getNextRpNumber($this->schoolId, $year),
                    'fiscal_year'  => $year,
                    'total_amount' => $totalRp,
                    'status'       => 'active',
                    'created_by'   => auth()->id(),
                ]);

                // Crear detalle de fuentes de financiación del RP
                foreach ($rpData['funding_sources'] as $fs) {
                    $amount = (float) ($fs['amount'] ?? 0);
                    if ($amount > 0) {
                        RpFundingSource::create([
                            'contract_rp_id'    => $rp->id,
                            'funding_source_id' => $fs['funding_source_id'],
                            'budget_id'         => $fs['budget_id'] ?? null,
                            'amount'            => $amount,
                            'bank_account_number' => $fs['bank_account_number'] ?? null,
                            'bank_name'         => $fs['bank_name'] ?? null,
                        ]);
                    }
                }

                // Marcar CDP como utilizado
                Cdp::where('id', $cdpId)->update(['status' => 'used']);
            }

            DB::commit();

            $this->dispatch('toast', message: 'Contrato creado exitosamente.', type: 'success');
            $this->viewDetail($contract->id);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error al crear contrato: ' . $e->getMessage(), type: 'error');
        }
    }

    // ══════════════════════════════════════════════════════════
    // CAMBIO DE ESTADO
    // ══════════════════════════════════════════════════════════

    public function openStatusModal($status = null)
    {
        if (!auth()->user()->can('contractual.edit')) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }
        $this->newStatus = $status ?? '';
        $this->showStatusModal = true;
    }

    public function changeStatus()
    {
        if (!$this->contractId || !$this->newStatus) return;

        $contract = Contract::forSchool($this->schoolId)->findOrFail($this->contractId);

        $allowed = $this->getAllowedStatuses($contract->status);
        if (!in_array($this->newStatus, $allowed)) {
            $this->dispatch('toast', message: 'Cambio de estado no permitido.', type: 'error');
            return;
        }

        $contract->update(['status' => $this->newStatus]);
        $this->showStatusModal = false;
        $this->dispatch('toast', message: 'Estado actualizado a ' . Contract::STATUSES[$this->newStatus] . '.', type: 'success');
        $this->viewDetail($this->contractId);
    }

    public function getAllowedStatuses(string $current): array
    {
        return match ($current) {
            'draft'        => ['active'],
            'active'       => ['in_execution', 'suspended', 'terminated'],
            'in_execution' => ['completed', 'suspended', 'terminated'],
            'suspended'    => ['active', 'in_execution', 'terminated'],
            default        => [],
        };
    }

    // ══════════════════════════════════════════════════════════
    // ELIMINAR CONTRATO
    // ══════════════════════════════════════════════════════════

    public function confirmDelete()
    {
        if (!auth()->user()->can('contractual.delete')) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }
        $this->showDeleteModal = true;
    }

    public function deleteContract()
    {
        if (!$this->contractId) return;

        $contract = Contract::with('rps')->forSchool($this->schoolId)->findOrFail($this->contractId);

        if ($contract->status !== 'draft') {
            $this->dispatch('toast', message: 'Solo se pueden eliminar contratos en estado borrador.', type: 'error');
            $this->showDeleteModal = false;
            return;
        }

        DB::beginTransaction();
        try {
            // Revertir CDPs a estado activo
            foreach ($contract->rps as $rp) {
                Cdp::where('id', $rp->cdp_id)->update(['status' => 'active']);
            }

            $contract->delete();
            DB::commit();

            $this->dispatch('toast', message: 'Contrato eliminado.', type: 'success');
            $this->showDeleteModal = false;
            $this->backToList();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    // ══════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════

    public function resetCreateForm()
    {
        $this->selectedConvocatoriaId = '';
        $this->contractNumber = '';
        $this->contractingModality = '';
        $this->executionPlace = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->durationDays = 0;
        $this->contractObject = '';
        $this->contractJustification = '';
        $this->paymentMethod = 'single';
        $this->supervisorId = '';
        $this->contractSubtotal = '';
        $this->contractIva = '';
        $this->contractTotal = '';
        $this->supplierData = [];
        $this->cdpsData = [];
        $this->rpAssignments = [];
        $this->awardedConvocatorias = [];
        $this->supervisors = [];
    }

    // ══════════════════════════════════════════════════════════
    // RENDER
    // ══════════════════════════════════════════════════════════

    #[Layout('layouts.app')]
    public function render()
    {
        // Recargar contrato fresco en cada render para evitar problemas de serialización
        if ($this->currentView === 'detail' && $this->contractId) {
            $this->contract = Contract::with([
                'convocatoria.cdps.budgetItem',
                'convocatoria.cdps.fundingSources.fundingSource',
                'convocatoria.selectedProposal',
                'supplier.department',
                'supplier.municipality',
                'supervisor',
                'creator',
                'rps.cdp.budgetItem',
                'rps.fundingSources.fundingSource',
            ])->forSchool($this->schoolId)->find($this->contractId);
        }

        return view('livewire.contractual-management', [
            'contracts' => $this->contracts,
            'summary'   => $this->summary,
        ]);
    }
}

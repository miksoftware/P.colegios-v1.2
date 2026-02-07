<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Cdp;
use App\Models\CdpFundingSource;
use App\Models\Convocatoria;
use App\Models\ExpenseDistribution;
use App\Models\FundingSource;
use App\Models\Proposal;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class PrecontractualManagement extends Component
{
    use WithPagination;

    public $schoolId;
    public $filterYear;
    public $filterStatus = '';
    public $search = '';

    // Vista principal
    public $currentView = 'list'; // list | detail

    // Convocatoria activa para detalle
    public $convocatoriaId = null;
    public $convocatoria = null;

    // Modal crear convocatoria
    public $showCreateModal = false;
    public $distributions = [];
    public $selectedDistributionId = '';
    public $convObject = '';
    public $convJustification = '';
    public $convStartDate = '';
    public $convEndDate = '';
    public $convAssignedBudget = '';

    // Modal CDP
    public $showCdpModal = false;
    public $cdpBudgetItemId = '';
    public $cdpFundingSources = [];
    public $availableFundingSources = [];
    public $budgetItems = [];

    // Modal Propuesta
    public $showProposalModal = false;
    public $proposalSupplierId = '';
    public $proposalSubtotal = '';
    public $proposalIva = '';
    public $proposalDescription = '';
    public $suppliers = [];

    // Modal Evaluar
    public $showEvaluateModal = false;
    public $proposalScores = [];

    // Modal Eliminar
    public $showDeleteModal = false;
    public $itemToDelete = null;
    public $deleteType = '';

    // Modal Cambio de Estado
    public $showStatusModal = false;
    public $newStatus = '';

    protected $queryString = [
        'filterYear' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function mount($distribution_id = null)
    {
        abort_if(!auth()->user()->can('precontractual.view'), 403);

        $this->schoolId = session('selected_school_id');
        if (!$this->schoolId) {
            session()->flash('error', 'Debe seleccionar un colegio.');
            $this->redirect(route('dashboard'));
            return;
        }

        $this->filterYear = date('Y');

        // Si viene de gastos con una distribución preseleccionada
        if ($distribution_id) {
            $this->openCreateModal($distribution_id);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterYear()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    // === LISTADO ===

    public function getConvocatoriasProperty()
    {
        return Convocatoria::with(['expenseDistribution.expenseCode', 'expenseDistribution.budget.budgetItem', 'expenseDistribution.budget.fundingSource', 'cdps', 'proposals', 'selectedProposal.supplier'])
            ->forSchool($this->schoolId)
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear))
            ->when($this->filterStatus, fn($q) => $q->byStatus($this->filterStatus))
            ->when($this->search, fn($q) => $q->search($this->search))
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function getSummaryProperty()
    {
        $base = Convocatoria::forSchool($this->schoolId)
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear));

        return [
            'total' => (clone $base)->count(),
            'draft' => (clone $base)->byStatus('draft')->count(),
            'open' => (clone $base)->byStatus('open')->count(),
            'evaluation' => (clone $base)->byStatus('evaluation')->count(),
            'awarded' => (clone $base)->byStatus('awarded')->count(),
            'total_budget' => (clone $base)->sum('assigned_budget'),
        ];
    }

    // === DETALLE ===

    public function viewDetail($id)
    {
        $this->convocatoria = Convocatoria::with([
            'expenseDistribution.expenseCode',
            'expenseDistribution.budget.budgetItem',
            'expenseDistribution.budget.fundingSource',
            'cdps.budgetItem',
            'cdps.fundingSources.fundingSource',
            'cdps.fundingSources.budget',
            'proposals.supplier',
            'creator',
        ])->forSchool($this->schoolId)->findOrFail($id);

        $this->convocatoriaId = $id;
        $this->currentView = 'detail';
    }

    public function backToList()
    {
        $this->currentView = 'list';
        $this->convocatoria = null;
        $this->convocatoriaId = null;
    }

    // === CREAR CONVOCATORIA ===

    public function openCreateModal($distributionId = null)
    {
        if (!auth()->user()->can('precontractual.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear convocatorias.', type: 'error');
            return;
        }

        // Cargar distribuciones disponibles (que no tengan convocatoria activa)
        $this->distributions = ExpenseDistribution::with(['expenseCode', 'budget.budgetItem', 'budget.fundingSource'])
            ->forSchool($this->schoolId)
            ->whereHas('budget', fn($q) => $q->where('fiscal_year', $this->filterYear))
            ->where('is_active', true)
            ->get()
            ->map(fn($d) => [
                'id' => $d->id,
                'label' => ($d->expenseCode?->code ?? '') . ' - ' . ($d->expenseCode?->name ?? 'Sin código'),
                'budget_item' => $d->budget?->budgetItem?->name ?? '',
                'funding_source' => $d->budget?->fundingSource?->name ?? '',
                'amount' => $d->amount,
                'available' => $d->available_balance,
            ])
            ->toArray();

        if ($distributionId) {
            $this->selectedDistributionId = $distributionId;
            $dist = collect($this->distributions)->firstWhere('id', $distributionId);
            if ($dist) {
                $this->convAssignedBudget = $dist['available'];
            }
        }

        $this->convStartDate = now()->format('Y-m-d');
        $this->convEndDate = now()->addDays(15)->format('Y-m-d');
        $this->showCreateModal = true;
    }

    public function updatedSelectedDistributionId($value)
    {
        if ($value) {
            $dist = collect($this->distributions)->firstWhere('id', (int) $value);
            if ($dist) {
                $this->convAssignedBudget = $dist['available'];
            }
        }
    }

    public function saveConvocatoria()
    {
        if (!auth()->user()->can('precontractual.create')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        $this->validate([
            'selectedDistributionId' => 'required|exists:expense_distributions,id',
            'convObject' => 'required|min:10|max:500',
            'convJustification' => 'required|min:10|max:1000',
            'convStartDate' => 'required|date',
            'convEndDate' => 'required|date|after:convStartDate',
            'convAssignedBudget' => 'required|numeric|min:1',
        ], [
            'selectedDistributionId.required' => 'Seleccione una distribución.',
            'convObject.required' => 'El objeto es obligatorio.',
            'convObject.min' => 'El objeto debe tener al menos 10 caracteres.',
            'convJustification.required' => 'La justificación es obligatoria.',
            'convJustification.min' => 'La justificación debe tener al menos 10 caracteres.',
            'convStartDate.required' => 'La fecha de inicio es obligatoria.',
            'convEndDate.required' => 'La fecha de cierre es obligatoria.',
            'convEndDate.after' => 'La fecha de cierre debe ser posterior a la de inicio.',
            'convAssignedBudget.required' => 'El presupuesto es obligatorio.',
            'convAssignedBudget.min' => 'El presupuesto debe ser mayor a 0.',
        ]);

        // Verificar que el monto no exceda el disponible
        $distribution = ExpenseDistribution::findOrFail($this->selectedDistributionId);
        if ($this->convAssignedBudget > $distribution->available_balance) {
            $this->addError('convAssignedBudget', 'El monto excede el saldo disponible de la distribución ($' . number_format($distribution->available_balance, 2) . ').');
            return;
        }

        $convocatoria = Convocatoria::create([
            'school_id' => $this->schoolId,
            'expense_distribution_id' => $this->selectedDistributionId,
            'convocatoria_number' => Convocatoria::getNextConvocatoriaNumber($this->schoolId, $this->filterYear),
            'fiscal_year' => $this->filterYear,
            'start_date' => $this->convStartDate,
            'end_date' => $this->convEndDate,
            'object' => $this->convObject,
            'justification' => $this->convJustification,
            'assigned_budget' => $this->convAssignedBudget,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        $this->dispatch('toast', message: 'Convocatoria #' . $convocatoria->formatted_number . ' creada exitosamente.', type: 'success');
        $this->closeCreateModal();
        $this->viewDetail($convocatoria->id);
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->selectedDistributionId = '';
        $this->convObject = '';
        $this->convJustification = '';
        $this->convStartDate = '';
        $this->convEndDate = '';
        $this->convAssignedBudget = '';
        $this->distributions = [];
        $this->resetValidation();
    }

    // === CAMBIAR ESTADO ===

    public function openStatusModal($status)
    {
        if (!auth()->user()->can('precontractual.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        $this->newStatus = $status;
        $this->showStatusModal = true;
    }

    public function changeStatus()
    {
        if (!$this->convocatoria) return;

        $validTransitions = [
            'draft' => ['open', 'cancelled'],
            'open' => ['evaluation', 'cancelled'],
            'evaluation' => ['awarded', 'open', 'cancelled'],
        ];

        $allowed = $validTransitions[$this->convocatoria->status] ?? [];
        if (!in_array($this->newStatus, $allowed)) {
            $this->dispatch('toast', message: 'Transición de estado no permitida.', type: 'error');
            $this->showStatusModal = false;
            return;
        }

        // Validaciones por estado
        if ($this->newStatus === 'open') {
            if ($this->convocatoria->cdps->where('status', 'active')->count() === 0) {
                $this->dispatch('toast', message: 'Debe registrar al menos un CDP antes de abrir la convocatoria.', type: 'error');
                $this->showStatusModal = false;
                return;
            }
        }

        if ($this->newStatus === 'awarded') {
            if ($this->convocatoria->proposals->where('is_selected', true)->count() === 0) {
                $this->dispatch('toast', message: 'Debe seleccionar una propuesta ganadora antes de adjudicar.', type: 'error');
                $this->showStatusModal = false;
                return;
            }
        }

        $this->convocatoria->update([
            'status' => $this->newStatus,
            'evaluation_date' => $this->newStatus === 'awarded' ? now() : $this->convocatoria->evaluation_date,
        ]);

        $this->dispatch('toast', message: 'Estado actualizado a: ' . Convocatoria::STATUSES[$this->newStatus], type: 'success');
        $this->showStatusModal = false;
        $this->viewDetail($this->convocatoria->id);
    }

    public function closeStatusModal()
    {
        $this->showStatusModal = false;
        $this->newStatus = '';
    }

    // === CDP ===

    public function openCdpModal()
    {
        if (!auth()->user()->can('precontractual.create')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        if (!in_array($this->convocatoria->status, ['draft'])) {
            $this->dispatch('toast', message: 'Solo se pueden agregar CDPs en estado borrador.', type: 'error');
            return;
        }

        // Cargar rubros disponibles
        $this->budgetItems = BudgetItem::active()
            ->orderBy('code')
            ->get()
            ->map(fn($item) => ['id' => $item->id, 'name' => "{$item->code} - {$item->name}"])
            ->toArray();

        $this->cdpBudgetItemId = '';
        $this->cdpFundingSources = [];
        $this->availableFundingSources = [];
        $this->showCdpModal = true;
    }

    public function updatedCdpBudgetItemId($value)
    {
        $this->cdpFundingSources = [];
        $this->availableFundingSources = [];

        if (empty($value)) return;

        // Obtener fuentes de financiación con saldo para este rubro y año
        $sources = FundingSource::where('budget_item_id', $value)
            ->active()
            ->get();

        $this->availableFundingSources = $sources->map(function ($source) {
            $balance = $source->getAvailableBalanceForYear($this->filterYear);
            // Restar lo ya reservado por CDPs activos
            $reserved = Cdp::getTotalReservedForFundingSource($source->id, $this->filterYear);
            $available = $balance - $reserved;

            // Obtener el presupuesto vinculado
            $budget = Budget::forSchool($this->schoolId)
                ->where('funding_source_id', $source->id)
                ->where('budget_item_id', $this->cdpBudgetItemId)
                ->where('fiscal_year', $this->filterYear)
                ->where('type', 'expense')
                ->first();

            return [
                'id' => $source->id,
                'name' => $source->code . ' - ' . $source->name,
                'type' => $source->type_name,
                'available' => max(0, $available),
                'budget_id' => $budget?->id,
            ];
        })->filter(fn($s) => $s['available'] > 0)->values()->toArray();
    }

    public function addCdpFundingSource($sourceId)
    {
        $source = collect($this->availableFundingSources)->firstWhere('id', $sourceId);
        if (!$source) return;

        // Verificar que no esté ya agregada
        if (collect($this->cdpFundingSources)->contains('id', $sourceId)) {
            $this->dispatch('toast', message: 'Esta fuente ya fue agregada.', type: 'error');
            return;
        }

        $this->cdpFundingSources[] = [
            'id' => $source['id'],
            'name' => $source['name'],
            'available' => $source['available'],
            'budget_id' => $source['budget_id'],
            'amount' => '',
        ];
    }

    public function removeCdpFundingSource($index)
    {
        unset($this->cdpFundingSources[$index]);
        $this->cdpFundingSources = array_values($this->cdpFundingSources);
    }

    public function saveCdp()
    {
        if (!auth()->user()->can('precontractual.create')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        $this->validate([
            'cdpBudgetItemId' => 'required|exists:budget_items,id',
            'cdpFundingSources' => 'required|array|min:1',
            'cdpFundingSources.*.amount' => 'required|numeric|min:0.01',
        ], [
            'cdpBudgetItemId.required' => 'Seleccione un rubro.',
            'cdpFundingSources.required' => 'Agregue al menos una fuente.',
            'cdpFundingSources.min' => 'Agregue al menos una fuente.',
            'cdpFundingSources.*.amount.required' => 'Ingrese un monto.',
            'cdpFundingSources.*.amount.min' => 'El monto debe ser mayor a 0.',
        ]);

        // Validar que los montos no excedan disponibles
        foreach ($this->cdpFundingSources as $i => $fs) {
            if ($fs['amount'] > $fs['available']) {
                $this->addError("cdpFundingSources.{$i}.amount", 'Excede el saldo disponible.');
                return;
            }
        }

        $totalAmount = collect($this->cdpFundingSources)->sum('amount');

        DB::transaction(function () use ($totalAmount) {
            $cdp = Cdp::create([
                'school_id' => $this->schoolId,
                'convocatoria_id' => $this->convocatoria->id,
                'cdp_number' => Cdp::getNextCdpNumber($this->schoolId, $this->filterYear),
                'fiscal_year' => $this->filterYear,
                'budget_item_id' => $this->cdpBudgetItemId,
                'total_amount' => $totalAmount,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            foreach ($this->cdpFundingSources as $fs) {
                $source = FundingSource::find($fs['id']);
                $balance = $source ? $source->getAvailableBalanceForYear($this->filterYear) : 0;

                CdpFundingSource::create([
                    'cdp_id' => $cdp->id,
                    'funding_source_id' => $fs['id'],
                    'budget_id' => $fs['budget_id'],
                    'amount' => $fs['amount'],
                    'available_balance_at_creation' => $balance,
                ]);
            }
        });

        $this->dispatch('toast', message: 'CDP registrado exitosamente.', type: 'success');
        $this->closeCdpModal();
        $this->viewDetail($this->convocatoria->id);
    }

    public function closeCdpModal()
    {
        $this->showCdpModal = false;
        $this->cdpBudgetItemId = '';
        $this->cdpFundingSources = [];
        $this->availableFundingSources = [];
        $this->resetValidation();
    }

    public function cancelCdp($cdpId)
    {
        if (!auth()->user()->can('precontractual.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        $cdp = Cdp::where('convocatoria_id', $this->convocatoria->id)->findOrFail($cdpId);
        
        if ($cdp->status !== 'active') {
            $this->dispatch('toast', message: 'Solo se pueden anular CDPs activos.', type: 'error');
            return;
        }

        $cdp->update(['status' => 'cancelled']);
        $this->dispatch('toast', message: 'CDP #' . $cdp->formatted_number . ' anulado.', type: 'success');
        $this->viewDetail($this->convocatoria->id);
    }

    // === PROPUESTAS ===

    public function openProposalModal()
    {
        if (!auth()->user()->can('precontractual.create')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        if (!in_array($this->convocatoria->status, ['open', 'evaluation'])) {
            $this->dispatch('toast', message: 'Solo se pueden registrar propuestas con la convocatoria abierta o en evaluación.', type: 'error');
            return;
        }

        $this->suppliers = Supplier::where('school_id', $this->schoolId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get()
            ->map(fn($s) => ['id' => $s->id, 'name' => $s->full_name . ' (' . $s->full_document . ')'])
            ->toArray();

        $this->proposalSupplierId = '';
        $this->proposalSubtotal = '';
        $this->proposalIva = '';
        $this->proposalDescription = '';
        $this->showProposalModal = true;
    }

    public function saveProposal()
    {
        if (!auth()->user()->can('precontractual.create')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        $this->validate([
            'proposalSupplierId' => 'required|exists:suppliers,id',
            'proposalSubtotal' => 'required|numeric|min:0.01',
            'proposalIva' => 'nullable|numeric|min:0',
        ], [
            'proposalSupplierId.required' => 'Seleccione un proveedor.',
            'proposalSubtotal.required' => 'El subtotal es obligatorio.',
            'proposalSubtotal.min' => 'El subtotal debe ser mayor a 0.',
        ]);

        // Verificar que el proveedor no tenga ya propuesta en esta convocatoria
        $exists = Proposal::where('convocatoria_id', $this->convocatoria->id)
            ->where('supplier_id', $this->proposalSupplierId)
            ->exists();

        if ($exists) {
            $this->dispatch('toast', message: 'Este proveedor ya tiene una propuesta registrada.', type: 'error');
            return;
        }

        $nextNumber = ($this->convocatoria->proposals()->max('proposal_number') ?? 0) + 1;
        $iva = $this->proposalIva ?: 0;
        $total = $this->proposalSubtotal + $iva;

        Proposal::create([
            'convocatoria_id' => $this->convocatoria->id,
            'supplier_id' => $this->proposalSupplierId,
            'proposal_number' => $nextNumber,
            'subtotal' => $this->proposalSubtotal,
            'iva' => $iva,
            'total' => $total,
        ]);

        // Actualizar contador
        $this->convocatoria->update([
            'proposals_count' => $this->convocatoria->proposals()->count() + 1,
        ]);

        $this->dispatch('toast', message: 'Propuesta registrada exitosamente.', type: 'success');
        $this->closeProposalModal();
        $this->viewDetail($this->convocatoria->id);
    }

    public function closeProposalModal()
    {
        $this->showProposalModal = false;
        $this->proposalSupplierId = '';
        $this->proposalSubtotal = '';
        $this->proposalIva = '';
        $this->proposalDescription = '';
        $this->resetValidation();
    }

    // === EVALUACIÓN ===

    public function openEvaluateModal()
    {
        if (!auth()->user()->can('precontractual.evaluate')) {
            $this->dispatch('toast', message: 'No tienes permisos para evaluar.', type: 'error');
            return;
        }

        if ($this->convocatoria->status !== 'evaluation') {
            $this->dispatch('toast', message: 'La convocatoria debe estar en evaluación.', type: 'error');
            return;
        }

        if ($this->convocatoria->proposals->count() < 1) {
            $this->dispatch('toast', message: 'Debe haber al menos una propuesta para evaluar.', type: 'error');
            return;
        }

        $this->proposalScores = $this->convocatoria->proposals->map(fn($p) => [
            'id' => $p->id,
            'supplier' => $p->supplier?->full_name ?? 'Sin proveedor',
            'total' => $p->total,
            'score' => $p->score ?? '',
            'is_selected' => $p->is_selected,
        ])->toArray();

        $this->showEvaluateModal = true;
    }

    public function saveEvaluation()
    {
        if (!auth()->user()->can('precontractual.evaluate')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        // Validar que todos tengan puntaje
        foreach ($this->proposalScores as $i => $ps) {
            if (!is_numeric($ps['score']) || $ps['score'] < 0 || $ps['score'] > 100) {
                $this->addError("proposalScores.{$i}.score", 'Puntaje debe ser entre 0 y 100.');
                return;
            }
        }

        // Verificar que exactamente una esté seleccionada
        $selectedCount = collect($this->proposalScores)->where('is_selected', true)->count();
        if ($selectedCount !== 1) {
            $this->dispatch('toast', message: 'Debe seleccionar exactamente una propuesta ganadora.', type: 'error');
            return;
        }

        DB::transaction(function () {
            foreach ($this->proposalScores as $ps) {
                Proposal::where('id', $ps['id'])->update([
                    'score' => $ps['score'],
                    'is_selected' => $ps['is_selected'] ?? false,
                ]);
            }

            $this->convocatoria->update([
                'evaluation_date' => now(),
            ]);
        });

        $this->dispatch('toast', message: 'Evaluación guardada exitosamente.', type: 'success');
        $this->showEvaluateModal = false;
        $this->viewDetail($this->convocatoria->id);
    }

    public function selectProposal($index)
    {
        foreach ($this->proposalScores as $i => $ps) {
            $this->proposalScores[$i]['is_selected'] = ($i === $index);
        }
    }

    public function closeEvaluateModal()
    {
        $this->showEvaluateModal = false;
        $this->proposalScores = [];
    }

    // === ELIMINAR ===

    public function confirmDeleteConvocatoria($id)
    {
        if (!auth()->user()->can('precontractual.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        $conv = Convocatoria::forSchool($this->schoolId)->findOrFail($id);
        if ($conv->status !== 'draft') {
            $this->dispatch('toast', message: 'Solo se pueden eliminar convocatorias en borrador.', type: 'error');
            return;
        }

        $this->itemToDelete = $conv;
        $this->deleteType = 'convocatoria';
        $this->showDeleteModal = true;
    }

    public function confirmDeleteProposal($id)
    {
        if (!auth()->user()->can('precontractual.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        $proposal = Proposal::where('convocatoria_id', $this->convocatoria->id)->findOrFail($id);
        $this->itemToDelete = $proposal;
        $this->deleteType = 'proposal';
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if (!auth()->user()->can('precontractual.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        if (!$this->itemToDelete) return;

        if ($this->deleteType === 'convocatoria') {
            // Eliminar CDPs y propuestas asociadas
            DB::transaction(function () {
                foreach ($this->itemToDelete->cdps as $cdp) {
                    $cdp->fundingSources()->delete();
                    $cdp->delete();
                }
                $this->itemToDelete->proposals()->delete();
                $this->itemToDelete->delete();
            });

            $this->dispatch('toast', message: 'Convocatoria eliminada.', type: 'success');
            $this->closeDeleteModal();
            $this->backToList();
            return;
        }

        if ($this->deleteType === 'proposal') {
            $this->itemToDelete->delete();
            $this->convocatoria->update([
                'proposals_count' => max(0, $this->convocatoria->proposals()->count() - 1),
            ]);
            $this->dispatch('toast', message: 'Propuesta eliminada.', type: 'success');
            $this->closeDeleteModal();
            $this->viewDetail($this->convocatoria->id);
            return;
        }

        $this->closeDeleteModal();
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->itemToDelete = null;
        $this->deleteType = '';
    }

    // === FILTROS ===

    public function clearFilters()
    {
        $this->filterYear = date('Y');
        $this->filterStatus = '';
        $this->search = '';
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.precontractual-management');
    }
}

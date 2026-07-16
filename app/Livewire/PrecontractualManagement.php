<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Cdp;
use App\Models\CdpFundingSource;
use App\Models\Convocatoria;
use App\Models\ConvocatoriaDistribution;
use App\Models\ExpenseDistribution;
use App\Models\FundingSource;
use App\Models\Proposal;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
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
    public $isLastConvocatoria = false;

    // Modal crear convocatoria
    public $showCreateModal = false;
    public $showEditModal = false;
    public $distributions = [];
    public $distributionDetailMap = []; // [expense_distribution_id => convocatoria_distribution_id]
    public $lockedDistributionIds = []; // distribuciones que no se pueden quitar en edición por CDP asociado
    public $lockedExpenseCodeIds = []; // códigos de gasto que no se pueden quitar totalmente en edición
    public $selectedDistributionId = '';
    public $groupedDistributions = []; // agrupadas por código de gasto
    public $selectedExpenseCodeIds = []; // [expense_code_id => bool]
    public $distributionAmounts = []; // [distribution_id => amount]
    public $selectedDistributionIds = []; // [distribution_id => bool]
    public $convObject = '';
    public $convJustification = '';
    public $convStartDate = '';
    public $convStartTime = '';
    public $convEndDate = '';
    public $convEndTime = '';
    public $convAssignedBudget = '';
    public $convEstimatedDuration = '';
    public $convContractingModality = 'especial';
    public $convRequesterName = '';
    public $convRequesterPosition = '';
    public $contractLockedEdit = false;

    // Modal CDP
    public $showCdpModal = false;
    public $editingCdpId = null;
    public $cdpDistributionId = '';   // ConvocatoriaDistribution seleccionada
    public $cdpBudgetItemId = '';     // se llena automáticamente desde la distribución
    public $cdpFundingSources = [];
    public $availableFundingSources = [];
    public $budgetItems = [];
    public $availableDistributions = []; // distribuciones sin CDP activo

    public $showProposalModal = false;
    public $editingProposalId = null;
    public $proposalSupplierId = '';
    public $proposalReceivedDate = '';
    public $proposalReceivedTime = '';
    public $proposalSubtotal = '';
    public $proposalIva = '';
    public $proposalDescription = '';
    public $suppliers = [];

    // Modal Evaluar
    public $showEvaluateModal = false;
    public $proposalScores = [];
    public $evaluationDate = '';
    public $evaluationTime = '';

    // Modal Eliminar
    public $showDeleteModal = false;

    // Modal Cambiar Fechas de Convocatoria
    public $showChangeDatesModal = false;
    public $changeDatesStartDate = '';
    public $changeDatesStartTime = '';
    public $changeDatesEndDate = '';
    public $changeDatesEndTime = '';
    public $itemToDelete = null;
    public $deleteType = '';

    // Modal Cambio de Estado
    public $showStatusModal = false;
    public $newStatus = '';

    // Modal Imprimir Documentos
    public $showPrintModal = false;
    public $printDocuments = [
        'estudios_previos' => false,
        'disponibilidad_presupuestal' => false,
        'requisicion_necesidades' => false,
        'certificado_plan_compras' => false,
        'convocatoria_veedurias' => false,
        'invitacion_cotizar' => false,
        'acta_evaluacion' => false,
        'aceptacion_propuesta' => false,
        'certificado_disponibilidad' => false,
    ];

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

        $this->filterYear = \App\Models\School::find($this->schoolId)?->current_validity ?? date('Y');

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
        return Convocatoria::with(['expenseDistribution.expenseCode', 'expenseDistribution.budget.budgetItem', 'expenseDistribution.budget.fundingSource', 'cdps', 'proposals', 'selectedProposal.supplier', 'contract'])
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
            'distributionDetails.expenseDistribution.expenseCode',
            'distributionDetails.expenseDistribution.budget.budgetItem',
            'distributionDetails.expenseDistribution.budget.fundingSource',
            'cdps.budgetItem',
            'cdps.contractRp',
            'cdps.fundingSources.fundingSource',
            'cdps.fundingSources.budget',
            'cdps.convocatoriaDistribution.expenseDistribution.expenseCode',
            'proposals.supplier',
            'creator',
            'contract',
        ])->forSchool($this->schoolId)->findOrFail($id);

        $this->convocatoriaId = $id;
        $this->currentView = 'detail';

        // Verificar si es la última convocatoria (para permitir eliminación)
        $year = (int) $this->convocatoria->fiscal_year;
        $maxNumber = Convocatoria::where('school_id', $this->schoolId)
            ->where('fiscal_year', $year)
            ->max('convocatoria_number');
        
        $this->isLastConvocatoria = ($this->convocatoria->convocatoria_number === $maxNumber);
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

        $this->resetConvocatoriaForm();
        $this->loadDistributionOptions((int) $this->filterYear);

        if ($distributionId) {
            $dist = collect($this->distributions)->firstWhere('id', $distributionId);
            if ($dist) {
                $this->selectedExpenseCodeIds[$dist['expense_code_id']] = true;
                $this->onExpenseCodeToggled($dist['expense_code_id']);
                // Pre-llenar el monto de esta distribución
                $this->distributionAmounts[$distributionId] = $dist['available'];
                $this->selectedDistributionIds[$distributionId] = true;
                $this->recalculateBudget();
            }
        }

        $this->convStartDate = now()->format('Y-m-d');
        $this->convStartTime = '08:00';
        $this->convEndDate = now()->addDays(15)->format('Y-m-d');
        $this->convEndTime = '16:00';
        $this->showCreateModal = true;
    }

    protected function canAdminEditConvocatoria(): bool
    {
        $user = auth()->user();

        return $user && $user->can('precontractual.edit') && $user->hasRole('Admin');
    }

    protected function canEditOnlyNonSensitiveFields(?Convocatoria $convocatoria = null): bool
    {
        return $this->canAdminEditConvocatoria() && (bool) ($convocatoria?->contract);
    }

    protected function loadDistributionOptions(int $year, ?Convocatoria $currentConvocatoria = null): void
    {
        $currentAmounts = [];

        if ($currentConvocatoria) {
            $currentConvocatoria->loadMissing('distributionDetails');
            $currentAmounts = $currentConvocatoria->distributionDetails
                ->groupBy('expense_distribution_id')
                ->map(fn($group) => (float) $group->sum('amount'))
                ->toArray();
        }

        $rawDistributions = ExpenseDistribution::with([
                'expenseCode',
                'budget.budgetItem',
                'budget.fundingSource',
                'convocatoriaDistributions.convocatoria.contract.paymentOrders',
                'paymentOrderLines.paymentOrder.contract',
            ])
            ->forSchool($this->schoolId)
            ->whereHas('budget', fn($q) => $q->where('fiscal_year', $year))
            ->where('is_active', true)
            ->get();

        $this->distributions = $rawDistributions->map(function ($d) use ($currentAmounts, $currentConvocatoria) {
            $currentAmount = (float) ($currentAmounts[$d->id] ?? 0);
            $editableAvailable = (float) $d->available_balance + $currentAmount;

            return [
                'id' => $d->id,
                'expense_code_id' => $d->expense_code_id,
                'expense_code' => ($d->expenseCode?->code ?? '') . ' - ' . ($d->expenseCode?->name ?? 'Sin código'),
                'budget_item' => $d->budget?->budgetItem?->name ?? '',
                'budget_item_code' => $d->budget?->budgetItem?->code ?? '',
                'funding_source' => $d->budget?->fundingSource?->name ?? '',
                'amount' => (float) $d->amount,
                'available' => $editableAvailable,
                'current_amount' => $currentAmount,
                'base_available' => (float) $d->available_balance,
            ];
        })->filter(function ($d) use ($currentConvocatoria) {
            return $currentConvocatoria
                ? ($d['available'] > 0 || $d['current_amount'] > 0)
                : $d['available'] > 0;
        })->values()->toArray();

        $this->groupedDistributions = collect($this->distributions)
            ->groupBy('expense_code_id')
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'expense_code_id' => $first['expense_code_id'],
                    'expense_code' => $first['expense_code'],
                    'total_available' => $group->sum('available'),
                    'distributions' => $group->values()->toArray(),
                    'count' => $group->count(),
                ];
            })
            ->filter(fn($g) => $g['total_available'] > 0)
            ->values()
            ->toArray();

        if ($currentConvocatoria) {
            $this->loadLockedEditDistributions($currentConvocatoria);
        }
    }

    protected function loadLockedEditDistributions(Convocatoria $convocatoria): void
    {
        $convocatoria->loadMissing('distributionDetails');

        $this->distributionDetailMap = $convocatoria->distributionDetails
            ->mapWithKeys(fn($detail) => [(int) $detail->expense_distribution_id => (int) $detail->id])
            ->toArray();

        if (empty($this->distributionDetailMap)) {
            $this->lockedDistributionIds = [];
            $this->lockedExpenseCodeIds = [];
            return;
        }

        $lockedDistributionDetailIds = Cdp::where('convocatoria_id', $convocatoria->id)
            ->whereNotNull('convocatoria_distribution_id')
            ->whereIn('convocatoria_distribution_id', array_values($this->distributionDetailMap))
            ->pluck('convocatoria_distribution_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $this->lockedDistributionIds = collect($this->distributionDetailMap)
            ->filter(fn($detailId) => in_array((int) $detailId, $lockedDistributionDetailIds, true))
            ->keys()
            ->map(fn($id) => (int) $id)
            ->values()
            ->toArray();

        $this->lockedExpenseCodeIds = collect($this->distributions)
            ->filter(fn($dist) => in_array((int) $dist['id'], $this->lockedDistributionIds, true))
            ->pluck('expense_code_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();
    }

    protected function canRemoveDistributionInEdit(int $distId): bool
    {
        if (!$this->showEditModal) {
            return true;
        }

        return !in_array($distId, $this->lockedDistributionIds, true);
    }

    protected function canRemoveExpenseCodeInEdit(int $expenseCodeId): bool
    {
        if (!$this->showEditModal) {
            return true;
        }

        return !in_array($expenseCodeId, $this->lockedExpenseCodeIds, true);
    }

    public function openEditModal()
    {
        if (!$this->canAdminEditConvocatoria()) {
            $this->dispatch('toast', message: 'Solo los administradores pueden editar convocatorias.', type: 'error');
            return;
        }

        if (!$this->convocatoriaId) {
            return;
        }

        $convocatoria = Convocatoria::with([
            'distributionDetails',
            'contract',
        ])->forSchool($this->schoolId)->findOrFail($this->convocatoriaId);

        $this->resetValidation();
        $this->resetConvocatoriaForm();
        $this->contractLockedEdit = $this->canEditOnlyNonSensitiveFields($convocatoria);

        if (!$this->contractLockedEdit) {
            $this->loadDistributionOptions((int) $convocatoria->fiscal_year, $convocatoria);
        }

        $this->convObject = $convocatoria->object ?? '';
        $this->convJustification = $convocatoria->justification ?? '';
        $this->convStartDate = $convocatoria->start_date?->format('Y-m-d') ?? '';
        $this->convStartTime = $convocatoria->start_time ?? '';
        $this->convEndDate = $convocatoria->end_date?->format('Y-m-d') ?? '';
        $this->convEndTime = $convocatoria->end_time ?? '';
        $this->convEstimatedDuration = $convocatoria->estimated_duration_days ?? '';
        $this->convContractingModality = $convocatoria->contracting_modality ?: 'especial';
        $this->convRequesterName = $convocatoria->requester_name ?? '';
        $this->convRequesterPosition = $convocatoria->requester_position ?? '';

        if (!$this->contractLockedEdit) {
            $selectedDetails = $convocatoria->distributionDetails
                ->groupBy('expense_distribution_id')
                ->map(fn($group) => (float) $group->sum('amount'));

            foreach ($selectedDetails as $distId => $amount) {
                $dist = collect($this->distributions)->firstWhere('id', (int) $distId);
                if (!$dist) {
                    continue;
                }

                $this->selectedDistributionIds[(int) $distId] = true;
                $this->selectedExpenseCodeIds[$dist['expense_code_id']] = true;
                $this->distributionAmounts[(int) $distId] = $amount;
            }

            if ($selectedDetails->isEmpty() && $convocatoria->expense_distribution_id) {
                $dist = collect($this->distributions)->firstWhere('id', (int) $convocatoria->expense_distribution_id);
                if ($dist) {
                    $this->selectedDistributionIds[$dist['id']] = true;
                    $this->selectedExpenseCodeIds[$dist['expense_code_id']] = true;
                    $this->distributionAmounts[$dist['id']] = (float) $convocatoria->assigned_budget;
                }
            }

            $this->recalculateBudget();
        } else {
            $this->convAssignedBudget = (float) $convocatoria->assigned_budget;
        }

        $this->showEditModal = true;
    }

    public function toggleExpenseCode($expenseCodeId)
    {
        $expenseCodeId = (int) $expenseCodeId;
        if (!empty($this->selectedExpenseCodeIds[$expenseCodeId])) {
            if (!$this->canRemoveExpenseCodeInEdit($expenseCodeId)) {
                $this->dispatch('toast', message: 'No se puede quitar este código de gasto porque ya tiene CDPs vinculados. Esto se bloquea para proteger reportes y trazabilidad.', type: 'error');
                return;
            }

            unset($this->selectedExpenseCodeIds[$expenseCodeId]);
            // Deseleccionar distribuciones de este código
            $group = collect($this->groupedDistributions)
                ->firstWhere('expense_code_id', $expenseCodeId);
            if ($group) {
                foreach ($group['distributions'] as $dist) {
                    if (!$this->canRemoveDistributionInEdit((int) $dist['id'])) {
                        continue;
                    }
                    unset($this->selectedDistributionIds[$dist['id']]);
                    unset($this->distributionAmounts[$dist['id']]);
                }
            }
        } else {
            $this->selectedExpenseCodeIds[$expenseCodeId] = true;
            $this->onExpenseCodeToggled($expenseCodeId);
        }
        $this->recalculateBudget();
    }

    public function onExpenseCodeToggled($expenseCodeId)
    {
        $group = collect($this->groupedDistributions)
            ->firstWhere('expense_code_id', (int) $expenseCodeId);

        if ($group) {
            if ($group['count'] === 1) {
                // Solo un rubro: auto-seleccionar y pre-llenar monto
                $dist = $group['distributions'][0];
                $this->selectedDistributionIds[$dist['id']] = true;
                $this->distributionAmounts[$dist['id']] = $dist['available'];
            }
        }
    }

    public function toggleDistribution($distId)
    {
        $distId = (int) $distId;
        if (!empty($this->selectedDistributionIds[$distId])) {
            if (!$this->canRemoveDistributionInEdit($distId)) {
                $this->dispatch('toast', message: 'No se puede quitar este rubro porque ya tiene CDP asociado. Así evitamos romper CDPs y reportes.', type: 'error');
                return;
            }

            unset($this->selectedDistributionIds[$distId]);
            unset($this->distributionAmounts[$distId]);
        } else {
            $dist = collect($this->distributions)->firstWhere('id', $distId);
            if ($dist) {
                $this->selectedDistributionIds[$distId] = true;
                $this->distributionAmounts[$distId] = $dist['available'];
            }
        }
        $this->recalculateBudget();
    }

    public function updatedDistributionAmounts()
    {
        $this->recalculateBudget();
    }

    public function recalculateBudget()
    {
        $total = 0;
        foreach ($this->distributionAmounts as $distId => $amount) {
            if (!empty($this->selectedDistributionIds[$distId])) {
                $total += (float) ($amount ?? 0);
            }
        }
        $this->convAssignedBudget = $total;
    }

    public function saveConvocatoria()
    {
        if (!auth()->user()->can('precontractual.create')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        $this->validate([
            'convObject' => 'required|min:10|max:500',
            'convJustification' => 'required|min:10|max:1000',
            'convStartDate' => 'required|date',
            'convStartTime' => 'required',
            'convEndDate' => 'required|date|after_or_equal:convStartDate',
            'convEndTime' => 'required',
            'convAssignedBudget' => 'required|numeric|min:1',
            'convEstimatedDuration' => 'required|integer|min:1',
            'convContractingModality' => 'required|string',
            'convRequesterName' => 'nullable|string|max:255',
            'convRequesterPosition' => 'nullable|string|max:255',
        ], [
            'convObject.required' => 'El objeto es obligatorio.',
            'convObject.min' => 'El objeto debe tener al menos 10 caracteres.',
            'convJustification.required' => 'La justificación es obligatoria.',
            'convJustification.min' => 'La justificación debe tener al menos 10 caracteres.',
            'convStartDate.required' => 'La fecha de inicio es obligatoria.',
            'convStartTime.required' => 'La hora de inicio es obligatoria.',
            'convEndDate.required' => 'La fecha de cierre es obligatoria.',
            'convEndDate.after_or_equal' => 'La fecha de cierre debe ser igual o posterior a la de inicio.',
            'convEndTime.required' => 'La hora de cierre es obligatoria.',
            'convAssignedBudget.required' => 'El presupuesto es obligatorio.',
            'convAssignedBudget.min' => 'El presupuesto debe ser mayor a 0.',
            'convEstimatedDuration.required' => 'La duración probable es obligatoria.',
            'convEstimatedDuration.min' => 'La duración debe ser al menos 1 día.',
            'convContractingModality.required' => 'La modalidad contractual es obligatoria.',
        ]);

        // Validar que al menos un código de gasto esté seleccionado
        if (empty(array_filter($this->selectedExpenseCodeIds))) {
            $this->dispatch('toast', message: 'Debe seleccionar al menos un código de gasto.', type: 'error');
            return;
        }

        // Validar que al menos una distribución esté seleccionada con monto > 0
        $validAmounts = collect($this->distributionAmounts)
            ->filter(fn($a, $id) => (float) $a > 0 && !empty($this->selectedDistributionIds[$id]));
        if ($validAmounts->isEmpty()) {
            $this->dispatch('toast', message: 'Debe seleccionar y asignar monto a al menos un rubro.', type: 'error');
            return;
        }

        // Validar que ningún monto exceda el disponible
        foreach ($this->distributionAmounts as $distId => $amount) {
            if (empty($this->selectedDistributionIds[$distId])) continue;
            $amount = (float) $amount;
            if ($amount <= 0) continue;

            $dist = collect($this->distributions)->firstWhere('id', (int) $distId);
            if ($dist && $amount > $dist['available']) {
                $this->dispatch('toast', message: 'El monto para "' . $dist['budget_item'] . '" excede el disponible ($' . number_format($dist['available'], 2, ',', '.') . ').', type: 'error');
                return;
            }
        }

        DB::beginTransaction();
        try {
            // Tomar la primera distribución como referencia (compatibilidad)
            $firstDistId = $validAmounts->keys()->first();

            $convocatoria = Convocatoria::create([
                'school_id' => $this->schoolId,
                'expense_distribution_id' => $firstDistId,
                'convocatoria_number' => Convocatoria::getNextConvocatoriaNumber($this->schoolId, $this->filterYear),
                'fiscal_year' => $this->filterYear,
                'start_date' => $this->convStartDate,
                'start_time' => $this->convStartTime ?: null,
                'end_date' => $this->convEndDate,
                'end_time' => $this->convEndTime ?: null,
                'object' => $this->convObject,
                'justification' => $this->convJustification,
                'assigned_budget' => $this->convAssignedBudget,
                'estimated_duration_days' => (int) $this->convEstimatedDuration,
                'contracting_modality' => $this->convContractingModality,
                'requester_name' => $this->convRequesterName ?: null,
                'requester_position' => $this->convRequesterPosition ?: null,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            // Guardar detalle de distribuciones
            foreach ($this->distributionAmounts as $distId => $amount) {
                if (empty($this->selectedDistributionIds[$distId])) continue;
                $amount = (float) $amount;
                if ($amount > 0) {
                    ConvocatoriaDistribution::create([
                        'convocatoria_id' => $convocatoria->id,
                        'expense_distribution_id' => (int) $distId,
                        'amount' => $amount,
                    ]);
                }
            }

            DB::commit();

            $this->dispatch('toast', message: 'Convocatoria #' . $convocatoria->formatted_number . ' creada exitosamente.', type: 'success');
            $this->closeCreateModal();
            $this->viewDetail($convocatoria->id);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function saveConvocatoriaEdit()
    {
        if (!$this->canAdminEditConvocatoria()) {
            $this->dispatch('toast', message: 'Solo los administradores pueden editar convocatorias.', type: 'error');
            return;
        }

        if (!$this->convocatoriaId) {
            return;
        }

        $convocatoria = Convocatoria::with(['distributionDetails', 'contract'])
            ->forSchool($this->schoolId)
            ->findOrFail($this->convocatoriaId);

        $this->contractLockedEdit = $this->canEditOnlyNonSensitiveFields($convocatoria);

        if ($this->contractLockedEdit) {
            $this->validate([
                'convObject' => 'required|min:10|max:500',
                'convJustification' => 'required|min:10|max:1000',
            ], [
                'convObject.required' => 'El objeto es obligatorio.',
                'convObject.min' => 'El objeto debe tener al menos 10 caracteres.',
                'convJustification.required' => 'La justificación es obligatoria.',
                'convJustification.min' => 'La justificación debe tener al menos 10 caracteres.',
            ]);

            try {
                $convocatoria->update([
                    'object' => $this->convObject,
                    'justification' => $this->convJustification,
                ]);

                if ($convocatoria->contract) {
                    $convocatoria->contract->update([
                        'object' => $this->convObject,
                        'justification' => $this->convJustification,
                    ]);
                }

                $this->dispatch('toast', message: 'Se actualizaron los campos permitidos de la convocatoria y su contrato asociado.', type: 'success');
                $this->closeEditModal();
                $this->viewDetail($convocatoria->id);
            } catch (\Exception $e) {
                $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
            }

            return;
        }

        $this->validate([
            'convObject' => 'required|min:10|max:500',
            'convJustification' => 'required|min:10|max:1000',
            'convStartDate' => 'required|date',
            'convStartTime' => 'required',
            'convEndDate' => 'required|date|after_or_equal:convStartDate',
            'convEndTime' => 'required',
            'convAssignedBudget' => 'required|numeric|min:1',
            'convEstimatedDuration' => 'required|integer|min:1',
            'convContractingModality' => 'required|string',
            'convRequesterName' => 'nullable|string|max:255',
            'convRequesterPosition' => 'nullable|string|max:255',
        ], [
            'convObject.required' => 'El objeto es obligatorio.',
            'convObject.min' => 'El objeto debe tener al menos 10 caracteres.',
            'convJustification.required' => 'La justificación es obligatoria.',
            'convJustification.min' => 'La justificación debe tener al menos 10 caracteres.',
            'convStartDate.required' => 'La fecha de inicio es obligatoria.',
            'convStartTime.required' => 'La hora de inicio es obligatoria.',
            'convEndDate.required' => 'La fecha de cierre es obligatoria.',
            'convEndDate.after_or_equal' => 'La fecha de cierre debe ser igual o posterior a la de inicio.',
            'convEndTime.required' => 'La hora de cierre es obligatoria.',
            'convAssignedBudget.required' => 'El presupuesto es obligatorio.',
            'convAssignedBudget.min' => 'El presupuesto debe ser mayor a 0.',
            'convEstimatedDuration.required' => 'La duración probable es obligatoria.',
            'convEstimatedDuration.min' => 'La duración debe ser al menos 1 día.',
            'convContractingModality.required' => 'La modalidad contractual es obligatoria.',
        ]);

        if (empty(array_filter($this->selectedExpenseCodeIds))) {
            $this->dispatch('toast', message: 'Debe seleccionar al menos un código de gasto.', type: 'error');
            return;
        }

        $validAmounts = collect($this->distributionAmounts)
            ->filter(fn($a, $id) => (float) $a > 0 && !empty($this->selectedDistributionIds[$id]));

        if ($validAmounts->isEmpty()) {
            $this->dispatch('toast', message: 'Debe seleccionar y asignar monto a al menos un rubro.', type: 'error');
            return;
        }

        foreach ($this->distributionAmounts as $distId => $amount) {
            if (empty($this->selectedDistributionIds[$distId])) {
                continue;
            }

            $amount = (float) $amount;
            if ($amount <= 0) {
                continue;
            }

            $dist = collect($this->distributions)->firstWhere('id', (int) $distId);
            if ($dist && $amount > (float) $dist['available']) {
                $this->dispatch('toast', message: 'El monto para "' . $dist['budget_item'] . '" excede el disponible editable ($' . number_format($dist['available'], 2, ',', '.') . ').', type: 'error');
                return;
            }
        }

        $selectedIds = $validAmounts->keys()->map(fn($id) => (int) $id)->values()->toArray();
        $removedLockedIds = collect($this->lockedDistributionIds)
            ->filter(fn($id) => !in_array((int) $id, $selectedIds, true))
            ->values();

        if ($removedLockedIds->isNotEmpty()) {
            $this->dispatch('toast', message: 'No se pueden quitar distribuciones que ya tienen CDPs asociados. Quite solo las que no tengan movimiento.', type: 'error');
            return;
        }

        DB::beginTransaction();
        try {
            $firstDistId = $validAmounts->keys()->first();

            $convocatoria->update([
                'expense_distribution_id' => $firstDistId,
                'start_date' => $this->convStartDate,
                'start_time' => $this->convStartTime ?: null,
                'end_date' => $this->convEndDate,
                'end_time' => $this->convEndTime ?: null,
                'object' => $this->convObject,
                'justification' => $this->convJustification,
                'assigned_budget' => $this->convAssignedBudget,
                'estimated_duration_days' => (int) $this->convEstimatedDuration,
                'contracting_modality' => $this->convContractingModality,
                'requester_name' => $this->convRequesterName ?: null,
                'requester_position' => $this->convRequesterPosition ?: null,
                'requires_multiple_cdps' => $validAmounts->count() > 1,
            ]);

            $selectedIds = [];
            foreach ($this->distributionAmounts as $distId => $amount) {
                if (empty($this->selectedDistributionIds[$distId])) {
                    continue;
                }

                $amount = (float) $amount;
                if ($amount <= 0) {
                    continue;
                }

                $selectedIds[] = (int) $distId;

                ConvocatoriaDistribution::updateOrCreate(
                    [
                        'convocatoria_id' => $convocatoria->id,
                        'expense_distribution_id' => (int) $distId,
                    ],
                    [
                        'amount' => $amount,
                    ]
                );
            }

            $convocatoria->distributionDetails()
                ->whereNotIn('expense_distribution_id', $selectedIds)
                ->delete();

            DB::commit();

            $this->dispatch('toast', message: 'Convocatoria #' . $convocatoria->formatted_number . ' actualizada exitosamente.', type: 'success');
            $this->closeEditModal();
            $this->viewDetail($convocatoria->id);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetConvocatoriaForm();
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetConvocatoriaForm();
    }

    protected function resetConvocatoriaForm(): void
    {
        $this->contractLockedEdit = false;
        $this->distributionDetailMap = [];
        $this->lockedDistributionIds = [];
        $this->lockedExpenseCodeIds = [];
        $this->selectedDistributionId = '';
        $this->selectedExpenseCodeIds = [];
        $this->groupedDistributions = [];
        $this->distributionAmounts = [];
        $this->selectedDistributionIds = [];
        $this->convObject = '';
        $this->convJustification = '';
        $this->convStartDate = '';
        $this->convStartTime = '';
        $this->convEndDate = '';
        $this->convEndTime = '';
        $this->convAssignedBudget = '';
        $this->convEstimatedDuration = '';
        $this->convContractingModality = 'especial';
        $this->convRequesterName = '';
        $this->convRequesterPosition = '';
        $this->distributions = [];
        $this->resetValidation();
    }

    // === CAMBIAR ESTADO ===

    public function deleteConvocatoria()
    {
        if (!auth()->user()->is_system_admin && !auth()->user()->hasRole('Admin')) {
            $this->dispatch('toast', message: 'Solo los administradores pueden eliminar convocatorias.', type: 'error');
            return;
        }

        if (!$this->convocatoria) return;

        $year = (int) $this->convocatoria->fiscal_year;
        $maxNumber = Convocatoria::where('school_id', $this->schoolId)
            ->where('fiscal_year', $year)
            ->max('convocatoria_number');

        if ($this->convocatoria->convocatoria_number !== $maxNumber) {
            $this->dispatch('toast', message: 'Solo se puede eliminar la última convocatoria registrada. Las anteriores solo pueden ser canceladas.', type: 'error');
            return;
        }

        if ($this->convocatoria->contract) {
            $this->dispatch('toast', message: 'No se puede eliminar una convocatoria que ya tiene un contrato asociado. Primero debe anular o eliminar el contrato.', type: 'error');
            return;
        }

        DB::transaction(function () {
            // Borrar CDPs y sus fuentes
            foreach ($this->convocatoria->cdps as $cdp) {
                $cdp->fundingSources()->delete();
                $cdp->delete();
            }
            
            // Borrar distribuciones
            $this->convocatoria->distributionDetails()->delete();
            
            // Borrar propuestas
            $this->convocatoria->proposals()->delete();

            // Finalmente borrar la convocatoria
            $this->convocatoria->delete();
        });

        $this->dispatch('toast', message: 'Convocatoria eliminada exitosamente.', type: 'success');
        $this->currentView = 'list';
        $this->convocatoriaId = null;
        $this->convocatoria = null;
    }

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
            // Validar que cada distribución de la convocatoria tenga un CDP activo vinculado
            $this->convocatoria->loadMissing([
                'distributionDetails.expenseDistribution.expenseCode',
                'cdps',
            ]);

            $requiredDistIds = $this->convocatoria->distributionDetails->pluck('id');
            $coveredDistIds  = $this->convocatoria->cdps
                ->where('status', 'active')
                ->whereNotNull('convocatoria_distribution_id')
                ->pluck('convocatoria_distribution_id');

            $missingDistIds = $requiredDistIds->diff($coveredDistIds);

            if ($requiredDistIds->isEmpty()) {
                // Convocatoria sin distribuciones detalladas (legacy) — exigir al menos 1 CDP activo
                if ($this->convocatoria->cdps->where('status', 'active')->count() === 0) {
                    $this->dispatch('toast', message: 'Debe registrar al menos un CDP antes de abrir la convocatoria.', type: 'error');
                    $this->showStatusModal = false;
                    return;
                }
            } elseif ($missingDistIds->isNotEmpty()) {
                $missingCodes = $this->convocatoria->distributionDetails
                    ->whereIn('id', $missingDistIds->toArray())
                    ->map(fn($dd) => $dd->expenseDistribution?->expenseCode?->code ?? '?')
                    ->implode(', ');
                $this->dispatch('toast', message: 'Faltan CDPs para los códigos de gasto: ' . $missingCodes, type: 'error');
                $this->showStatusModal = false;
                return;
            }
        }

        if ($this->newStatus === 'evaluation') {
            if ($this->convocatoria->cdps->where('status', 'active')->count() === 0) {
                $this->dispatch('toast', message: 'Debe registrar al menos un CDP antes de pasar a evaluación.', type: 'error');
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

        // Si se cancela, liberar CDPs activos
        if ($this->newStatus === 'cancelled') {
            DB::transaction(function () {
                // Cancelar todos los CDPs activos de esta convocatoria
                $this->convocatoria->cdps()
                    ->where('status', 'active')
                    ->update(['status' => 'cancelled']);

                $this->convocatoria->update([
                    'status' => $this->newStatus,
                ]);
            });

            $this->dispatch('toast', message: 'Convocatoria cancelada. Los CDPs activos fueron liberados.', type: 'success');
            $this->showStatusModal = false;
            $this->viewDetail($this->convocatoria->id);
            return;
        }

        $this->convocatoria->update([
            'status' => $this->newStatus,
            'evaluation_date' => ($this->newStatus === 'awarded' && !$this->convocatoria->evaluation_date) ? now() : $this->convocatoria->evaluation_date,
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

    // === CAMBIAR FECHAS DE CONVOCATORIA ===

    public function openChangeDatesModal()
    {
        if (!auth()->user()->can('precontractual.reschedule')) {
            $this->dispatch('toast', message: 'No tienes permisos para cambiar las fechas.', type: 'error');
            return;
        }

        if (!$this->convocatoria) return;

        if (in_array($this->convocatoria->status, ['awarded', 'cancelled'])) {
            $this->dispatch('toast', message: 'No se pueden cambiar las fechas de una convocatoria en estado ' . $this->convocatoria->status_name . '.', type: 'error');
            return;
        }

        $this->changeDatesStartDate = $this->convocatoria->start_date?->format('Y-m-d') ?? '';
        $this->changeDatesStartTime = $this->convocatoria->start_time ?? '';
        $this->changeDatesEndDate   = $this->convocatoria->end_date?->format('Y-m-d') ?? '';
        $this->changeDatesEndTime   = $this->convocatoria->end_time ?? '';
        $this->resetErrorBag(['changeDatesStartDate', 'changeDatesEndDate']);
        $this->showChangeDatesModal = true;
    }

    public function saveChangedDates()
    {
        if (!auth()->user()->can('precontractual.reschedule')) {
            $this->dispatch('toast', message: 'No tienes permisos para cambiar las fechas.', type: 'error');
            return;
        }

        $this->validate([
            'changeDatesStartDate' => 'required|date',
            'changeDatesStartTime' => 'nullable|date_format:H:i',
            'changeDatesEndDate'   => 'required|date|after_or_equal:changeDatesStartDate',
            'changeDatesEndTime'   => 'nullable|date_format:H:i',
        ], [
            'changeDatesStartDate.required'    => 'La fecha de inicio es obligatoria.',
            'changeDatesStartDate.date'        => 'La fecha de inicio no es válida.',
            'changeDatesEndDate.required'      => 'La fecha de cierre es obligatoria.',
            'changeDatesEndDate.date'          => 'La fecha de cierre no es válida.',
            'changeDatesEndDate.after_or_equal'=> 'La fecha de cierre debe ser igual o posterior a la de inicio.',
            'changeDatesStartTime.date_format' => 'La hora de inicio no es válida (formato HH:MM).',
            'changeDatesEndTime.date_format'   => 'La hora de cierre no es válida (formato HH:MM).',
        ]);

        $this->convocatoria->update([
            'start_date' => $this->changeDatesStartDate,
            'start_time' => $this->changeDatesStartTime ?: null,
            'end_date'   => $this->changeDatesEndDate,
            'end_time'   => $this->changeDatesEndTime ?: null,
        ]);

        $this->dispatch('toast', message: 'Fechas de la convocatoria actualizadas correctamente.', type: 'success');
        $this->showChangeDatesModal = false;
        $this->viewDetail($this->convocatoria->id);
    }

    public function closeChangeDatesModal()
    {
        $this->showChangeDatesModal = false;
        $this->changeDatesStartDate = '';
        $this->changeDatesStartTime = '';
        $this->changeDatesEndDate   = '';
        $this->changeDatesEndTime   = '';
        $this->resetErrorBag(['changeDatesStartDate', 'changeDatesEndDate']);
    }

    // === CDP ===

    protected function loadAvailableDistributionsForCdp(?Cdp $currentCdp = null): void
    {
        $this->convocatoria->loadMissing([
            'distributionDetails.expenseDistribution.budget.budgetItem',
            'distributionDetails.expenseDistribution.budget.fundingSource',
            'distributionDetails.expenseDistribution.expenseCode',
            'cdps',
        ]);

        $coveredDistributionIds = $this->convocatoria->cdps
            ->where('status', 'active')
            ->when($currentCdp, fn($collection) => $collection->where('id', '!=', $currentCdp->id))
            ->whereNotNull('convocatoria_distribution_id')
            ->pluck('convocatoria_distribution_id')
            ->toArray();

        $availableDistributions = $this->convocatoria->distributionDetails
            ->filter(function ($dd) use ($coveredDistributionIds, $currentCdp) {
                if ($currentCdp && (int) $dd->id === (int) $currentCdp->convocatoria_distribution_id) {
                    return true;
                }

                return !in_array($dd->id, $coveredDistributionIds);
            })
            ->values();

        $this->availableDistributions = $availableDistributions->map(function ($dd) {
            $expDist = $dd->expenseDistribution;
            $budget  = $expDist?->budget;
            $item    = $budget?->budgetItem;
            $source  = $budget?->fundingSource;
            $expCode = $expDist?->expenseCode;

            return [
                'id' => $dd->id,
                'amount' => (float) $dd->amount,
                'expense_code' => ($expCode?->code ?? '') . ' - ' . ($expCode?->name ?? ''),
                'budget_item_id' => $item?->id,
                'budget_item' => ($item?->code ?? '') . ' - ' . ($item?->name ?? ''),
                'funding_source_id' => $source?->id,
                'funding_source' => ($source?->code ?? '') . ' - ' . ($source?->name ?? ''),
                'budget_id' => $budget?->id,
            ];
        })->toArray();
    }

    protected function resetCdpForm(): void
    {
        $this->editingCdpId = null;
        $this->cdpDistributionId = '';
        $this->cdpBudgetItemId = '';
        $this->cdpFundingSources = [];
        $this->availableFundingSources = [];
        $this->availableDistributions = [];
        $this->resetValidation();
    }

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

        $this->resetCdpForm();
        $this->loadAvailableDistributionsForCdp();

        if (empty($this->availableDistributions)) {
            $this->dispatch('toast', message: 'Todas las distribuciones de esta convocatoria ya tienen CDP activo.', type: 'info');
            return;
        }

        // Si solo hay una distribución, auto-seleccionarla
        if (count($this->availableDistributions) === 1) {
            $this->cdpDistributionId = $this->availableDistributions[0]['id'];
            $this->updatedCdpDistributionId($this->cdpDistributionId);
        }

        $this->showCdpModal = true;
    }

    public function openEditCdpModal($cdpId)
    {
        if (!auth()->user()->can('precontractual.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        if (!in_array($this->convocatoria->status, ['draft']) && !auth()->user()->is_system_admin && !auth()->user()->hasRole('Admin')) {
            $this->dispatch('toast', message: 'Solo se pueden editar CDPs cuando la convocatoria está en borrador.', type: 'error');
            return;
        }

        $cdp = Cdp::with(['fundingSources.fundingSource', 'contractRp'])
            ->forSchool($this->schoolId)
            ->where('convocatoria_id', $this->convocatoria->id)
            ->findOrFail($cdpId);

        if ($cdp->status !== 'active') {
            $this->dispatch('toast', message: 'Solo se pueden editar CDPs activos.', type: 'error');
            return;
        }

        if ($cdp->contractRp) {
            $this->dispatch('toast', message: 'No se puede editar este CDP porque ya está siendo usado por un RP o contrato.', type: 'error');
            return;
        }

        $this->resetCdpForm();
        $this->editingCdpId = $cdp->id;
        $this->loadAvailableDistributionsForCdp($cdp);

        $this->cdpDistributionId = (string) $cdp->convocatoria_distribution_id;
        $this->updatedCdpDistributionId($this->cdpDistributionId);

        $selectedSourceAvailability = collect($this->availableFundingSources)->keyBy('id');
        $this->cdpFundingSources = $cdp->fundingSources->map(function ($fs) use ($selectedSourceAvailability) {
            $availability = $selectedSourceAvailability->get($fs->funding_source_id);

            return [
                'id' => $fs->funding_source_id,
                'name' => $fs->fundingSource?->code . ' - ' . $fs->fundingSource?->name,
                'available' => (float) ($availability['available'] ?? $fs->amount),
                'budget_id' => $fs->budget_id,
                'amount' => (float) $fs->amount,
            ];
        })->toArray();

        $this->showCdpModal = true;
    }

    public function updatedCdpDistributionId($value)
    {
        $this->cdpFundingSources       = [];
        $this->availableFundingSources = [];
        $this->cdpBudgetItemId         = '';

        if (empty($value)) return;

        $dist = collect($this->availableDistributions)->firstWhere('id', (int) $value);
        if (!$dist) return;

        $this->cdpBudgetItemId = $dist['budget_item_id'];

        // Saldo disponible de la fuente de financiación para este presupuesto
        $budget = \App\Models\Budget::find($dist['budget_id']);
        if (!$budget || !$dist['funding_source_id']) {
            $this->availableFundingSources = [];
            return;
        }

        $source = \App\Models\FundingSource::find($dist['funding_source_id']);
        if (!$source) return;

        // Monto ya cubierto por CDPs activos de ESTA convocatoria para esta distribución
        // (CDPs con convocatoria_distribution_id = $value)
        $alreadyCoveredThisDist = (float) \App\Models\Cdp::where('convocatoria_id', $this->convocatoria->id)
            ->where('convocatoria_distribution_id', $value)
            ->where('status', 'active')
            ->when($this->editingCdpId, fn($q) => $q->where('id', '!=', $this->editingCdpId))
            ->sum('total_amount');

        // Saldo disponible en la fuente = monto de la distribución - ya cubierto
        $availableForDist = max(0, (float) $dist['amount'] - $alreadyCoveredThisDist);

        if ($availableForDist <= 0) {
            $this->availableFundingSources = [];
            return;
        }

        $this->availableFundingSources = [[
            'id'            => $source->id,
            'name'          => $source->code . ' - ' . $source->name,
            'type'          => $source->type_name ?? '',
            'available'     => $availableForDist,
            'budget_id'     => $budget->id,
            'budget_amount' => (float) $dist['amount'],
            'reserved'      => $alreadyCoveredThisDist,
        ]];
    }

    // Mantener compatibilidad legacy si algo invoca updatedCdpBudgetItemId
    public function updatedCdpBudgetItemId($value)
    {
        // ya no se usa directamente; la selección es por distribución
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
            'amount' => $source['available'],
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
            'cdpDistributionId' => 'required|exists:convocatoria_distributions,id',
            'cdpFundingSources' => 'required|array|min:1',
            'cdpFundingSources.*.amount' => 'required|numeric|min:0.01',
        ], [
            'cdpDistributionId.required' => 'Seleccione una distribución/código de gasto.',
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
                'school_id'                    => $this->schoolId,
                'convocatoria_id'              => $this->convocatoria->id,
                'convocatoria_distribution_id' => $this->cdpDistributionId,
                'cdp_number'                   => Cdp::getNextCdpNumber($this->schoolId, $this->filterYear),
                'fiscal_year'                  => $this->filterYear,
                'budget_item_id'               => $this->cdpBudgetItemId,
                'total_amount'                 => $totalAmount,
                'status'                       => 'active',
                'created_by'                   => auth()->id(),
            ]);

            foreach ($this->cdpFundingSources as $fs) {
                // available_balance_at_creation = disponibilidad de la distribución
                // de gasto antes de crear este CDP (no el saldo total de la fuente)
                CdpFundingSource::create([
                    'cdp_id'                       => $cdp->id,
                    'funding_source_id'            => $fs['id'],
                    'budget_id'                    => $fs['budget_id'],
                    'amount'                       => $fs['amount'],
                    'available_balance_at_creation' => $fs['available'],
                ]);
            }
        });

        $this->dispatch('toast', message: 'CDP registrado exitosamente.', type: 'success');
        $this->closeCdpModal();
        $this->viewDetail($this->convocatoria->id);
    }

    public function saveCdpEdit()
    {
        if (!auth()->user()->can('precontractual.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        if (!$this->editingCdpId) {
            return;
        }

        if (!in_array($this->convocatoria->status, ['draft']) && !auth()->user()->is_system_admin && !auth()->user()->hasRole('Admin')) {
            $this->dispatch('toast', message: 'Solo se pueden editar CDPs cuando la convocatoria está en borrador.', type: 'error');
            return;
        }

        $cdp = Cdp::with(['fundingSources', 'contractRp'])
            ->forSchool($this->schoolId)
            ->where('convocatoria_id', $this->convocatoria->id)
            ->findOrFail($this->editingCdpId);

        if ($cdp->status !== 'active') {
            $this->dispatch('toast', message: 'Solo se pueden editar CDPs activos.', type: 'error');
            return;
        }

        if ($cdp->contractRp) {
            $this->dispatch('toast', message: 'No se puede editar este CDP porque ya está siendo usado por un RP o contrato.', type: 'error');
            return;
        }

        $this->validate([
            'cdpDistributionId' => 'required|exists:convocatoria_distributions,id',
            'cdpFundingSources' => 'required|array|min:1',
            'cdpFundingSources.*.amount' => 'required|numeric|min:0.01',
        ], [
            'cdpDistributionId.required' => 'Seleccione una distribución/código de gasto.',
            'cdpFundingSources.required' => 'Agregue al menos una fuente.',
            'cdpFundingSources.min' => 'Agregue al menos una fuente.',
            'cdpFundingSources.*.amount.required' => 'Ingrese un monto.',
            'cdpFundingSources.*.amount.min' => 'El monto debe ser mayor a 0.',
        ]);

        foreach ($this->cdpFundingSources as $i => $fs) {
            if ((float) $fs['amount'] > (float) $fs['available']) {
                $this->addError("cdpFundingSources.{$i}.amount", 'Excede el saldo disponible.');
                return;
            }
        }

        $totalAmount = collect($this->cdpFundingSources)->sum(fn($fs) => (float) ($fs['amount'] ?? 0));

        DB::transaction(function () use ($cdp, $totalAmount) {
            $cdp->update([
                'convocatoria_distribution_id' => $this->cdpDistributionId,
                'budget_item_id' => $this->cdpBudgetItemId,
                'total_amount' => $totalAmount,
            ]);

            $cdp->fundingSources()->delete();

            foreach ($this->cdpFundingSources as $fs) {
                CdpFundingSource::create([
                    'cdp_id' => $cdp->id,
                    'funding_source_id' => $fs['id'],
                    'budget_id' => $fs['budget_id'],
                    'amount' => $fs['amount'],
                    'available_balance_at_creation' => $fs['available'],
                ]);
            }
        });

        $this->dispatch('toast', message: 'CDP actualizado exitosamente.', type: 'success');
        $this->closeCdpModal();
        $this->viewDetail($this->convocatoria->id);
    }

    public function closeCdpModal()
    {
        $this->showCdpModal = false;
        $this->resetCdpForm();
    }

    public function cancelCdp($cdpId)
    {
        if (!auth()->user()->can('precontractual.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        $cdp = Cdp::forSchool($this->schoolId)->where('convocatoria_id', $this->convocatoria->id)->findOrFail($cdpId);
        
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
            'proposalReceivedDate' => 'required|date',
            'proposalReceivedTime' => 'required',
            'proposalSubtotal' => 'required|numeric|min:0.01',
            'proposalIva' => 'nullable|numeric|min:0',
        ], [
            'proposalSupplierId.required' => 'Seleccione un proveedor.',
            'proposalReceivedDate.required' => 'La fecha de recepción es obligatoria.',
            'proposalReceivedTime.required' => 'La hora de recepción es obligatoria.',
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
            'received_date' => $this->proposalReceivedDate,
            'received_time' => $this->proposalReceivedTime,
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

    public function openEditProposalModal($id)
    {
        if (!auth()->user()->is_system_admin && !auth()->user()->hasRole('Admin')) {
            $this->dispatch('toast', message: 'Solo los administradores pueden editar propuestas.', type: 'error');
            return;
        }

        $proposal = Proposal::where('convocatoria_id', $this->convocatoria->id)->findOrFail($id);

        if ($this->convocatoria->contract && $this->convocatoria->contract->hasPaymentOrders()) {
            $this->dispatch('toast', message: 'No se puede editar el valor porque el contrato ya tiene órdenes de pago.', type: 'error');
            return;
        }

        $this->suppliers = Supplier::where('school_id', $this->schoolId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get()
            ->map(fn($s) => ['id' => $s->id, 'name' => $s->full_name . ' (' . $s->full_document . ')'])
            ->toArray();

        $this->editingProposalId = $proposal->id;
        $this->proposalSupplierId = $proposal->supplier_id;
        $this->proposalReceivedDate = $proposal->received_date?->format('Y-m-d');
        $this->proposalReceivedTime = $proposal->received_time;
        $this->proposalSubtotal = $proposal->subtotal;
        $this->proposalIva = $proposal->iva;
        
        $this->showProposalModal = true;
    }

    public function saveProposalEdit()
    {
        if (!auth()->user()->is_system_admin && !auth()->user()->hasRole('Admin')) {
            $this->dispatch('toast', message: 'Solo los administradores pueden editar propuestas.', type: 'error');
            return;
        }

        $this->validate([
            'proposalSupplierId' => 'required|exists:suppliers,id',
            'proposalReceivedDate' => 'required|date',
            'proposalReceivedTime' => 'required',
            'proposalSubtotal' => 'required|numeric|min:0.01',
            'proposalIva' => 'nullable|numeric|min:0',
        ], [
            'proposalSupplierId.required' => 'Seleccione un proveedor.',
            'proposalReceivedDate.required' => 'La fecha de recepción es obligatoria.',
            'proposalReceivedTime.required' => 'La hora de recepción es obligatoria.',
            'proposalSubtotal.required' => 'El subtotal es obligatorio.',
            'proposalSubtotal.min' => 'El subtotal debe ser mayor a 0.',
        ]);

        $proposal = Proposal::where('convocatoria_id', $this->convocatoria->id)->findOrFail($this->editingProposalId);

        // Verificar que el proveedor no tenga ya propuesta en esta convocatoria
        $exists = Proposal::where('convocatoria_id', $this->convocatoria->id)
            ->where('supplier_id', $this->proposalSupplierId)
            ->where('id', '!=', $this->editingProposalId)
            ->exists();

        if ($exists) {
            $this->dispatch('toast', message: 'Este proveedor ya tiene una propuesta registrada.', type: 'error');
            return;
        }

        if ($this->convocatoria->contract && $this->convocatoria->contract->hasPaymentOrders()) {
            $this->dispatch('toast', message: 'No se puede guardar porque el contrato ya tiene órdenes de pago.', type: 'error');
            return;
        }

        $iva = $this->proposalIva ?: 0;
        $total = $this->proposalSubtotal + $iva;

        DB::transaction(function () use ($proposal, $iva, $total) {
            $proposal->update([
                'supplier_id' => $this->proposalSupplierId,
                'received_date' => $this->proposalReceivedDate,
                'received_time' => $this->proposalReceivedTime,
                'subtotal' => $this->proposalSubtotal,
                'iva' => $iva,
                'total' => $total,
            ]);

            if ($proposal->is_selected && $this->convocatoria->contract) {
                // Update contract if this is the winning proposal
                $this->convocatoria->contract->update([
                    'subtotal' => $this->proposalSubtotal,
                    'iva' => $iva,
                    'total' => $total,
                    'original_total' => $total,
                    'supplier_id' => $this->proposalSupplierId,
                ]);
            }
        });

        $this->dispatch('toast', message: 'Propuesta actualizada exitosamente.', type: 'success');
        $this->closeProposalModal();
        $this->viewDetail($this->convocatoria->id);
    }

    public function closeProposalModal()
    {
        $this->showProposalModal = false;
        $this->editingProposalId = null;
        $this->proposalSupplierId = '';
        $this->proposalReceivedDate = '';
        $this->proposalReceivedTime = '';
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

        // Si ya fue evaluada, no permitir re-evaluar
        if ($this->convocatoria->evaluation_date) {
            $this->dispatch('toast', message: 'Esta convocatoria ya fue evaluada el ' . $this->convocatoria->evaluation_date->format('d/m/Y h:i A') . '. No se puede volver a evaluar.', type: 'warning');
            return;
        }

        if ($this->convocatoria->proposals->count() < 1) {
            $this->dispatch('toast', message: 'Debe haber al menos una propuesta para evaluar.', type: 'error');
            return;
        }

        $this->proposalScores = $this->convocatoria->proposals->sortByDesc('score')->values()->map(fn($p) => [
            'id' => $p->id,
            'supplier' => $p->supplier?->full_name ?? 'Sin proveedor',
            'total' => $p->total,
            'score' => $p->score ?? '',
            'is_selected' => (bool) $p->is_selected,
        ])->toArray();

        // Pre-llenar con fecha y hora actual (o fecha de cierre si es posterior a hoy)
        $minDate = $this->convocatoria->end_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->evaluationDate = now()->format('Y-m-d') >= $minDate ? now()->format('Y-m-d') : $minDate;
        $this->evaluationTime = now()->format('H:i');

        $this->showEvaluateModal = true;
    }

    public function saveEvaluation()
    {
        if (!auth()->user()->can('precontractual.evaluate')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        // Verificar que no haya sido evaluada previamente
        $this->convocatoria->refresh();
        if ($this->convocatoria->evaluation_date) {
            $this->dispatch('toast', message: 'Esta convocatoria ya fue evaluada. No se puede volver a evaluar.', type: 'warning');
            $this->showEvaluateModal = false;
            return;
        }

        // Validar fecha y hora de evaluación
        if (empty($this->evaluationDate)) {
            $this->addError('evaluationDate', 'La fecha de evaluación es obligatoria.');
            return;
        }
        if ($this->convocatoria->end_date && $this->evaluationDate < $this->convocatoria->end_date->format('Y-m-d')) {
            $this->addError('evaluationDate', 'La fecha de evaluación debe ser igual o posterior a la fecha de cierre (' . $this->convocatoria->end_date->format('d/m/Y') . ').');
            return;
        }
        if (empty($this->evaluationTime)) {
            $this->addError('evaluationTime', 'La hora de evaluación es obligatoria.');
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

        $evaluationDateTime = \Carbon\Carbon::parse($this->evaluationDate . ' ' . $this->evaluationTime);

        DB::transaction(function () use ($evaluationDateTime) {
            // Primero resetear todas las propuestas de esta convocatoria
            Proposal::where('convocatoria_id', $this->convocatoria->id)
                ->update(['is_selected' => false]);

            // Obtener el ID de la propuesta seleccionada
            $selectedId = collect($this->proposalScores)
                ->first(fn($ps) => !empty($ps['is_selected']))['id'] ?? null;

            // Guardar puntajes y marcar la ganadora
            foreach ($this->proposalScores as $ps) {
                Proposal::where('id', $ps['id'])->update([
                    'score' => $ps['score'],
                    'is_selected' => ($ps['id'] == $selectedId),
                ]);
            }

            $this->convocatoria->update([
                'evaluation_date' => $evaluationDateTime,
            ]);
        });

        $this->dispatch('toast', message: 'Evaluación guardada exitosamente.', type: 'success');
        $this->showEvaluateModal = false;
        $this->viewDetail($this->convocatoria->id);
    }

    public function selectProposal($index)
    {
        $index = (int) $index;
        foreach ($this->proposalScores as $i => $ps) {
            $this->proposalScores[$i]['is_selected'] = ((int) $i === $index);
        }
    }

    public function closeEvaluateModal()
    {
        $this->showEvaluateModal = false;
        $this->proposalScores = [];
        $this->evaluationDate = '';
        $this->evaluationTime = '';
        $this->resetErrorBag(['evaluationDate', 'evaluationTime']);
    }

    // === ELIMINAR ===

    public function confirmDeleteConvocatoria($id)
    {
        if (!auth()->user()->can('precontractual.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos.', type: 'error');
            return;
        }

        $user = Auth::user();
        $isAdmin = $user instanceof \App\Models\User && $user->hasRole('Admin');
        $conv = Convocatoria::with('contract')->forSchool($this->schoolId)->findOrFail($id);

        $canDelete = $conv->status === 'draft'
            || ($isAdmin && $conv->status === 'awarded');

        if (!$canDelete) {
            $this->dispatch('toast', message: 'Solo los administradores pueden eliminar convocatorias adjudicadas.', type: 'error');
            return;
        }

        if ($conv->contract) {
            $this->dispatch('toast', message: 'No se puede eliminar la convocatoria porque ya tiene un contrato asociado.', type: 'error');
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
            $convocatoria = Convocatoria::with(['cdps.fundingSources', 'contract'])
                ->forSchool($this->schoolId)
                ->findOrFail($this->itemToDelete->id);

            $user = Auth::user();
            $isAdmin = $user instanceof \App\Models\User && $user->hasRole('Admin');

            $canDelete = $convocatoria->status === 'draft'
                || ($isAdmin && $convocatoria->status === 'awarded');

            if (!$canDelete) {
                $this->dispatch('toast', message: 'Solo los administradores pueden eliminar convocatorias adjudicadas.', type: 'error');
                $this->closeDeleteModal();
                return;
            }

            if ($convocatoria->contract) {
                $this->dispatch('toast', message: 'No se puede eliminar la convocatoria porque ya tiene un contrato asociado.', type: 'error');
                $this->closeDeleteModal();
                return;
            }

            // Eliminar CDPs, propuestas y distribuciones asociadas
            DB::transaction(function () use ($convocatoria) {
                foreach ($convocatoria->cdps as $cdp) {
                    $cdp->fundingSources()->delete();
                    $cdp->delete();
                }
                $convocatoria->proposals()->delete();
                $convocatoria->distributionDetails()->delete();
                $convocatoria->delete();

                $this->resequenceConvocatoriasForYear($this->schoolId, $convocatoria->fiscal_year);
                $this->resequenceCdpsForYear($this->schoolId, $convocatoria->fiscal_year);
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

    private function resequenceConvocatoriasForYear(int $schoolId, int $fiscalYear): void
    {
        $convocatoriaIds = Convocatoria::query()
            ->forSchool($schoolId)
            ->forYear($fiscalYear)
            ->orderBy('convocatoria_number')
            ->orderBy('id')
            ->pluck('id');

        if ($convocatoriaIds->isEmpty()) {
            return;
        }

        foreach ($convocatoriaIds as $index => $convocatoriaId) {
            Convocatoria::whereKey($convocatoriaId)->update([
                'convocatoria_number' => 100000 + $index,
            ]);
        }

        foreach ($convocatoriaIds as $index => $convocatoriaId) {
            Convocatoria::whereKey($convocatoriaId)->update([
                'convocatoria_number' => $index + 1,
            ]);
        }
    }

    private function resequenceCdpsForYear(int $schoolId, int $fiscalYear): void
    {
        $hasProtectedCdps = Cdp::query()
            ->forSchool($schoolId)
            ->forYear($fiscalYear)
            ->where(function ($query) {
                $query->whereNull('convocatoria_id')
                    ->orWhereHas('contractRp');
            })
            ->exists();

        if ($hasProtectedCdps) {
            return;
        }

        $cdpIds = Cdp::query()
            ->forSchool($schoolId)
            ->forYear($fiscalYear)
            ->orderBy('cdp_number')
            ->orderBy('id')
            ->pluck('id');

        if ($cdpIds->isEmpty()) {
            return;
        }

        foreach ($cdpIds as $index => $cdpId) {
            Cdp::whereKey($cdpId)->update([
                'cdp_number' => 100000 + $index,
            ]);
        }

        foreach ($cdpIds as $index => $cdpId) {
            Cdp::whereKey($cdpId)->update([
                'cdp_number' => $index + 1,
            ]);
        }
    }

    // === IMPRIMIR DOCUMENTOS ===

    public function openPrintModal()
    {
        $this->printDocuments = [
            'estudios_previos' => false,
            'disponibilidad_presupuestal' => false,
            'requisicion_necesidades' => false,
            'certificado_plan_compras' => false,
            'convocatoria_veedurias' => false,
            'invitacion_cotizar' => false,
            'acta_evaluacion' => false,
            'aceptacion_propuesta' => false,
            'certificado_disponibilidad' => false,
        ];
        $this->showPrintModal = true;
    }

    public function closePrintModal()
    {
        $this->showPrintModal = false;
    }

    public function printSelectedDocuments()
    {
        $selected = array_filter($this->printDocuments);

        if (empty($selected)) {
            $this->dispatch('toast', message: 'Seleccione al menos un documento para imprimir.', type: 'error');
            return;
        }

        // Por ahora solo tenemos estudios previos
        if (!empty($selected['estudios_previos'])) {
            $this->dispatch('openPdfWindow', url: route('precontractual.estudios-previos.pdf', $this->convocatoriaId));
        }

        if (!empty($selected['disponibilidad_presupuestal'])) {
            $this->dispatch('openPdfWindow', url: route('precontractual.disponibilidad-presupuestal.pdf', $this->convocatoriaId));
        }

        if (!empty($selected['requisicion_necesidades'])) {
            $this->dispatch('openPdfWindow', url: route('precontractual.requisicion-necesidades.pdf', $this->convocatoriaId));
        }

        if (!empty($selected['certificado_plan_compras'])) {
            $this->dispatch('openPdfWindow', url: route('precontractual.certificado-plan-compras.pdf', $this->convocatoriaId));
        }

        if (!empty($selected['convocatoria_veedurias'])) {
            $this->dispatch('openPdfWindow', url: route('precontractual.convocatoria-veedurias.pdf', $this->convocatoriaId));
        }

        if (!empty($selected['invitacion_cotizar'])) {
            $this->dispatch('openPdfWindow', url: route('precontractual.invitacion-cotizar.pdf', $this->convocatoriaId));
        }

        if (!empty($selected['acta_evaluacion'])) {
            $this->dispatch('openPdfWindow', url: route('precontractual.acta-evaluacion.pdf', $this->convocatoriaId));
        }

        if (!empty($selected['aceptacion_propuesta'])) {
            $this->dispatch('openPdfWindow', url: route('precontractual.aceptacion-propuesta.pdf', $this->convocatoriaId));
        }

        if (!empty($selected['certificado_disponibilidad'])) {
            // Generar un PDF individual por cada CDP activo
            $convocatoria = Convocatoria::with('cdps')->find($this->convocatoriaId);
            if ($convocatoria) {
                foreach ($convocatoria->cdps->where('status', '!=', 'cancelled') as $cdp) {
                    $this->dispatch('openPdfWindow', url: route('precontractual.certificado-disponibilidad.pdf', [$this->convocatoriaId, $cdp->id]));
                }
            }
        }

        $this->closePrintModal();
    }

    // === FILTROS ===

    public function clearFilters()
    {
        $this->filterYear = \App\Models\School::find($this->schoolId)?->current_validity ?? date('Y');
        $this->filterStatus = '';
        $this->search = '';
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.precontractual-management');
    }

    public function deleteAllDataForMikSoftware()
    {
        if (auth()->user()->email !== 'softwaremik@gmail.com') {
            abort(403);
        }

        DB::transaction(function () {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // 1. Postcontractual (Payment Orders and their lines)
            $paymentOrderIds = \App\Models\PaymentOrder::where('school_id', $this->schoolId)->pluck('id');
            if ($paymentOrderIds->isNotEmpty()) {
                \App\Models\PaymentOrderTaxLine::whereIn('payment_order_id', $paymentOrderIds)->delete();
                \App\Models\PaymentOrderExpenseLine::whereIn('payment_order_id', $paymentOrderIds)->delete();
                \App\Models\PaymentOrderBankLine::whereIn('payment_order_id', $paymentOrderIds)->delete();
                if (\Illuminate\Support\Facades\Schema::hasTable('payment_order_discounts')) {
                    DB::table('payment_order_discounts')->whereIn('payment_order_id', $paymentOrderIds)->delete();
                }
                \App\Models\PaymentOrder::whereIn('id', $paymentOrderIds)->delete();
            }

            // Acta Recepcion (if any)
            $contractIds = \App\Models\Contract::where('school_id', $this->schoolId)->pluck('id');
            if ($contractIds->isNotEmpty() && \Illuminate\Support\Facades\Schema::hasTable('actas_recepcion')) {
                $actaIds = DB::table('actas_recepcion')->whereIn('contract_id', $contractIds)->pluck('id');
                if ($actaIds->isNotEmpty()) {
                    if (\Illuminate\Support\Facades\Schema::hasTable('acta_recepcion_lines')) {
                        DB::table('acta_recepcion_lines')->whereIn('acta_recepcion_id', $actaIds)->delete();
                    }
                    DB::table('actas_recepcion')->whereIn('id', $actaIds)->delete();
                }
            }

            // 2. Contractual (Contracts and RPs)
            if ($contractIds->isNotEmpty()) {
                $rpIds = \App\Models\ContractRp::whereIn('contract_id', $contractIds)->pluck('id');
                if ($rpIds->isNotEmpty()) {
                    \App\Models\RpFundingSource::whereIn('contract_rp_id', $rpIds)->delete();
                    \App\Models\ContractRp::whereIn('id', $rpIds)->delete();
                }
            }
            \App\Models\Contract::where('school_id', $this->schoolId)->delete();

            // 3. Precontractual (Convocatorias, Proposals, CDPs)
            $convocatoriaIds = \App\Models\Convocatoria::where('school_id', $this->schoolId)->pluck('id');
            if ($convocatoriaIds->isNotEmpty()) {
                \App\Models\Proposal::whereIn('convocatoria_id', $convocatoriaIds)->delete();
                \App\Models\ConvocatoriaDistribution::whereIn('convocatoria_id', $convocatoriaIds)->delete();
            }
            
            $cdpIds = \App\Models\Cdp::where('school_id', $this->schoolId)->pluck('id');
            if ($cdpIds->isNotEmpty()) {
                \App\Models\CdpFundingSource::whereIn('cdp_id', $cdpIds)->delete();
                \App\Models\Cdp::whereIn('id', $cdpIds)->delete();
            }

            \App\Models\Convocatoria::where('school_id', $this->schoolId)->delete();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        });

        $this->dispatch('toast', message: 'Toda la etapa precontractual, contractual y postcontractual ha sido eliminada. Los CDPs y RPs han quedado libres.', type: 'success');
        $this->currentView = 'list';
        $this->convocatoria = null;
        $this->convocatoriaId = null;
    }
}

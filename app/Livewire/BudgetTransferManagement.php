<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\BudgetTransfer;
use App\Models\ExpenseCode;
use App\Models\ExpenseDistribution;
use App\Models\FundingSource;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class BudgetTransferManagement extends Component
{
    use WithPagination;

    public $schoolId;

    // Búsqueda y filtros
    public $search = '';
    public $filterYear = '';
    public $perPage = 15;

    // Modal crear traslado
    public $showModal = false;

    // Paso 1: Fuentes de financiación (origen y destino)
    public $selected_funding_source_id = '';
    public $destination_funding_source_id = '';

    // Contracrédito (origen - sale dinero)
    public $source_distribution_id = '';

    // Crédito (destino - entra dinero)
    public $destination_expense_code_id = '';

    // Datos del traslado
    public $amount = '';
    public $reason = '';
    public $document_number = '';
    public $document_date = '';

    // Datos dinámicos para selects en creación
    public $availableFundingSources = [];
    public $destinationFundingSources = [];
    public $sourceDistributions = [];
    public $destinationExpenseCodes = [];

    // Info de selección actual en creación
    public $selectedSourceInfo = [];
    public $selectedDestinationInfo = [];

    // Modal de detalle
    public $showDetailModal = false;
    public $detailTransfer = null;

    // ======================================================
    // MODAL EDITAR TRASLADO
    // ======================================================
    public $showEditModal = false;
    public $editingTransfer = null;
    public $editTransferId = null;
    public $edit_source_funding_source_id = '';
    public $edit_destination_funding_source_id = '';
    public $edit_source_distribution_id = '';
    public $edit_destination_expense_code_id = '';
    public $edit_amount = '';
    public $edit_reason = '';
    public $edit_document_number = '';
    public $edit_document_date = '';

    public $editAvailableFundingSources = [];
    public $editDestinationFundingSources = [];
    public $editSourceDistributions = [];
    public $editDestinationExpenseCodes = [];
    public $editSelectedSourceInfo = [];
    public $editSelectedDestinationInfo = [];

    // ======================================================
    // MODAL ELIMINAR TRASLADO
    // ======================================================
    public $showDeleteModal = false;
    public $transferToDelete = null;
    public $deleteWarning = null;

    // Editar fecha inline desde detalle
    public $editingTransferId = null;
    public $editingTransferDate = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterYear' => ['except' => ''],
    ];

    protected function rules()
    {
        return [
            'selected_funding_source_id' => 'required|exists:funding_sources,id',
            'destination_funding_source_id' => 'required|exists:funding_sources,id',
            'source_distribution_id' => 'required|exists:expense_distributions,id',
            'destination_expense_code_id' => 'required|exists:expense_codes,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:10',
            'document_number' => 'nullable|string|max:50',
            'document_date' => 'required|date',
        ];
    }

    protected $messages = [
        'selected_funding_source_id.required' => 'Debe seleccionar una fuente de financiación de origen.',
        'destination_funding_source_id.required' => 'Debe seleccionar una fuente de financiación de destino.',
        'source_distribution_id.required' => 'Debe seleccionar el gasto origen (contracrédito).',
        'destination_expense_code_id.required' => 'Debe seleccionar el gasto destino (crédito).',
        'amount.required' => 'El monto es obligatorio.',
        'amount.min' => 'El monto debe ser mayor a 0.',
        'reason.required' => 'La justificación es obligatoria.',
        'reason.min' => 'La justificación debe tener al menos 10 caracteres.',
        'document_date.required' => 'La fecha de realización es obligatoria.',
        'document_date.date' => 'La fecha de realización no es válida.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('budget_transfers.view'), 403, 'No tienes permisos para ver traslados presupuestales.');

        $this->schoolId = session('selected_school_id');

        if (!$this->schoolId) {
            $school = auth()->user()->hasRole('Admin')
                ? \App\Models\School::first()
                : auth()->user()->schools()->first();

            if ($school) {
                session(['selected_school_id' => $school->id]);
                $this->schoolId = $school->id;
            } else {
                session()->flash('error', 'Debes seleccionar un colegio primero.');
                $this->redirect(route('dashboard'));
                return;
            }
        }

        $this->schoolId = (int) $this->schoolId;
        $this->filterYear = \App\Models\School::find($this->schoolId)?->current_validity ?? date('Y');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterYear()
    {
        $this->resetPage();
    }

    /**
     * Cargar fuentes de financiación que tengan presupuestos de gasto para el año activo
     */
    public function loadAvailableFundingSources()
    {
        $this->availableFundingSources = FundingSource::whereHas('budgets', function ($q) {
            $q->forSchool($this->schoolId)
              ->forYear((int) $this->filterYear)
              ->byType('expense')
              ->whereHas('distributions', fn($dq) => $dq->where('amount', '>', 0));
        })
            ->active()
            ->orderBy('code')
            ->get()
            ->map(fn($fs) => [
                'id' => $fs->id,
                'name' => $fs->code . ' - ' . $fs->name,
            ])
            ->toArray();

        $this->destinationFundingSources = FundingSource::whereHas('budgets', function ($q) {
            $q->forSchool($this->schoolId)
              ->forYear((int) $this->filterYear)
              ->byType('expense');
        })
            ->active()
            ->orderBy('code')
            ->get()
            ->map(fn($fs) => [
                'id' => $fs->id,
                'name' => $fs->code . ' - ' . $fs->name,
            ])
            ->toArray();
    }

    /**
     * Al seleccionar fuente de origen en creación
     */
    public function updatedSelectedFundingSourceId($value)
    {
        $this->source_distribution_id = '';
        $this->sourceDistributions = [];
        $this->selectedSourceInfo = [];

        // Por defecto, sincronizar fuente destino con la fuente de origen
        if (!$this->destination_funding_source_id || $this->destination_funding_source_id === $this->selected_funding_source_id) {
            $this->destination_funding_source_id = $value;
        }

        if ($value) {
            $this->loadSourceDistributions();
            $this->loadDestinationExpenseCodes();
        }
    }

    /**
     * Al seleccionar fuente de destino en creación
     */
    public function updatedDestinationFundingSourceId($value)
    {
        $this->destination_expense_code_id = '';
        $this->selectedDestinationInfo = [];

        if ($value) {
            $this->loadDestinationExpenseCodes();
        }
    }

    /**
     * Cargar distribuciones de gasto con saldo disponible > 0 para la fuente de origen
     */
    public function loadSourceDistributions()
    {
        $this->sourceDistributions = ExpenseDistribution::forSchool($this->schoolId)
            ->where('amount', '>', 0)
            ->whereHas('budget', function ($q) {
                $q->forYear((int) $this->filterYear)
                  ->byType('expense')
                  ->byFundingSource((int) $this->selected_funding_source_id);
            })
            ->with(['expenseCode', 'budget.budgetItem'])
            ->get()
            ->filter(fn($d) => $d->available_balance > 0)
            ->map(fn($d) => [
                'id' => $d->id,
                'expense_code_id' => $d->expense_code_id,
                'name' => ($d->expenseCode->code ?? '') . ' - ' . ($d->expenseCode->name ?? ''),
                'rubro' => ($d->budget->budgetItem->code ?? '') . ' - ' . ($d->budget->budgetItem->name ?? ''),
                'amount' => (float) $d->amount,
                'available_balance' => $d->available_balance,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Cargar códigos de gasto para el destino
     */
    public function loadDestinationExpenseCodes()
    {
        $excludeExpenseCodeId = null;
        if ($this->selected_funding_source_id === $this->destination_funding_source_id && $this->source_distribution_id) {
            $source = collect($this->sourceDistributions)->firstWhere('id', (int) $this->source_distribution_id);
            $excludeExpenseCodeId = $source['expense_code_id'] ?? null;
        }

        $this->destinationExpenseCodes = ExpenseCode::active()
            ->when($excludeExpenseCodeId, fn($q) => $q->where('id', '!=', $excludeExpenseCodeId))
            ->orderBy('code')
            ->get()
            ->map(fn($ec) => [
                'id' => $ec->id,
                'name' => $ec->code . ' - ' . $ec->name,
            ])
            ->toArray();
    }

    public function updatedSourceDistributionId($value)
    {
        $this->selectedSourceInfo = [];

        if ($value) {
            $found = collect($this->sourceDistributions)->firstWhere('id', (int) $value);
            if ($found) {
                $this->selectedSourceInfo = $found;
            }
            $this->loadDestinationExpenseCodes();
        }
    }

    public function updatedDestinationExpenseCodeId($value)
    {
        $this->selectedDestinationInfo = [];

        if ($value && $this->destination_funding_source_id) {
            $ec = ExpenseCode::find($value);
            $destFsId = (int) $this->destination_funding_source_id;

            $existingDist = ExpenseDistribution::forSchool($this->schoolId)
                ->where('expense_code_id', (int) $value)
                ->whereHas('budget', function ($q) use ($destFsId) {
                    $q->forYear((int) $this->filterYear)
                      ->byType('expense')
                      ->byFundingSource($destFsId);
                })
                ->first();

            if ($existingDist) {
                $this->selectedDestinationInfo = [
                    'distribution_id' => $existingDist->id,
                    'name' => ($ec->code ?? '') . ' - ' . ($ec->name ?? ''),
                    'current_amount' => (float) $existingDist->amount,
                    'is_new' => false,
                ];
            } else {
                $this->selectedDestinationInfo = [
                    'distribution_id' => null,
                    'name' => $ec ? ($ec->code . ' - ' . $ec->name) : 'N/A',
                    'current_amount' => 0,
                    'is_new' => true,
                ];
            }
        }
    }

    public function getTransfersProperty()
    {
        return BudgetTransfer::forSchool($this->schoolId)
            ->with([
                'sourceBudget.budgetItem',
                'destinationBudget.budgetItem',
                'sourceFundingSource',
                'destinationFundingSource',
                'sourceExpenseDistribution.expenseCode',
                'destinationExpenseDistribution.expenseCode',
                'creator'
            ])
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear))
            ->when($this->search, fn($q) => $q->search($this->search))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    public function getAvailableYearsProperty()
    {
        $years = BudgetTransfer::forSchool($this->schoolId)
            ->distinct()
            ->pluck('fiscal_year')
            ->toArray();

        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $years)) {
            $years[] = $currentYear;
        }

        rsort($years);
        return $years;
    }

    public function openCreateModal()
    {
        if (!auth()->user()->can('budget_transfers.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear traslados.', type: 'error');
            return;
        }

        $this->resetForm();
        $this->loadAvailableFundingSources();
        $this->document_date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function save()
    {
        if (!auth()->user()->can('budget_transfers.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear traslados.', type: 'error');
            return;
        }

        if (empty($this->destination_funding_source_id)) {
            $this->destination_funding_source_id = $this->selected_funding_source_id;
        }

        $this->validate();

        $amount = (float) $this->amount;
        $sourceFsId = (int) $this->selected_funding_source_id;
        $destFsId = (int) $this->destination_funding_source_id;

        // Cargar distribución origen con presupuesto
        $sourceDistribution = ExpenseDistribution::forSchool($this->schoolId)
            ->with('budget')
            ->findOrFail($this->source_distribution_id);

        // Validar que el código de gasto destino no sea igual al de origen si están en la misma fuente
        if ($sourceFsId === $destFsId && $sourceDistribution->expense_code_id === (int) $this->destination_expense_code_id) {
            $this->addError('destination_expense_code_id', 'El gasto destino debe ser diferente al origen para la misma fuente.');
            return;
        }

        // Validar saldo disponible en la distribución origen
        $availableBalance = (float) $sourceDistribution->available_balance;
        if ($amount > $availableBalance) {
            $this->addError('amount', 'El monto no puede ser mayor al saldo disponible ($' . number_format($availableBalance, 2, ',', '.') . ').');
            return;
        }

        DB::beginTransaction();
        try {
            // Encontrar o crear distribución destino
            $destDistribution = ExpenseDistribution::forSchool($this->schoolId)
                ->where('expense_code_id', (int) $this->destination_expense_code_id)
                ->whereHas('budget', function ($q) use ($destFsId) {
                    $q->forYear((int) $this->filterYear)
                      ->byType('expense')
                      ->byFundingSource($destFsId);
                })
                ->first();

            if (!$destDistribution) {
                // Si la fuente destino es la misma que la de origen, asociar al mismo presupuesto
                if ($destFsId === $sourceFsId) {
                    $targetBudgetId = $sourceDistribution->budget_id;
                } else {
                    $targetBudget = Budget::forSchool($this->schoolId)
                        ->forYear((int) $this->filterYear)
                        ->byType('expense')
                        ->byFundingSource($destFsId)
                        ->first();

                    if (!$targetBudget) {
                        throw new \Exception("No existe un presupuesto de gasto registrado para la fuente destino seleccionada.");
                    }
                    $targetBudgetId = $targetBudget->id;
                }

                $destDistribution = ExpenseDistribution::create([
                    'school_id' => $this->schoolId,
                    'budget_id' => $targetBudgetId,
                    'expense_code_id' => (int) $this->destination_expense_code_id,
                    'amount' => 0,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);
            }

            // Guardar montos anteriores a nivel de distribución
            $sourcePrev = (float) $sourceDistribution->amount;
            $destPrev = (float) $destDistribution->amount;

            // Actualizar montos de las distribuciones
            $sourceDistribution->update(['amount' => $sourcePrev - $amount]);
            $destDistribution->update(['amount' => $destPrev + $amount]);

            // Ajustar presupuestos si están en diferentes líneas presupuestales
            $sourceBudget = $sourceDistribution->budget;
            $destBudget = Budget::findOrFail($destDistribution->budget_id);

            if ($sourceBudget->id !== $destBudget->id) {
                $sourceBudget->update(['current_amount' => (float) $sourceBudget->current_amount - $amount]);
                $destBudget->update(['current_amount' => (float) $destBudget->current_amount + $amount]);
            }

            // Crear registro del traslado
            BudgetTransfer::create([
                'school_id' => $this->schoolId,
                'transfer_number' => BudgetTransfer::getNextTransferNumber($this->schoolId, (int) $this->filterYear),
                'source_budget_id' => $sourceBudget->id,
                'source_funding_source_id' => $sourceFsId,
                'source_expense_distribution_id' => $sourceDistribution->id,
                'destination_budget_id' => $destBudget->id,
                'destination_funding_source_id' => $destFsId,
                'destination_expense_distribution_id' => $destDistribution->id,
                'amount' => $amount,
                'source_previous_amount' => $sourcePrev,
                'source_new_amount' => $sourcePrev - $amount,
                'destination_previous_amount' => $destPrev,
                'destination_new_amount' => $destPrev + $amount,
                'reason' => $this->reason,
                'document_number' => $this->document_number ?: null,
                'document_date' => $this->document_date,
                'fiscal_year' => (int) $this->filterYear,
                'created_by' => auth()->id(),
            ]);

            // Recalcular presupuestos afectados
            $sourceBudget->recalculateCurrentAmount();
            $destBudget->recalculateCurrentAmount();

            DB::commit();

            $this->dispatch('toast', message: 'Traslado (crédito/contracrédito) registrado exitosamente.', type: 'success');
            $this->closeModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    // ======================================================
    // LÓGICA DE EDICIÓN DE TRASLADO
    // ======================================================

    public function openEditModal(int $id)
    {
        if (!auth()->user()->can('budget_transfers.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para editar traslados.', type: 'error');
            return;
        }

        $this->editingTransfer = BudgetTransfer::forSchool($this->schoolId)
            ->with([
                'sourceBudget.budgetItem',
                'destinationBudget.budgetItem',
                'sourceFundingSource',
                'destinationFundingSource',
                'sourceExpenseDistribution.expenseCode',
                'destinationExpenseDistribution.expenseCode',
            ])
            ->findOrFail($id);

        $this->editTransferId = $this->editingTransfer->id;
        $this->edit_source_funding_source_id = (string) $this->editingTransfer->source_funding_source_id;
        $this->edit_destination_funding_source_id = (string) ($this->editingTransfer->destination_funding_source_id ?: $this->editingTransfer->source_funding_source_id);
        $this->edit_source_distribution_id = (string) $this->editingTransfer->source_expense_distribution_id;
        $this->edit_destination_expense_code_id = (string) ($this->editingTransfer->destinationExpenseDistribution?->expense_code_id ?? '');
        $this->edit_amount = (string) $this->editingTransfer->amount;
        $this->edit_reason = $this->editingTransfer->reason;
        $this->edit_document_number = $this->editingTransfer->document_number ?? '';
        $this->edit_document_date = $this->editingTransfer->document_date ? $this->editingTransfer->document_date->format('Y-m-d') : now()->format('Y-m-d');

        $this->loadEditFundingSources();
        $this->loadEditSourceDistributions();
        $this->loadEditDestinationExpenseCodes();

        $this->updatedEditSourceDistributionId($this->edit_source_distribution_id);
        $this->updatedEditDestinationExpenseCodeId($this->edit_destination_expense_code_id);

        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function loadEditFundingSources()
    {
        $this->editAvailableFundingSources = FundingSource::whereHas('budgets', function ($q) {
            $q->forSchool($this->schoolId)
              ->forYear((int) $this->filterYear)
              ->byType('expense');
        })
            ->active()
            ->orderBy('code')
            ->get()
            ->map(fn($fs) => [
                'id' => $fs->id,
                'name' => $fs->code . ' - ' . $fs->name,
            ])
            ->toArray();

        $this->editDestinationFundingSources = $this->editAvailableFundingSources;
    }

    public function loadEditSourceDistributions()
    {
        if (!$this->edit_source_funding_source_id) {
            $this->editSourceDistributions = [];
            return;
        }

        $editingTransferSourceDistId = $this->editingTransfer?->source_expense_distribution_id;
        $originalAmount = (float) ($this->editingTransfer?->amount ?? 0);

        $this->editSourceDistributions = ExpenseDistribution::forSchool($this->schoolId)
            ->whereHas('budget', function ($q) {
                $q->forYear((int) $this->filterYear)
                  ->byType('expense')
                  ->byFundingSource((int) $this->edit_source_funding_source_id);
            })
            ->with(['expenseCode', 'budget.budgetItem'])
            ->get()
            ->map(function ($d) use ($editingTransferSourceDistId, $originalAmount) {
                $isOriginalSource = ($editingTransferSourceDistId && $d->id === $editingTransferSourceDistId);
                // Si es el origen original, el saldo disponible recupera el monto actual del traslado
                $effectiveAvailable = (float) $d->available_balance + ($isOriginalSource ? $originalAmount : 0);

                return [
                    'id' => $d->id,
                    'expense_code_id' => $d->expense_code_id,
                    'name' => ($d->expenseCode->code ?? '') . ' - ' . ($d->expenseCode->name ?? ''),
                    'rubro' => ($d->budget->budgetItem->code ?? '') . ' - ' . ($d->budget->budgetItem->name ?? ''),
                    'amount' => (float) $d->amount,
                    'available_balance' => $effectiveAvailable,
                    'is_original' => $isOriginalSource,
                ];
            })
            ->filter(fn($d) => $d['available_balance'] > 0)
            ->values()
            ->toArray();
    }

    public function loadEditDestinationExpenseCodes()
    {
        $excludeExpenseCodeId = null;
        if ($this->edit_source_funding_source_id === $this->edit_destination_funding_source_id && $this->edit_source_distribution_id) {
            $source = collect($this->editSourceDistributions)->firstWhere('id', (int) $this->edit_source_distribution_id);
            $excludeExpenseCodeId = $source['expense_code_id'] ?? null;
        }

        $this->editDestinationExpenseCodes = ExpenseCode::active()
            ->when($excludeExpenseCodeId, fn($q) => $q->where('id', '!=', $excludeExpenseCodeId))
            ->orderBy('code')
            ->get()
            ->map(fn($ec) => [
                'id' => $ec->id,
                'name' => $ec->code . ' - ' . $ec->name,
            ])
            ->toArray();
    }

    public function updatedEditSourceFundingSourceId($value)
    {
        $this->edit_source_distribution_id = '';
        $this->editSourceDistributions = [];
        $this->editSelectedSourceInfo = [];

        if ($value) {
            $this->loadEditSourceDistributions();
            $this->loadEditDestinationExpenseCodes();
        }
    }

    public function updatedEditSourceDistributionId($value)
    {
        $this->editSelectedSourceInfo = [];

        if ($value) {
            $found = collect($this->editSourceDistributions)->firstWhere('id', (int) $value);
            if ($found) {
                $this->editSelectedSourceInfo = $found;
            }
            $this->loadEditDestinationExpenseCodes();
        }
    }

    public function updatedEditDestinationFundingSourceId($value)
    {
        $this->edit_destination_expense_code_id = '';
        $this->editSelectedDestinationInfo = [];

        if ($value) {
            $this->loadEditDestinationExpenseCodes();
        }
    }

    public function updatedEditDestinationExpenseCodeId($value)
    {
        $this->editSelectedDestinationInfo = [];

        if ($value && $this->edit_destination_funding_source_id) {
            $ec = ExpenseCode::find($value);
            $destFsId = (int) $this->edit_destination_funding_source_id;

            $existingDist = ExpenseDistribution::forSchool($this->schoolId)
                ->where('expense_code_id', (int) $value)
                ->whereHas('budget', function ($q) use ($destFsId) {
                    $q->forYear((int) $this->filterYear)
                      ->byType('expense')
                      ->byFundingSource($destFsId);
                })
                ->first();

            $isOriginalDest = ($this->editingTransfer && $existingDist && $existingDist->id === $this->editingTransfer->destination_expense_distribution_id);
            $origTransferAmount = (float) ($this->editingTransfer?->amount ?? 0);

            if ($existingDist) {
                $baseAmount = (float) $existingDist->amount - ($isOriginalDest ? $origTransferAmount : 0);
                $this->editSelectedDestinationInfo = [
                    'distribution_id' => $existingDist->id,
                    'name' => ($ec->code ?? '') . ' - ' . ($ec->name ?? ''),
                    'current_amount' => (float) $existingDist->amount,
                    'base_amount' => $baseAmount,
                    'available_balance' => (float) $existingDist->available_balance,
                    'is_new' => false,
                    'is_original' => $isOriginalDest,
                ];
            } else {
                $this->editSelectedDestinationInfo = [
                    'distribution_id' => null,
                    'name' => $ec ? ($ec->code . ' - ' . $ec->name) : 'N/A',
                    'current_amount' => 0,
                    'base_amount' => 0,
                    'available_balance' => 0,
                    'is_new' => true,
                    'is_original' => false,
                ];
            }
        }
    }

    public function update()
    {
        if (!auth()->user()->can('budget_transfers.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para editar traslados.', type: 'error');
            return;
        }

        $this->validate([
            'edit_source_funding_source_id' => 'required|exists:funding_sources,id',
            'edit_destination_funding_source_id' => 'required|exists:funding_sources,id',
            'edit_source_distribution_id' => 'required|exists:expense_distributions,id',
            'edit_destination_expense_code_id' => 'required|exists:expense_codes,id',
            'edit_amount' => 'required|numeric|min:0.01',
            'edit_reason' => 'required|string|min:10',
            'edit_document_number' => 'nullable|string|max:50',
            'edit_document_date' => 'required|date',
        ], [
            'edit_source_funding_source_id.required' => 'Debe seleccionar la fuente de origen.',
            'edit_destination_funding_source_id.required' => 'Debe seleccionar la fuente de destino.',
            'edit_source_distribution_id.required' => 'Debe seleccionar el gasto origen (contracrédito).',
            'edit_destination_expense_code_id.required' => 'Debe seleccionar el gasto destino (crédito).',
            'edit_amount.required' => 'El monto es obligatorio.',
            'edit_amount.min' => 'El monto debe ser mayor a 0.',
            'edit_reason.required' => 'La justificación es obligatoria.',
            'edit_reason.min' => 'La justificación debe tener al menos 10 caracteres.',
            'edit_document_date.required' => 'La fecha de realización es obligatoria.',
        ]);

        $transfer = BudgetTransfer::forSchool($this->schoolId)->findOrFail($this->editTransferId);
        $oldAmount = (float) $transfer->amount;
        $newAmount = (float) $this->edit_amount;

        $oldSourceDist = ExpenseDistribution::find($transfer->source_expense_distribution_id);
        $oldDestDist = ExpenseDistribution::find($transfer->destination_expense_distribution_id);

        $newSourceDist = ExpenseDistribution::with('budget')->findOrFail($this->edit_source_distribution_id);
        $newSourceFsId = (int) $this->edit_source_funding_source_id;
        $newDestFsId = (int) $this->edit_destination_funding_source_id;
        $newDestEcId = (int) $this->edit_destination_expense_code_id;

        // Validar que origen y destino no sean idénticos
        if ($newSourceFsId === $newDestFsId && $newSourceDist->expense_code_id === $newDestEcId) {
            $this->addError('edit_destination_expense_code_id', 'El gasto destino no puede ser el mismo que el origen para la misma fuente.');
            return;
        }

        // VALIDACIÓN: ¿Puede el destino anterior ceder los fondos?
        // Buscar si el nuevo destino es el mismo objeto ExpenseDistribution anterior
        $isSameDestinationDistribution = ($oldDestDist && $newDestFsId === (int) $transfer->destination_funding_source_id && $oldDestDist->expense_code_id === $newDestEcId);

        if ($oldDestDist) {
            $destAvail = (float) $oldDestDist->available_balance;
            if (!$isSameDestinationDistribution) {
                // Destino diferente: el destino anterior debe tener suficiente para revertir todo oldAmount
                if ($destAvail < $oldAmount) {
                    $this->addError('edit_destination_expense_code_id', "El gasto destino anterior ya tiene compromisos o pagos registrados. Solo tiene disponible $" . number_format($destAvail, 2, ',', '.') . " y se requiere revertir $" . number_format($oldAmount, 2, ',', '.') . ".");
                    return;
                }
            } else {
                // Mismo destino: si el nuevo monto es menor, el destino debe poder ceder la diferencia
                if ($newAmount < $oldAmount) {
                    $difference = $oldAmount - $newAmount;
                    if ($destAvail < $difference) {
                        $this->addError('edit_amount', "El gasto destino ya tiene compromisos o pagos registrados. No es posible reducir el traslado en $" . number_format($difference, 2, ',', '.') . " (saldo disponible: $" . number_format($destAvail, 2, ',', '.') . ").");
                        return;
                    }
                }
            }
        }

        // VALIDACIÓN: ¿Tiene el nuevo origen saldo disponible para el nuevo monto?
        $sourceAvailable = (float) $newSourceDist->available_balance;
        if ($oldSourceDist && $oldSourceDist->id === $newSourceDist->id) {
            $sourceAvailable += $oldAmount;
        }

        if ($newAmount > $sourceAvailable) {
            $this->addError('edit_amount', 'El monto supera el saldo disponible del gasto origen ($' . number_format($sourceAvailable, 2, ',', '.') . ').');
            return;
        }

        $oldSourceBudgetId = $transfer->source_budget_id;
        $oldDestBudgetId = $transfer->destination_budget_id;

        DB::beginTransaction();
        try {
            // 1. Revertir traslado anterior en distribuciones
            if ($oldDestDist) {
                $oldDestDist->decrement('amount', $oldAmount);
            }
            if ($oldSourceDist) {
                $oldSourceDist->increment('amount', $oldAmount);
            }

            // 2. Encontrar o crear nueva distribución destino
            $newDestDist = ExpenseDistribution::forSchool($this->schoolId)
                ->where('expense_code_id', $newDestEcId)
                ->whereHas('budget', function ($q) use ($newDestFsId) {
                    $q->forYear((int) $this->filterYear)
                      ->byType('expense')
                      ->byFundingSource($newDestFsId);
                })
                ->first();

            if (!$newDestDist) {
                if ($newDestFsId === $newSourceFsId) {
                    $targetBudgetId = $newSourceDist->budget_id;
                } else {
                    $targetBudget = Budget::forSchool($this->schoolId)
                        ->forYear((int) $this->filterYear)
                        ->byType('expense')
                        ->byFundingSource($newDestFsId)
                        ->first();

                    if (!$targetBudget) {
                        throw new \Exception("No existe un presupuesto de gasto registrado para la fuente destino seleccionada.");
                    }
                    $targetBudgetId = $targetBudget->id;
                }

                $newDestDist = ExpenseDistribution::create([
                    'school_id' => $this->schoolId,
                    'budget_id' => $targetBudgetId,
                    'expense_code_id' => $newDestEcId,
                    'amount' => 0,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);
            }

            // Refrescar para obtener los valores exactos antes de aplicar el nuevo monto
            $newSourceDist->refresh();
            $newDestDist->refresh();

            $sourcePrevAmount = (float) $newSourceDist->amount;
            $destPrevAmount = (float) $newDestDist->amount;

            // 3. Aplicar nuevo traslado en distribuciones
            $newSourceDist->decrement('amount', $newAmount);
            $newDestDist->increment('amount', $newAmount);

            $newSourceBudget = $newSourceDist->budget;
            $newDestBudget = Budget::findOrFail($newDestDist->budget_id);

            // 4. Actualizar registro BudgetTransfer
            $transfer->update([
                'source_budget_id' => $newSourceBudget->id,
                'source_funding_source_id' => $newSourceFsId,
                'source_expense_distribution_id' => $newSourceDist->id,
                'destination_budget_id' => $newDestBudget->id,
                'destination_funding_source_id' => $newDestFsId,
                'destination_expense_distribution_id' => $newDestDist->id,
                'amount' => $newAmount,
                'source_previous_amount' => $sourcePrevAmount,
                'source_new_amount' => $sourcePrevAmount - $newAmount,
                'destination_previous_amount' => $destPrevAmount,
                'destination_new_amount' => $destPrevAmount + $newAmount,
                'reason' => $this->edit_reason,
                'document_number' => $this->edit_document_number ?: null,
                'document_date' => $this->edit_document_date,
            ]);

            // 5. Recalcular presupuestos afectados
            $budgetIdsToRecalculate = array_unique(array_filter([
                $oldSourceBudgetId,
                $oldDestBudgetId,
                $newSourceBudget->id,
                $newDestBudget->id,
            ]));

            foreach ($budgetIdsToRecalculate as $bId) {
                $b = Budget::find($bId);
                $b?->recalculateCurrentAmount();
            }

            DB::commit();

            $this->dispatch('toast', message: "Traslado #{$transfer->formatted_number} actualizado exitosamente.", type: 'success');
            $this->closeEditModal();

            if ($this->showDetailModal) {
                $this->showDetail($transfer->id);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error al actualizar el traslado: ' . $e->getMessage(), type: 'error');
        }
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingTransfer = null;
        $this->editTransferId = null;
        $this->edit_source_funding_source_id = '';
        $this->edit_destination_funding_source_id = '';
        $this->edit_source_distribution_id = '';
        $this->edit_destination_expense_code_id = '';
        $this->edit_amount = '';
        $this->edit_reason = '';
        $this->edit_document_number = '';
        $this->edit_document_date = '';
        $this->editAvailableFundingSources = [];
        $this->editDestinationFundingSources = [];
        $this->editSourceDistributions = [];
        $this->editDestinationExpenseCodes = [];
        $this->editSelectedSourceInfo = [];
        $this->editSelectedDestinationInfo = [];
        $this->resetValidation();
    }

    // ======================================================
    // LÓGICA DE ELIMINACIÓN DE TRASLADO
    // ======================================================

    public function confirmDelete(int $id)
    {
        if (!auth()->user()->can('budget_transfers.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar traslados.', type: 'error');
            return;
        }

        $this->transferToDelete = BudgetTransfer::forSchool($this->schoolId)
            ->with([
                'sourceBudget.budgetItem',
                'destinationBudget.budgetItem',
                'sourceFundingSource',
                'destinationFundingSource',
                'sourceExpenseDistribution.expenseCode',
                'destinationExpenseDistribution.expenseCode',
            ])
            ->findOrFail($id);

        $this->deleteWarning = null;

        // Validar si el destino tiene disponible el monto para revertir
        $destDist = $this->transferToDelete->destinationExpenseDistribution;
        if ($destDist) {
            $destAvail = (float) $destDist->available_balance;
            $transferAmount = (float) $this->transferToDelete->amount;
            if ($destAvail < $transferAmount) {
                $this->deleteWarning = "El gasto destino ya tiene compromisos o pagos registrados. Solo tiene disponible $" . number_format($destAvail, 2, ',', '.') . " y se requiere revertir $" . number_format($transferAmount, 2, ',', '.') . ". No es posible eliminar el traslado hasta liberar dichos compromisos.";
            }
        }

        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if (!auth()->user()->can('budget_transfers.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar traslados.', type: 'error');
            return;
        }

        if (!$this->transferToDelete) {
            return;
        }

        $transfer = BudgetTransfer::forSchool($this->schoolId)->findOrFail($this->transferToDelete->id);
        $amount = (float) $transfer->amount;
        $destDist = ExpenseDistribution::find($transfer->destination_expense_distribution_id);
        $sourceDist = ExpenseDistribution::find($transfer->source_expense_distribution_id);

        if ($destDist && (float) $destDist->available_balance < $amount) {
            $this->dispatch('toast', message: 'No se puede eliminar: el gasto destino no cuenta con saldo disponible suficiente para revertir el monto.', type: 'error');
            return;
        }

        $sourceBudgetId = $transfer->source_budget_id;
        $destBudgetId = $transfer->destination_budget_id;
        $formattedNum = $transfer->formatted_number;

        DB::beginTransaction();
        try {
            if ($destDist) {
                $destDist->decrement('amount', $amount);
            }
            if ($sourceDist) {
                $sourceDist->increment('amount', $amount);
            }

            $transfer->delete();

            // Recalcular presupuestos
            $sourceBudget = Budget::find($sourceBudgetId);
            $destBudget = Budget::find($destBudgetId);
            $sourceBudget?->recalculateCurrentAmount();
            $destBudget?->recalculateCurrentAmount();

            DB::commit();

            $this->dispatch('toast', message: "Traslado #{$formattedNum} eliminado y saldos revertidos exitosamente.", type: 'success');
            $this->closeDeleteModal();

            if ($this->showDetailModal) {
                $this->closeDetailModal();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error al eliminar el traslado: ' . $e->getMessage(), type: 'error');
        }
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->transferToDelete = null;
        $this->deleteWarning = null;
    }

    public function showDetail($id)
    {
        $this->detailTransfer = BudgetTransfer::forSchool($this->schoolId)
            ->with([
                'sourceBudget.budgetItem',
                'destinationBudget.budgetItem',
                'sourceFundingSource',
                'destinationFundingSource',
                'sourceExpenseDistribution.expenseCode',
                'destinationExpenseDistribution.expenseCode',
                'creator'
            ])
            ->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->detailTransfer = null;
    }

    public function resetForm()
    {
        $this->selected_funding_source_id = '';
        $this->destination_funding_source_id = '';
        $this->source_distribution_id = '';
        $this->destination_expense_code_id = '';
        $this->amount = '';
        $this->reason = '';
        $this->document_number = '';
        $this->document_date = '';
        $this->availableFundingSources = [];
        $this->destinationFundingSources = [];
        $this->sourceDistributions = [];
        $this->destinationExpenseCodes = [];
        $this->selectedSourceInfo = [];
        $this->selectedDestinationInfo = [];
        $this->resetValidation();
    }

    public function clearFilters()
    {
        $this->reset(['search']);
        $this->filterYear = \App\Models\School::find($this->schoolId)?->current_validity ?? date('Y');
        $this->resetPage();
    }

    // ======================================================
    // EDITAR FECHA DE TRASLADO EXISTENTE DESDE DETALLE
    // ======================================================

    public function startEditTransferDate(int $id, ?string $currentDate)
    {
        $this->editingTransferId = $id;
        $this->editingTransferDate = $currentDate ?: now()->format('Y-m-d');
    }

    public function cancelEditTransferDate()
    {
        $this->editingTransferId = null;
        $this->editingTransferDate = '';
    }

    public function saveTransferDate()
    {
        if (!auth()->user()->can('budget_transfers.edit') && !auth()->user()->can('budget_transfers.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }
        $this->validate(
            ['editingTransferDate' => 'required|date'],
            ['editingTransferDate.required' => 'La fecha es obligatoria.', 'editingTransferDate.date' => 'La fecha no es válida.']
        );

        BudgetTransfer::forSchool($this->schoolId)
            ->findOrFail($this->editingTransferId)
            ->update(['document_date' => $this->editingTransferDate]);

        $this->detailTransfer = BudgetTransfer::forSchool($this->schoolId)
            ->with([
                'sourceBudget.budgetItem',
                'destinationBudget.budgetItem',
                'sourceFundingSource',
                'destinationFundingSource',
                'sourceExpenseDistribution.expenseCode',
                'destinationExpenseDistribution.expenseCode',
                'creator',
            ])
            ->findOrFail($this->editingTransferId);

        $this->editingTransferId   = null;
        $this->editingTransferDate = '';
        $this->dispatch('toast', message: 'Fecha actualizada exitosamente.', type: 'success');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.budget-transfer-management');
    }
}

<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\BudgetModification;
use App\Models\FundingSource;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class BudgetManagement extends Component
{
    use WithPagination;

    public $schoolId;
    public $search = '';
    public $filterYear = '';
    public $filterStatus = '';
    public $filterFundingSource = '';
    public $perPage = 10;

    public $showModal = false;
    public $isEditing = false;
    public $budgetId = null;
    public $budget_item_id = '';
    public $initial_amount = '';
    public $fiscal_year = '';
    public $description = '';
    public $is_active = true;
    public $useMultipleSources = false;
    public $selectedFundingSourceId = '';
    public $fundingSourceAmounts = [];

    public $showModificationModal = false;
    public $modificationBudget = null;
    public $modification_type = 'addition';
    public $modification_amount = '';
    public $modification_reason = '';
    public $modification_document_number = '';

    public $showHistoryModal = false;
    public $historyBudget = null;

    public $showDeleteModal = false;
    public $itemToDelete = null;

    public $budgetItems = [];
    public $fundingSources = [];
    public $allFundingSources = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterYear' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterFundingSource' => ['except' => ''],
    ];

    protected function rules()
    {
        $rules = [
            'budget_item_id' => 'required|exists:budget_items,id',
            'initial_amount' => 'required|numeric|min:0.01',
            'fiscal_year' => 'required|integer|min:2020|max:2100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        if ($this->useMultipleSources && !$this->isEditing) {
            $rules['fundingSourceAmounts'] = 'required|array|min:1';
        } else {
            $rules['selectedFundingSourceId'] = 'required|exists:funding_sources,id';
        }

        return $rules;
    }

    protected $messages = [
        'budget_item_id.required' => 'Debe seleccionar un rubro.',
        'initial_amount.required' => 'El monto presupuestado es obligatorio.',
        'initial_amount.min' => 'El monto debe ser mayor a 0.',
        'fiscal_year.required' => 'El año fiscal es obligatorio.',
        'selectedFundingSourceId.required' => 'Debe seleccionar una fuente de financiación.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('budgets.view'), 403);
        
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
        $this->fiscal_year = date('Y');
        $this->filterYear = date('Y');
        $this->loadBudgetItems();
        $this->loadAllFundingSources();
    }

    public function loadBudgetItems()
    {
        $this->budgetItems = BudgetItem::active()
            ->orderBy('code')
            ->get()
            ->map(fn($item) => ['id' => $item->id, 'name' => "{$item->code} - {$item->name}"])
            ->toArray();
    }

    public function loadAllFundingSources()
    {
        $this->allFundingSources = FundingSource::active()
            ->orderBy('code')
            ->get()
            ->map(fn($s) => ['id' => $s->id, 'name' => "{$s->code} - {$s->name}"])
            ->toArray();
    }

    public function updatedBudgetItemId($value)
    {
        $this->selectedFundingSourceId = '';
        $this->fundingSourceAmounts = [];
        $this->loadFundingSourcesForItem($value);
    }

    public function loadFundingSourcesForItem($budgetItemId)
    {
        if (empty($budgetItemId)) {
            $this->fundingSources = [];
            return;
        }

        $this->fundingSources = FundingSource::forBudgetItem($budgetItemId)
            ->active()
            ->orderBy('code')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'full_name' => "{$s->code} - {$s->name}",
                'type' => $s->type,
                'type_name' => $s->type_name,
            ])
            ->toArray();

        $this->fundingSourceAmounts = [];
        foreach ($this->fundingSources as $source) {
            $this->fundingSourceAmounts[$source['id']] = '';
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

    public function updatingFilterFundingSource()
    {
        $this->resetPage();
    }

    public function getGroupedBudgetsProperty()
    {
        if (!$this->schoolId) {
            return new LengthAwarePaginator(collect([]), 0, $this->perPage, 1, [
                'path' => request()->url(),
                'pageName' => 'page',
            ]);
        }

        $query = Budget::where('school_id', $this->schoolId)
            ->with(['budgetItem', 'fundingSource', 'modifications']);
        
        if ($this->search) {
            $query->search($this->search);
        }
        
        if ($this->filterYear) {
            $query->where('fiscal_year', (int) $this->filterYear);
        }
        
        if ($this->filterFundingSource) {
            $query->where('funding_source_id', (int) $this->filterFundingSource);
        }
        
        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus === '1');
        }
        
        $budgets = $query->orderBy('budget_item_id')
            ->orderBy('funding_source_id')
            ->orderBy('type')
            ->get();

        $grouped = [];
        foreach ($budgets as $budget) {
            $key = $budget->budget_item_id . '-' . $budget->funding_source_id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'key' => $key,
                    'budget_item' => $budget->budgetItem,
                    'funding_source' => $budget->fundingSource,
                    'fiscal_year' => $budget->fiscal_year,
                    'income' => null,
                    'expense' => null,
                ];
            }
            $grouped[$key][$budget->type] = $budget;
        }

        $collection = collect($grouped)->values();
        
        // Use Livewire's page tracking
        $page = $this->getPage();
        if ($page < 1) $page = 1;
        
        $total = $collection->count();
        $offset = ($page - 1) * $this->perPage;
        $items = $collection->slice($offset, $this->perPage)->values();
        
        return new LengthAwarePaginator($items, $total, $this->perPage, $page, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);
    }

    public function getAvailableYearsProperty()
    {
        $years = Budget::forSchool($this->schoolId)->distinct()->pluck('fiscal_year')->toArray();
        $currentYear = (int) date('Y');
        if (!in_array($currentYear, $years)) {
            $years[] = $currentYear;
        }
        rsort($years);
        return $years;
    }

    public function getTotalsProperty()
    {
        $year = $this->filterYear ?: date('Y');
        $budgets = Budget::forSchool($this->schoolId)->forYear($year)->get();

        $totals = ['total_income' => 0, 'total_expense' => 0, 'balance' => 0];
        foreach ($budgets as $budget) {
            if ($budget->type === 'income') {
                $totals['total_income'] += $budget->current_amount;
            } else {
                $totals['total_expense'] += $budget->current_amount;
            }
        }
        $totals['balance'] = $totals['total_income'] - $totals['total_expense'];
        return $totals;
    }

    public function openCreateModal()
    {
        if (!auth()->user()->can('budgets.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear presupuestos.', type: 'error');
            return;
        }
        $this->resetForm();
        $this->showModal = true;
    }

    public function editBudget($id)
    {
        if (!auth()->user()->can('budgets.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para editar presupuestos.', type: 'error');
            return;
        }

        $budget = Budget::forSchool($this->schoolId)->findOrFail($id);
        $this->budgetId = $budget->id;
        $this->budget_item_id = $budget->budget_item_id;
        $this->loadFundingSourcesForItem($budget->budget_item_id);
        $this->selectedFundingSourceId = $budget->funding_source_id;
        $this->initial_amount = $budget->initial_amount;
        $this->fiscal_year = $budget->fiscal_year;
        $this->description = $budget->description;
        $this->is_active = $budget->is_active;
        $this->useMultipleSources = false;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $permission = $this->isEditing ? 'budgets.edit' : 'budgets.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }

        $this->validate();

        if ($this->useMultipleSources && !$this->isEditing) {
            $totalAssigned = collect($this->fundingSourceAmounts)
                ->filter(fn($a) => is_numeric($a) && $a > 0)
                ->sum();
            $totalBudget = (float) $this->initial_amount;
            
            if (abs($totalAssigned - $totalBudget) > 0.01) {
                $formattedAssigned = number_format($totalAssigned, 2);
                $formattedBudget = number_format($totalBudget, 2);
                $this->dispatch('toast', message: "La suma asignada (\${$formattedAssigned}) debe ser igual al monto total (\${$formattedBudget}).", type: 'error');
                return;
            }

            if ($totalAssigned <= 0) {
                $this->dispatch('toast', message: 'Debe asignar monto a al menos una fuente.', type: 'error');
                return;
            }
        }

        if ($this->isEditing) {
            $this->updateBudget();
        } else {
            $created = $this->createBudgets();
            if (!$created) {
                return; // Don't close modal if creation failed
            }
        }

        $this->closeModal();
    }

    protected function createBudgets(): bool
    {
        DB::beginTransaction();
        try {
            $sourcesToCreate = [];

            if ($this->useMultipleSources) {
                foreach ($this->fundingSourceAmounts as $sourceId => $amount) {
                    if (is_numeric($amount) && $amount > 0) {
                        $sourcesToCreate[] = ['funding_source_id' => $sourceId, 'amount' => $amount];
                    }
                }
            } else {
                $sourcesToCreate[] = [
                    'funding_source_id' => $this->selectedFundingSourceId,
                    'amount' => $this->initial_amount,
                ];
            }

            foreach ($sourcesToCreate as $sourceData) {
                $exists = Budget::where('school_id', $this->schoolId)
                    ->where('budget_item_id', $this->budget_item_id)
                    ->where('funding_source_id', $sourceData['funding_source_id'])
                    ->where('fiscal_year', $this->fiscal_year)
                    ->exists();

                if ($exists) {
                    $source = FundingSource::find($sourceData['funding_source_id']);
                    $budgetItem = BudgetItem::find($this->budget_item_id);
                    DB::rollBack();
                    $this->dispatch('toast', 
                        message: "Ya existe un presupuesto para el rubro '{$budgetItem->name}' con la fuente '{$source->name}' en el año {$this->fiscal_year}.", 
                        type: 'error'
                    );
                    return false;
                }

                Budget::create([
                    'school_id' => $this->schoolId,
                    'budget_item_id' => $this->budget_item_id,
                    'funding_source_id' => $sourceData['funding_source_id'],
                    'type' => 'income',
                    'initial_amount' => $sourceData['amount'],
                    'current_amount' => $sourceData['amount'],
                    'fiscal_year' => $this->fiscal_year,
                    'description' => $this->description,
                    'is_active' => $this->is_active,
                ]);

                Budget::create([
                    'school_id' => $this->schoolId,
                    'budget_item_id' => $this->budget_item_id,
                    'funding_source_id' => $sourceData['funding_source_id'],
                    'type' => 'expense',
                    'initial_amount' => $sourceData['amount'],
                    'current_amount' => $sourceData['amount'],
                    'fiscal_year' => $this->fiscal_year,
                    'description' => $this->description,
                    'is_active' => $this->is_active,
                ]);
            }

            DB::commit();
            $this->dispatch('toast', message: 'Presupuesto creado exitosamente.', type: 'success');
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
            return false;
        }
    }

    protected function updateBudget()
    {
        $budget = Budget::forSchool($this->schoolId)->findOrFail($this->budgetId);
        
        $data = [
            'budget_item_id' => $this->budget_item_id,
            'funding_source_id' => $this->selectedFundingSourceId,
            'initial_amount' => $this->initial_amount,
            'fiscal_year' => $this->fiscal_year,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];

        if ($budget->initial_amount != $this->initial_amount) {
            $data['current_amount'] = $this->initial_amount + $budget->total_additions - $budget->total_reductions
                + $budget->total_creditos - $budget->total_contracreditos;
        }
        
        $budget->update($data);
        $this->dispatch('toast', message: 'Presupuesto actualizado.', type: 'success');
    }

    public function openModificationModal($id)
    {
        if (!auth()->user()->can('budgets.modify')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar presupuestos.', type: 'error');
            return;
        }

        $this->modificationBudget = Budget::forSchool($this->schoolId)
            ->with(['budgetItem', 'fundingSource'])
            ->findOrFail($id);
        $this->resetModificationForm();
        $this->showModificationModal = true;
    }

    public function saveModification()
    {
        if (!auth()->user()->can('budgets.modify')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar presupuestos.', type: 'error');
            return;
        }

        $this->validate([
            'modification_amount' => 'required|numeric|min:0.01',
            'modification_reason' => 'required|string|min:10',
        ], [
            'modification_amount.required' => 'El monto es obligatorio.',
            'modification_amount.min' => 'El monto debe ser mayor a 0.',
            'modification_reason.required' => 'La razón es obligatoria.',
            'modification_reason.min' => 'La razón debe tener al menos 10 caracteres.',
        ]);

        $budget = $this->modificationBudget;
        $amount = (float) $this->modification_amount;

        if ($this->modification_type === 'reduction' && $amount > $budget->current_amount) {
            $this->addError('modification_amount', 'La reducción no puede ser mayor al saldo actual.');
            return;
        }

        $previousAmount = $budget->current_amount;
        $newAmount = $this->modification_type === 'addition' 
            ? $previousAmount + $amount 
            : $previousAmount - $amount;

        DB::beginTransaction();
        try {
            BudgetModification::create([
                'budget_id' => $budget->id,
                'modification_number' => $budget->getNextModificationNumber(),
                'type' => $this->modification_type,
                'amount' => $amount,
                'previous_amount' => $previousAmount,
                'new_amount' => $newAmount,
                'reason' => $this->modification_reason,
                'document_number' => $this->modification_document_number,
                'document_date' => now(),
                'created_by' => auth()->id(),
            ]);

            $budget->update(['current_amount' => $newAmount]);

            DB::commit();
            $this->dispatch('toast', message: 'Modificación registrada exitosamente.', type: 'success');
            $this->closeModificationModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function openHistoryModal($id)
    {
        $this->historyBudget = Budget::forSchool($this->schoolId)
            ->with(['budgetItem', 'fundingSource', 'modifications.creator'])
            ->findOrFail($id);
        $this->showHistoryModal = true;
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->can('budgets.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar presupuestos.', type: 'error');
            return;
        }

        $this->itemToDelete = Budget::forSchool($this->schoolId)
            ->with('budgetItem')
            ->findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function deleteBudget()
    {
        if (!auth()->user()->can('budgets.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar presupuestos.', type: 'error');
            return;
        }

        if (!$this->itemToDelete) {
            return;
        }

        DB::beginTransaction();
        try {
            $this->itemToDelete->modifications()->delete();
            $this->itemToDelete->delete();
            
            DB::commit();
            $this->dispatch('toast', message: 'Presupuesto eliminado.', type: 'success');
            $this->closeDeleteModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->can('budgets.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para cambiar el estado.', type: 'error');
            return;
        }

        $budget = Budget::forSchool($this->schoolId)->findOrFail($id);
        $budget->update(['is_active' => !$budget->is_active]);
        
        $status = $budget->is_active ? 'activado' : 'desactivado';
        $this->dispatch('toast', message: "Presupuesto {$status}.", type: 'success');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeModificationModal()
    {
        $this->showModificationModal = false;
        $this->modificationBudget = null;
        $this->resetModificationForm();
    }

    public function closeHistoryModal()
    {
        $this->showHistoryModal = false;
        $this->historyBudget = null;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->itemToDelete = null;
    }

    public function resetForm()
    {
        $this->budgetId = null;
        $this->budget_item_id = '';
        $this->initial_amount = '';
        $this->fiscal_year = date('Y');
        $this->description = '';
        $this->is_active = true;
        $this->useMultipleSources = false;
        $this->selectedFundingSourceId = '';
        $this->fundingSourceAmounts = [];
        $this->fundingSources = [];
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function resetModificationForm()
    {
        $this->modification_type = 'addition';
        $this->modification_amount = '';
        $this->modification_reason = '';
        $this->modification_document_number = '';
        $this->resetValidation();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterYear = date('Y');
        $this->filterStatus = '';
        $this->filterFundingSource = '';
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.budget-management');
    }
}

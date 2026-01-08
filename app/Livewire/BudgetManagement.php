<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\BudgetModification;
use App\Models\FundingSource;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

class BudgetManagement extends Component
{
    use WithPagination;

    public $schoolId;

    // Búsqueda y filtros
    public $search = '';
    public $filterType = '';
    public $filterYear = '';
    public $filterStatus = '';
    public $filterFundingSource = '';
    public $perPage = 15;

    // Modal crear/editar presupuesto
    public $showModal = false;
    public $isEditing = false;
    public $budgetId = null;

    // Campos del formulario
    public $type = 'expense';
    public $budget_item_id = '';
    public $funding_source_id = '';
    public $initial_amount = '';
    public $fiscal_year = '';
    public $description = '';
    public $is_active = true;

    // Modal de modificación
    public $showModificationModal = false;
    public $modificationBudget = null;
    public $modification_type = 'addition';
    public $modification_amount = '';
    public $modification_reason = '';
    public $modification_document_number = '';

    // Modal de historial
    public $showHistoryModal = false;
    public $historyBudget = null;

    // Modal de eliminación
    public $showDeleteModal = false;
    public $itemToDelete = null;

    // Rubros y fuentes disponibles
    public $budgetItems = [];
    public $fundingSources = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterType' => ['except' => ''],
        'filterYear' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterFundingSource' => ['except' => ''],
    ];

    protected function rules()
    {
        // Validación única compuesta: rubro + fuente + año + tipo + colegio
        $uniqueRule = function ($attribute, $value, $fail) {
            $query = Budget::where('school_id', $this->schoolId)
                ->where('budget_item_id', $this->budget_item_id)
                ->where('funding_source_id', $this->funding_source_id)
                ->where('fiscal_year', $this->fiscal_year)
                ->where('type', $this->type);
            
            if ($this->budgetId) {
                $query->where('id', '!=', $this->budgetId);
            }
            
            if ($query->exists()) {
                $fail('Ya existe un presupuesto con este rubro, fuente y tipo para el año fiscal seleccionado.');
            }
        };

        return [
            'type' => 'required|in:income,expense',
            'budget_item_id' => ['required', 'exists:budget_items,id'],
            'funding_source_id' => ['required', 'exists:funding_sources,id', $uniqueRule],
            'initial_amount' => 'required|numeric|min:0',
            'fiscal_year' => 'required|integer|min:2020|max:2100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'type.required' => 'Debe seleccionar el tipo (Ingreso o Gasto).',
        'budget_item_id.required' => 'Debe seleccionar un rubro.',
        'funding_source_id.required' => 'Debe seleccionar una fuente de financiación.',
        'initial_amount.required' => 'El monto presupuestado es obligatorio.',
        'initial_amount.min' => 'El monto debe ser mayor o igual a 0.',
        'fiscal_year.required' => 'El año fiscal es obligatorio.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('budgets.view'), 403, 'No tienes permisos para ver presupuestos.');
        
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

        $this->fiscal_year = date('Y');
        $this->filterYear = date('Y');
        $this->loadBudgetItems();
        // Las fuentes se cargan cuando se selecciona un rubro
        $this->fundingSources = [];
    }

    public function loadBudgetItems()
    {
        $this->budgetItems = BudgetItem::forSchool($this->schoolId)
            ->active()
            ->with('accountingAccount')
            ->orderBy('code')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => "{$item->code} - {$item->name}",
            ])
            ->toArray();
    }

    /**
     * Cuando cambia el rubro seleccionado, cargar las fuentes de ese rubro
     */
    public function updatedBudgetItemId($value)
    {
        $this->funding_source_id = ''; // Reset fuente
        $this->loadFundingSourcesForItem($value);
    }

    /**
     * Cargar fuentes de financiación para un rubro específico
     */
    public function loadFundingSourcesForItem($budgetItemId)
    {
        if (empty($budgetItemId)) {
            $this->fundingSources = [];
            return;
        }

        $this->fundingSources = FundingSource::forSchool($this->schoolId)
            ->forBudgetItem($budgetItemId)
            ->active()
            ->orderBy('code')
            ->get()
            ->map(fn($source) => [
                'id' => $source->id,
                'name' => "{$source->code} - {$source->name}",
            ])
            ->toArray();
    }

    /**
     * @deprecated Las fuentes ahora se cargan por rubro
     */
    public function loadFundingSources()
    {
        $this->fundingSources = FundingSource::forSchool($this->schoolId)
            ->active()
            ->orderBy('code')
            ->get()
            ->map(fn($source) => [
                'id' => $source->id,
                'name' => "{$source->code} - {$source->name}",
            ])
            ->toArray();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function getBudgetsProperty()
    {
        return Budget::forSchool($this->schoolId)
            ->with(['budgetItem.accountingAccount', 'fundingSource', 'modifications'])
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->filterType, fn($q) => $q->byType($this->filterType))
            ->when($this->filterYear, fn($q) => $q->forYear($this->filterYear))
            ->when($this->filterFundingSource, fn($q) => $q->byFundingSource($this->filterFundingSource))
            ->when($this->filterStatus !== '', function ($q) {
                $q->where('is_active', $this->filterStatus === '1');
            })
            ->orderBy('type')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    /**
     * Obtener los años fiscales disponibles
     */
    public function getAvailableYearsProperty()
    {
        $years = Budget::forSchool($this->schoolId)
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

    /**
     * Calcular totales por tipo y fuente para el año seleccionado
     */
    public function getTotalsProperty()
    {
        $year = $this->filterYear ?: date('Y');
        
        $budgets = Budget::forSchool($this->schoolId)
            ->forYear($year)
            ->with('fundingSource')
            ->get();

        $totals = [
            'income' => [],
            'expense' => [],
            'total_income' => 0,
            'total_expense' => 0,
            'balance' => 0,
        ];

        foreach ($budgets as $budget) {
            $sourceCode = $budget->fundingSource->code ?? 'N/A';
            $sourceName = $budget->fundingSource->name ?? 'Sin fuente';
            
            if (!isset($totals[$budget->type][$sourceCode])) {
                $totals[$budget->type][$sourceCode] = [
                    'name' => $sourceName,
                    'initial' => 0,
                    'current' => 0,
                ];
            }
            
            $totals[$budget->type][$sourceCode]['initial'] += $budget->initial_amount;
            $totals[$budget->type][$sourceCode]['current'] += $budget->current_amount;
            
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
        $this->type = $budget->type;
        $this->budget_item_id = $budget->budget_item_id;
        
        // Cargar las fuentes del rubro antes de asignar la fuente seleccionada
        $this->loadFundingSourcesForItem($budget->budget_item_id);
        
        $this->funding_source_id = $budget->funding_source_id;
        $this->initial_amount = $budget->initial_amount;
        $this->fiscal_year = $budget->fiscal_year;
        $this->description = $budget->description;
        $this->is_active = $budget->is_active;

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

        $data = [
            'school_id' => $this->schoolId,
            'budget_item_id' => $this->budget_item_id,
            'funding_source_id' => $this->funding_source_id,
            'type' => $this->type,
            'initial_amount' => $this->initial_amount,
            'current_amount' => $this->initial_amount,
            'fiscal_year' => $this->fiscal_year,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $budget = Budget::forSchool($this->schoolId)->findOrFail($this->budgetId);
            
            // Si cambia el monto inicial, recalcular el actual
            if ($budget->initial_amount != $this->initial_amount) {
                $data['current_amount'] = $this->initial_amount + $budget->total_additions - $budget->total_reductions;
            } else {
                unset($data['current_amount']);
            }
            
            $budget->update($data);
            $this->dispatch('toast', message: 'Presupuesto actualizado exitosamente.', type: 'success');
        } else {
            Budget::create($data);
            $this->dispatch('toast', message: 'Presupuesto creado exitosamente.', type: 'success');
        }

        $this->closeModal();
    }

    // Modificaciones
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
            'modification_type' => 'required|in:addition,reduction',
            'modification_amount' => 'required|numeric|min:0.01',
            'modification_reason' => 'required|string|min:10',
            'modification_document_number' => 'nullable|string|max:50',
        ], [
            'modification_amount.required' => 'El monto es obligatorio.',
            'modification_amount.min' => 'El monto debe ser mayor a 0.',
            'modification_reason.required' => 'La razón es obligatoria.',
            'modification_reason.min' => 'La razón debe tener al menos 10 caracteres.',
        ]);

        // Validar que no quede negativo
        if ($this->modification_type === 'reduction') {
            if ($this->modification_amount > $this->modificationBudget->current_amount) {
                $this->addError('modification_amount', 'El monto de reducción no puede ser mayor al saldo actual.');
                return;
            }
        }

        $previousAmount = $this->modificationBudget->current_amount;
        $newAmount = $this->modification_type === 'addition'
            ? $previousAmount + $this->modification_amount
            : $previousAmount - $this->modification_amount;

        BudgetModification::create([
            'budget_id' => $this->modificationBudget->id,
            'modification_number' => $this->modificationBudget->getNextModificationNumber(),
            'type' => $this->modification_type,
            'amount' => $this->modification_amount,
            'previous_amount' => $previousAmount,
            'new_amount' => $newAmount,
            'reason' => $this->modification_reason,
            'document_number' => $this->modification_document_number ?: null,
            'document_date' => now(),
            'created_by' => auth()->id(),
        ]);

        $this->modificationBudget->update(['current_amount' => $newAmount]);

        $typeName = $this->modification_type === 'addition' ? 'Adición' : 'Reducción';
        $this->dispatch('toast', message: "{$typeName} registrada exitosamente.", type: 'success');
        $this->closeModificationModal();
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
            ->with(['budgetItem', 'fundingSource'])
            ->findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function deleteBudget()
    {
        if (!auth()->user()->can('budgets.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar presupuestos.', type: 'error');
            return;
        }

        if ($this->itemToDelete) {
            $name = $this->itemToDelete->budgetItem->name;
            $this->itemToDelete->delete();
            $this->dispatch('toast', message: "Presupuesto '{$name}' eliminado exitosamente.", type: 'success');
        }

        $this->closeDeleteModal();
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->can('budgets.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar presupuestos.', type: 'error');
            return;
        }

        $budget = Budget::forSchool($this->schoolId)->findOrFail($id);
        $budget->update(['is_active' => !$budget->is_active]);

        $status = $budget->is_active ? 'activado' : 'desactivado';
        $this->dispatch('toast', message: "Presupuesto {$status} exitosamente.", type: 'success');
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
        $this->type = 'expense';
        $this->budget_item_id = '';
        $this->funding_source_id = '';
        $this->initial_amount = '';
        $this->fiscal_year = date('Y');
        $this->description = '';
        $this->is_active = true;
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
        $this->reset(['search', 'filterType', 'filterStatus', 'filterFundingSource']);
        $this->filterYear = date('Y');
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.budget-management');
    }
}

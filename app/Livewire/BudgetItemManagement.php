<?php

namespace App\Livewire;

use App\Models\AccountingAccount;
use App\Models\BudgetItem;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

class BudgetItemManagement extends Component
{
    use WithPagination;

    public $schoolId;

    // Búsqueda y filtros
    public $search = '';
    public $filterStatus = '';
    public $filterAccount = '';
    public $perPage = 15;

    // Modal y formulario
    public $showModal = false;
    public $isEditing = false;
    public $budgetItemId = null;

    // Campos del formulario
    public $code = '';
    public $name = '';
    public $description = '';
    public $accounting_account_id = '';
    public $is_active = true;

    // Modal de eliminación
    public $showDeleteModal = false;
    public $itemToDelete = null;

    // Cuentas auxiliares disponibles
    public $auxiliaryAccounts = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterAccount' => ['except' => ''],
    ];

    protected function rules()
    {
        $uniqueRule = 'unique:budget_items,code';
        if ($this->budgetItemId) {
            $uniqueRule .= ',' . $this->budgetItemId . ',id,school_id,' . $this->schoolId;
        } else {
            $uniqueRule .= ',NULL,id,school_id,' . $this->schoolId;
        }

        return [
            'code' => ['required', 'string', 'max:20', $uniqueRule],
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'accounting_account_id' => 'required|exists:accounting_accounts,id',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'code.required' => 'El código es obligatorio.',
        'code.unique' => 'Ya existe un rubro con este código.',
        'name.required' => 'El nombre es obligatorio.',
        'accounting_account_id.required' => 'Debe seleccionar una cuenta contable.',
        'accounting_account_id.exists' => 'La cuenta contable seleccionada no es válida.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('budget_items.view'), 403, 'No tienes permisos para ver rubros.');
        
        $this->schoolId = session('selected_school_id');
        
        // Si no hay colegio seleccionado, intentar seleccionar el primero disponible
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

        $this->loadAuxiliaryAccounts();
    }

    public function loadAuxiliaryAccounts()
    {
        // Solo cuentas auxiliares (nivel 5) que permiten movimiento
        $this->auxiliaryAccounts = AccountingAccount::where('level', 5)
            ->where('allows_movement', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'name' => "{$account->code} - {$account->name}",
            ])
            ->toArray();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function getBudgetItemsProperty()
    {
        return BudgetItem::forSchool($this->schoolId)
            ->with('accountingAccount')
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->filterStatus !== '', function ($q) {
                $q->where('is_active', $this->filterStatus === '1');
            })
            ->when($this->filterAccount, fn($q) => $q->where('accounting_account_id', $this->filterAccount))
            ->orderBy('code')
            ->paginate($this->perPage);
    }

    public function openCreateModal()
    {
        if (!auth()->user()->can('budget_items.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear rubros.', type: 'error');
            return;
        }

        $this->resetForm();
        $this->showModal = true;
    }

    public function editBudgetItem($id)
    {
        if (!auth()->user()->can('budget_items.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para editar rubros.', type: 'error');
            return;
        }

        $item = BudgetItem::forSchool($this->schoolId)->findOrFail($id);

        $this->budgetItemId = $item->id;
        $this->code = $item->code;
        $this->name = $item->name;
        $this->description = $item->description;
        $this->accounting_account_id = $item->accounting_account_id;
        $this->is_active = $item->is_active;

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $permission = $this->isEditing ? 'budget_items.edit' : 'budget_items.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }

        $this->validate();

        // Verificar que la cuenta sea auxiliar
        $account = AccountingAccount::find($this->accounting_account_id);
        if (!$account || $account->level !== 5) {
            $this->addError('accounting_account_id', 'Solo puede vincular rubros a cuentas auxiliares (nivel 5).');
            return;
        }

        $data = [
            'school_id' => $this->schoolId,
            'code' => strtoupper($this->code),
            'name' => $this->name,
            'description' => $this->description,
            'accounting_account_id' => $this->accounting_account_id,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $item = BudgetItem::forSchool($this->schoolId)->findOrFail($this->budgetItemId);
            $item->update($data);
            $this->dispatch('toast', message: 'Rubro actualizado exitosamente.', type: 'success');
        } else {
            BudgetItem::create($data);
            $this->dispatch('toast', message: 'Rubro creado exitosamente.', type: 'success');
        }

        $this->closeModal();
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->can('budget_items.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar rubros.', type: 'error');
            return;
        }

        $this->itemToDelete = BudgetItem::forSchool($this->schoolId)->findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function deleteBudgetItem()
    {
        if (!auth()->user()->can('budget_items.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar rubros.', type: 'error');
            return;
        }

        if ($this->itemToDelete) {
            $name = $this->itemToDelete->name;
            $this->itemToDelete->delete();
            $this->dispatch('toast', message: "Rubro '{$name}' eliminado exitosamente.", type: 'success');
        }

        $this->closeDeleteModal();
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->can('budget_items.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar rubros.', type: 'error');
            return;
        }

        $item = BudgetItem::forSchool($this->schoolId)->findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);

        $status = $item->is_active ? 'activado' : 'desactivado';
        $this->dispatch('toast', message: "Rubro {$status} exitosamente.", type: 'success');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->itemToDelete = null;
    }

    public function resetForm()
    {
        $this->budgetItemId = null;
        $this->code = '';
        $this->name = '';
        $this->description = '';
        $this->accounting_account_id = '';
        $this->is_active = true;
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterStatus', 'filterAccount']);
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.budget-item-management');
    }
}

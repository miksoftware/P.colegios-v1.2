<?php

namespace App\Livewire;

use App\Models\AccountingAccount;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

class AccountingAccountManagement extends Component
{
    use WithPagination;

    // Form fields
    public $accountId = null;
    public $code = '';
    public $name = '';
    public $description = '';
    public $level = 1;
    public $parentId = null;
    public $nature = 'D';
    public $allowsMovement = false;
    public $isActive = true;
    
    // UI state
    public $showModal = false;
    public $isEditing = false;
    public $expandedAccounts = [];
    public $search = '';
    public $filterLevel = '';
    public $filterNature = '';
    public $filterStatus = '';

    // Parent selection
    public $parentName = '';

    // Delete confirmation
    public $showDeleteModal = false;
    public $accountToDelete = null;
    public $deleteStep = 1; // 1 = primera confirmación, 2 = segunda confirmación
    public $childrenCount = 0;

    protected function rules()
    {
        return [
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'required|integer|min:1|max:5',
            'parentId' => 'nullable|exists:accounting_accounts,id',
            'nature' => 'required|in:D,C',
            'allowsMovement' => 'boolean',
            'isActive' => 'boolean',
        ];
    }

    protected $messages = [
        'code.required' => 'El código es obligatorio.',
        'code.unique' => 'Este código ya existe.',
        'name.required' => 'El nombre es obligatorio.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('accounting_accounts.view'), 403, 'No tienes permisos para ver cuentas contables.');
    }

    public function getAccountsProperty()
    {
        return AccountingAccount::roots()
            ->with('childrenRecursive')
            ->get();
    }

    public function toggleExpand($accountId)
    {
        if (in_array($accountId, $this->expandedAccounts)) {
            $this->expandedAccounts = array_diff($this->expandedAccounts, [$accountId]);
        } else {
            $this->expandedAccounts[] = $accountId;
        }
    }

    public function expandAll()
    {
        $this->expandedAccounts = AccountingAccount::whereHas('children')
            ->pluck('id')
            ->toArray();
    }

    public function collapseAll()
    {
        $this->expandedAccounts = [];
    }

    public function openCreateModal($parentId = null)
    {
        if (!auth()->user()->can('accounting_accounts.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear cuentas.', type: 'error');
            return;
        }

        $this->resetForm();
        
        if ($parentId) {
            $parent = AccountingAccount::find($parentId);
            if ($parent) {
                $this->parentId = $parent->id;
                $this->parentName = "{$parent->code} - {$parent->name}";
                $this->level = $parent->level + 1;
                $this->code = $parent->getNextChildCode();
                $this->nature = $parent->nature;
            }
        } else {
            $this->level = 1;
            $this->code = AccountingAccount::getNextClassCode();
        }
        
        $this->showModal = true;
    }

    public function editAccount($id)
    {
        if (!auth()->user()->can('accounting_accounts.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para editar cuentas.', type: 'error');
            return;
        }

        $account = AccountingAccount::findOrFail($id);
        
        $this->accountId = $account->id;
        $this->code = $account->code;
        $this->name = $account->name;
        $this->description = $account->description;
        $this->level = $account->level;
        $this->parentId = $account->parent_id;
        $this->nature = $account->nature;
        $this->allowsMovement = $account->allows_movement;
        $this->isActive = $account->is_active;
        
        if ($account->parent) {
            $this->parentName = "{$account->parent->code} - {$account->parent->name}";
        }
        
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $permission = $this->isEditing ? 'accounting_accounts.edit' : 'accounting_accounts.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }

        $this->validate();

        // Validar código único
        $existingAccount = AccountingAccount::where('code', $this->code)
            ->when($this->accountId, fn($q) => $q->where('id', '!=', $this->accountId))
            ->exists();

        if ($existingAccount) {
            $this->addError('code', 'Este código ya existe.');
            return;
        }

        $data = [
            'code' => $this->code,
            'name' => strtoupper($this->name),
            'description' => $this->description,
            'level' => $this->level,
            'parent_id' => $this->parentId,
            'nature' => $this->nature,
            'allows_movement' => $this->allowsMovement,
            'is_active' => $this->isActive,
        ];

        if ($this->isEditing) {
            $account = AccountingAccount::findOrFail($this->accountId);
            $account->update($data);
            $this->dispatch('toast', message: 'Cuenta actualizada exitosamente.', type: 'success');
        } else {
            AccountingAccount::create($data);
            $this->dispatch('toast', message: 'Cuenta creada exitosamente.', type: 'success');
        }

        $this->closeModal();
    }

    /**
     * Iniciar proceso de eliminación - Primera confirmación
     */
    public function confirmDelete($id)
    {
        if (!auth()->user()->can('accounting_accounts.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar cuentas.', type: 'error');
            return;
        }

        $account = AccountingAccount::with('childrenRecursive')->findOrFail($id);
        $this->accountToDelete = $account;
        $this->childrenCount = $this->countAllDescendants($account);
        $this->deleteStep = 1;
        $this->showDeleteModal = true;
    }

    /**
     * Contar todos los descendientes de una cuenta
     */
    private function countAllDescendants($account): int
    {
        $count = 0;
        foreach ($account->children as $child) {
            $count++;
            $count += $this->countAllDescendants($child);
        }
        return $count;
    }

    /**
     * Segunda confirmación - El usuario confirma que entiende las consecuencias
     */
    public function proceedToSecondConfirmation()
    {
        $this->deleteStep = 2;
    }

    /**
     * Ejecutar la eliminación en cascada
     */
    public function executeDelete()
    {
        if (!auth()->user()->can('accounting_accounts.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar cuentas.', type: 'error');
            return;
        }

        if (!$this->accountToDelete) {
            return;
        }

        $accountName = "{$this->accountToDelete->code} - {$this->accountToDelete->name}";
        $totalDeleted = $this->childrenCount + 1;

        // La eliminación en cascada está configurada en la migración
        $this->accountToDelete->delete();

        $this->closeDeleteModal();
        $this->dispatch('toast', 
            message: "Se eliminaron {$totalDeleted} cuenta(s) correctamente. ({$accountName} y sus subcuentas)", 
            type: 'success'
        );
    }

    /**
     * Cerrar modal de eliminación
     */
    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->accountToDelete = null;
        $this->deleteStep = 1;
        $this->childrenCount = 0;
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->can('accounting_accounts.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar cuentas.', type: 'error');
            return;
        }

        $account = AccountingAccount::findOrFail($id);
        $account->update(['is_active' => !$account->is_active]);
        
        $status = $account->is_active ? 'activada' : 'desactivada';
        $this->dispatch('toast', message: "Cuenta {$status} exitosamente.", type: 'success');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->accountId = null;
        $this->code = '';
        $this->name = '';
        $this->description = '';
        $this->level = 1;
        $this->parentId = null;
        $this->nature = 'D';
        $this->allowsMovement = false;
        $this->isActive = true;
        $this->isEditing = false;
        $this->parentName = '';
        $this->resetValidation();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.accounting-account-management');
    }
}

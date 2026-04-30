<?php

namespace App\Livewire;

use App\Models\InventoryAccountingAccount;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryAccountingAccountManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $isEditing = false;
    public $accountId;

    // Form fields
    public $code = '';
    public $name = '';
    public $depreciation_years = 0;
    public $is_active = true;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function rules()
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('inventory_accounting_accounts', 'code')->ignore($this->accountId),
            ],
            'name' => 'required|string|max:255',
            'depreciation_years' => 'required|integer|min:0|max:100',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'code.required' => 'El código es obligatorio.',
        'code.unique' => 'Este código ya está en uso.',
        'name.required' => 'El nombre es obligatorio.',
        'depreciation_years.required' => 'Los años de depreciación son obligatorios.',
        'depreciation_years.min' => 'Los años no pueden ser negativos.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('inventory_accounting_accounts.view'), 403);
    }

    public function getAccountsProperty()
    {
        return InventoryAccountingAccount::when($this->search, fn($q) => $q->search($this->search))
            ->orderBy('code')
            ->paginate(15);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        if (!auth()->user()->can('inventory_accounting_accounts.create')) {
            $this->dispatch('toast', message: 'No tiene permisos para crear cuentas.', type: 'error');
            return;
        }

        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->can('inventory_accounting_accounts.edit')) {
            $this->dispatch('toast', message: 'No tiene permisos para editar cuentas.', type: 'error');
            return;
        }

        $account = InventoryAccountingAccount::findOrFail($id);
        
        $this->accountId = $account->id;
        $this->code = $account->code;
        $this->name = $account->name;
        $this->depreciation_years = $account->depreciation_years;
        $this->is_active = $account->is_active;
        
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $permission = $this->isEditing ? 'inventory_accounting_accounts.edit' : 'inventory_accounting_accounts.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tiene permisos para realizar esta acción.', type: 'error');
            return;
        }

        $this->validate();

        InventoryAccountingAccount::updateOrCreate(
            ['id' => $this->accountId],
            [
                'code' => $this->code,
                'name' => $this->name,
                'depreciation_years' => $this->depreciation_years,
                'is_active' => $this->is_active,
            ]
        );

        $this->dispatch('toast', message: $this->isEditing ? 'Cuenta contable actualizada.' : 'Cuenta contable creada.', type: 'success');
        $this->closeModal();
    }

    public function resetForm()
    {
        $this->accountId = null;
        $this->code = '';
        $this->name = '';
        $this->depreciation_years = 0;
        $this->is_active = true;
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.inventory-accounting-account-management');
    }
}

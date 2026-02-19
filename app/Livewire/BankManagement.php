<?php

namespace App\Livewire;

use App\Models\Bank;
use App\Models\BankAccount;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

class BankManagement extends Component
{
    use WithPagination;

    public $schoolId;

    // Búsqueda y filtros
    public $search = '';
    public $filterStatus = '';
    public $perPage = 15;

    // Modal banco
    public $showBankModal = false;
    public $isEditingBank = false;
    public $bankId = null;
    public $bankName = '';
    public $bankCode = '';
    public $bankIsActive = true;
    public $bankNotes = '';

    // Modal cuenta
    public $showAccountModal = false;
    public $isEditingAccount = false;
    public $accountId = null;
    public $accountBankId = null;
    public $accountNumber = '';
    public $accountType = 'ahorros';
    public $holderName = '';
    public $accountDescription = '';
    public $accountIsActive = true;

    // Vista detalle banco
    public $showDetail = false;
    public $selectedBank = null;

    // Modal eliminación
    public $showDeleteBankModal = false;
    public $bankToDelete = null;
    public $showDeleteAccountModal = false;
    public $accountToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    protected function bankRules()
    {
        $uniqueRule = 'unique:banks,name';
        if ($this->bankId) {
            $uniqueRule .= ',' . $this->bankId . ',id,school_id,' . $this->schoolId;
        } else {
            $uniqueRule .= ',NULL,id,school_id,' . $this->schoolId;
        }

        return [
            'bankName' => ['required', 'string', 'max:150', $uniqueRule],
            'bankCode' => 'nullable|string|max:20',
            'bankIsActive' => 'boolean',
            'bankNotes' => 'nullable|string',
        ];
    }

    protected function accountRules()
    {
        $uniqueRule = 'unique:bank_accounts,account_number';
        if ($this->accountId) {
            $uniqueRule .= ',' . $this->accountId . ',id,bank_id,' . $this->accountBankId;
        } else {
            $uniqueRule .= ',NULL,id,bank_id,' . $this->accountBankId;
        }

        return [
            'accountBankId' => 'required|exists:banks,id',
            'accountNumber' => ['required', 'string', 'max:30', $uniqueRule],
            'accountType' => 'required|in:ahorros,corriente',
            'holderName' => 'nullable|string|max:200',
            'accountDescription' => 'nullable|string|max:255',
            'accountIsActive' => 'boolean',
        ];
    }

    protected $messages = [
        'bankName.required' => 'El nombre del banco es obligatorio.',
        'bankName.unique' => 'Ya existe un banco con este nombre.',
        'accountNumber.required' => 'El número de cuenta es obligatorio.',
        'accountNumber.unique' => 'Ya existe una cuenta con este número en este banco.',
        'accountBankId.required' => 'Debe seleccionar un banco.',
        'accountType.required' => 'El tipo de cuenta es obligatorio.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('banks.view'), 403, 'No tienes permisos para ver bancos.');

        $this->schoolId = session('selected_school_id');

        if (!$this->schoolId) {
            return redirect()->route('dashboard')->with('error', 'Debes seleccionar un colegio primero.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // ─── Computed Properties ────────────────────────────

    public function getBanksProperty()
    {
        return Bank::forSchool($this->schoolId)
            ->withCount(['accounts', 'accounts as active_accounts_count' => function ($q) {
                $q->where('is_active', true);
            }])
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->filterStatus !== '', function ($q) {
                $q->where('is_active', $this->filterStatus === '1');
            })
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    // ─── Bank CRUD ──────────────────────────────────────

    public function openCreateBankModal()
    {
        if (!auth()->user()->can('banks.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear bancos.', type: 'error');
            return;
        }

        $this->resetBankForm();
        $this->showBankModal = true;
    }

    public function editBank($id)
    {
        if (!auth()->user()->can('banks.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para editar bancos.', type: 'error');
            return;
        }

        $bank = Bank::forSchool($this->schoolId)->findOrFail($id);

        $this->bankId = $bank->id;
        $this->bankName = $bank->name;
        $this->bankCode = $bank->code;
        $this->bankIsActive = $bank->is_active;
        $this->bankNotes = $bank->notes;
        $this->isEditingBank = true;
        $this->showBankModal = true;
    }

    public function saveBank()
    {
        $permission = $this->isEditingBank ? 'banks.edit' : 'banks.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }

        $this->validate($this->bankRules());

        $data = [
            'school_id' => $this->schoolId,
            'name' => strtoupper($this->bankName),
            'code' => $this->bankCode ?: null,
            'is_active' => $this->bankIsActive,
            'notes' => $this->bankNotes,
        ];

        if ($this->isEditingBank) {
            $bank = Bank::forSchool($this->schoolId)->findOrFail($this->bankId);
            $bank->update($data);
            $this->dispatch('toast', message: 'Banco actualizado exitosamente.', type: 'success');

            // Refrescar detalle si está abierto
            if ($this->showDetail && $this->selectedBank && $this->selectedBank->id === $bank->id) {
                $this->selectedBank = $bank->fresh(['accounts']);
            }
        } else {
            Bank::create($data);
            $this->dispatch('toast', message: 'Banco creado exitosamente.', type: 'success');
        }

        $this->closeBankModal();
    }

    public function confirmDeleteBank($id)
    {
        if (!auth()->user()->can('banks.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar bancos.', type: 'error');
            return;
        }

        $this->bankToDelete = Bank::forSchool($this->schoolId)->withCount('accounts')->findOrFail($id);
        $this->showDeleteBankModal = true;
    }

    public function deleteBank()
    {
        if (!auth()->user()->can('banks.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar bancos.', type: 'error');
            return;
        }

        if ($this->bankToDelete) {
            $name = $this->bankToDelete->name;
            $this->bankToDelete->delete();
            $this->dispatch('toast', message: "Banco '{$name}' y sus cuentas eliminados exitosamente.", type: 'success');

            // Cerrar detalle si era el banco eliminado
            if ($this->showDetail && $this->selectedBank && $this->selectedBank->id === $this->bankToDelete->id) {
                $this->closeDetail();
            }
        }

        $this->closeDeleteBankModal();
    }

    public function toggleBankStatus($id)
    {
        if (!auth()->user()->can('banks.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar bancos.', type: 'error');
            return;
        }

        $bank = Bank::forSchool($this->schoolId)->findOrFail($id);
        $bank->update(['is_active' => !$bank->is_active]);

        $status = $bank->is_active ? 'activado' : 'desactivado';
        $this->dispatch('toast', message: "Banco {$status} exitosamente.", type: 'success');
    }

    // ─── Bank Detail ────────────────────────────────────

    public function showBankDetail($id)
    {
        $this->selectedBank = Bank::forSchool($this->schoolId)
            ->with(['accounts' => fn($q) => $q->orderBy('account_number')])
            ->findOrFail($id);
        $this->showDetail = true;
    }

    public function closeDetail()
    {
        $this->showDetail = false;
        $this->selectedBank = null;
    }

    // ─── Account CRUD ───────────────────────────────────

    public function openCreateAccountModal($bankId = null)
    {
        if (!auth()->user()->can('banks.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear cuentas.', type: 'error');
            return;
        }

        $this->resetAccountForm();
        $this->accountBankId = $bankId ?? ($this->selectedBank ? $this->selectedBank->id : null);
        $this->showAccountModal = true;
    }

    public function editAccount($id)
    {
        if (!auth()->user()->can('banks.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para editar cuentas.', type: 'error');
            return;
        }

        $account = BankAccount::whereHas('bank', function ($q) {
            $q->where('school_id', $this->schoolId);
        })->findOrFail($id);

        $this->accountId = $account->id;
        $this->accountBankId = $account->bank_id;
        $this->accountNumber = $account->account_number;
        $this->accountType = $account->account_type;
        $this->holderName = $account->holder_name;
        $this->accountDescription = $account->description;
        $this->accountIsActive = $account->is_active;
        $this->isEditingAccount = true;
        $this->showAccountModal = true;
    }

    public function saveAccount()
    {
        $permission = $this->isEditingAccount ? 'banks.edit' : 'banks.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }

        // Verificar que el banco pertenece al colegio
        $bank = Bank::forSchool($this->schoolId)->findOrFail($this->accountBankId);

        $this->validate($this->accountRules());

        $data = [
            'bank_id' => $bank->id,
            'account_number' => $this->accountNumber,
            'account_type' => $this->accountType,
            'holder_name' => $this->holderName ?: null,
            'description' => $this->accountDescription ?: null,
            'is_active' => $this->accountIsActive,
        ];

        if ($this->isEditingAccount) {
            $account = BankAccount::findOrFail($this->accountId);
            $account->update($data);
            $this->dispatch('toast', message: 'Cuenta actualizada exitosamente.', type: 'success');
        } else {
            BankAccount::create($data);
            $this->dispatch('toast', message: 'Cuenta creada exitosamente.', type: 'success');
        }

        // Refrescar detalle
        if ($this->showDetail && $this->selectedBank) {
            $this->selectedBank = $this->selectedBank->fresh(['accounts']);
        }

        $this->closeAccountModal();
    }

    public function confirmDeleteAccount($id)
    {
        if (!auth()->user()->can('banks.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar cuentas.', type: 'error');
            return;
        }

        $this->accountToDelete = BankAccount::whereHas('bank', function ($q) {
            $q->where('school_id', $this->schoolId);
        })->findOrFail($id);

        $this->showDeleteAccountModal = true;
    }

    public function deleteAccount()
    {
        if (!auth()->user()->can('banks.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar cuentas.', type: 'error');
            return;
        }

        if ($this->accountToDelete) {
            $number = $this->accountToDelete->account_number;
            $this->accountToDelete->delete();
            $this->dispatch('toast', message: "Cuenta '{$number}' eliminada exitosamente.", type: 'success');
        }

        // Refrescar detalle
        if ($this->showDetail && $this->selectedBank) {
            $this->selectedBank = $this->selectedBank->fresh(['accounts']);
        }

        $this->closeDeleteAccountModal();
    }

    public function toggleAccountStatus($id)
    {
        if (!auth()->user()->can('banks.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar cuentas.', type: 'error');
            return;
        }

        $account = BankAccount::whereHas('bank', function ($q) {
            $q->where('school_id', $this->schoolId);
        })->findOrFail($id);

        $account->update(['is_active' => !$account->is_active]);

        $status = $account->is_active ? 'activada' : 'desactivada';
        $this->dispatch('toast', message: "Cuenta {$status} exitosamente.", type: 'success');

        // Refrescar detalle
        if ($this->showDetail && $this->selectedBank) {
            $this->selectedBank = $this->selectedBank->fresh(['accounts']);
        }
    }

    // ─── Helpers ────────────────────────────────────────

    public function closeBankModal()
    {
        $this->showBankModal = false;
        $this->resetBankForm();
    }

    public function closeAccountModal()
    {
        $this->showAccountModal = false;
        $this->resetAccountForm();
    }

    public function closeDeleteBankModal()
    {
        $this->showDeleteBankModal = false;
        $this->bankToDelete = null;
    }

    public function closeDeleteAccountModal()
    {
        $this->showDeleteAccountModal = false;
        $this->accountToDelete = null;
    }

    public function resetBankForm()
    {
        $this->bankId = null;
        $this->bankName = '';
        $this->bankCode = '';
        $this->bankIsActive = true;
        $this->bankNotes = '';
        $this->isEditingBank = false;
        $this->resetValidation();
    }

    public function resetAccountForm()
    {
        $this->accountId = null;
        $this->accountBankId = null;
        $this->accountNumber = '';
        $this->accountType = 'ahorros';
        $this->holderName = '';
        $this->accountDescription = '';
        $this->accountIsActive = true;
        $this->isEditingAccount = false;
        $this->resetValidation();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterStatus']);
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.bank-management');
    }
}

<?php

namespace App\Livewire;

use App\Models\ExpenseCode;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

class ExpenseCodeManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $perPage = 15;

    public $showModal = false;
    public $isEditing = false;
    public $expenseCodeId = null;
    public $code = '';
    public $name = '';
    public $is_active = true;

    public $showDeleteModal = false;
    public $itemToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    protected function rules()
    {
        $uniqueRule = $this->isEditing 
            ? "unique:expense_codes,code,{$this->expenseCodeId}" 
            : 'unique:expense_codes,code';

        return [
            'code' => ['required', 'string', 'max:50', $uniqueRule],
            'name' => 'required|string|max:500',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'code.required' => 'El código es obligatorio.',
        'code.unique' => 'Este código ya existe.',
        'code.max' => 'El código no puede tener más de 50 caracteres.',
        'name.required' => 'El nombre es obligatorio.',
        'name.max' => 'El nombre no puede tener más de 500 caracteres.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('expense_codes.view'), 403);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function getExpenseCodesProperty()
    {
        $query = ExpenseCode::query();

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus === '1');
        }

        return $query->orderBy('code')->paginate($this->perPage);
    }

    public function getTotalsProperty()
    {
        return [
            'total' => ExpenseCode::count(),
            'active' => ExpenseCode::active()->count(),
        ];
    }

    public function openCreateModal()
    {
        if (!auth()->user()->can('expense_codes.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear códigos.', type: 'error');
            return;
        }
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->can('expense_codes.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para editar códigos.', type: 'error');
            return;
        }

        $expenseCode = ExpenseCode::findOrFail($id);
        $this->expenseCodeId = $expenseCode->id;
        $this->code = $expenseCode->code;
        $this->name = $expenseCode->name;
        $this->is_active = $expenseCode->is_active;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $permission = $this->isEditing ? 'expense_codes.edit' : 'expense_codes.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }

        $this->validate();

        $data = [
            'code' => strtoupper(trim($this->code)),
            'name' => mb_strtoupper(trim($this->name)),
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $expenseCode = ExpenseCode::findOrFail($this->expenseCodeId);
            $expenseCode->update($data);
            $this->dispatch('toast', message: 'Código actualizado exitosamente.', type: 'success');
        } else {
            ExpenseCode::create($data);
            $this->dispatch('toast', message: 'Código creado exitosamente.', type: 'success');
        }

        $this->closeModal();
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->can('expense_codes.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar códigos.', type: 'error');
            return;
        }

        $this->itemToDelete = ExpenseCode::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if (!auth()->user()->can('expense_codes.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar códigos.', type: 'error');
            return;
        }

        if ($this->itemToDelete) {
            $this->itemToDelete->delete();
            $this->dispatch('toast', message: 'Código eliminado exitosamente.', type: 'success');
        }

        $this->closeDeleteModal();
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->can('expense_codes.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para cambiar el estado.', type: 'error');
            return;
        }

        $expenseCode = ExpenseCode::findOrFail($id);
        $expenseCode->update(['is_active' => !$expenseCode->is_active]);
        
        $status = $expenseCode->is_active ? 'activado' : 'desactivado';
        $this->dispatch('toast', message: "Código {$status}.", type: 'success');
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
        $this->expenseCodeId = null;
        $this->code = '';
        $this->name = '';
        $this->is_active = true;
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.expense-code-management');
    }
}

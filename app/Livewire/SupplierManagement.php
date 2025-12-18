<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Municipality;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

class SupplierManagement extends Component
{
    use WithPagination;

    public $schoolId;

    // Búsqueda y filtros
    public $search = '';
    public $filterStatus = '';
    public $filterPersonType = '';
    public $filterTaxRegime = '';
    public $perPage = 15;

    // Modal y formulario
    public $showModal = false;
    public $isEditing = false;
    public $supplierId = null;

    // Campos del formulario
    public $document_type = 'CC';
    public $document_number = '';
    public $dv = '';
    public $first_name = '';
    public $second_name = '';
    public $first_surname = '';
    public $second_surname = '';
    public $person_type = 'natural';
    public $tax_regime = 'simplificado';
    public $address = '';
    public $department_id = '';
    public $municipality_id = '';
    public $phone = '';
    public $mobile = '';
    public $email = '';
    public $bank_name = '';
    public $account_type = '';
    public $account_number = '';
    public $is_active = true;
    public $notes = '';

    // Colecciones para selects
    public $departments = [];
    public $municipalities = [];

    // Modal de eliminación
    public $showDeleteModal = false;
    public $supplierToDelete = null;

    // QueryString para mantener filtros en URL
    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterPersonType' => ['except' => ''],
    ];

    protected function rules()
    {
        $uniqueRule = 'unique:suppliers,document_number';
        if ($this->supplierId) {
            $uniqueRule .= ',' . $this->supplierId . ',id,school_id,' . $this->schoolId . ',document_type,' . $this->document_type;
        } else {
            $uniqueRule .= ',NULL,id,school_id,' . $this->schoolId . ',document_type,' . $this->document_type;
        }

        return [
            'document_type' => 'required|in:CC,CE,NIT,TI,PA,RC,NUIP',
            'document_number' => ['required', 'string', 'max:20', $uniqueRule],
            'dv' => 'nullable|string|size:1',
            'first_name' => 'nullable|string|max:100',
            'second_name' => 'nullable|string|max:100',
            'first_surname' => 'required|string|max:150',
            'second_surname' => 'nullable|string|max:100',
            'person_type' => 'required|in:natural,juridica',
            'tax_regime' => 'required|in:simplificado,comun,gran_contribuyente,no_responsable',
            'address' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'municipality_id' => 'required|exists:municipalities,id',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'bank_name' => 'nullable|string|max:100',
            'account_type' => 'nullable|in:ahorros,corriente',
            'account_number' => 'nullable|string|max:30',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    protected $messages = [
        'document_type.required' => 'El tipo de documento es obligatorio.',
        'document_number.required' => 'El número de documento es obligatorio.',
        'document_number.unique' => 'Ya existe un proveedor con este documento.',
        'first_surname.required' => 'El primer apellido o razón social es obligatorio.',
        'address.required' => 'La dirección es obligatoria.',
        'department_id.required' => 'El departamento es obligatorio.',
        'municipality_id.required' => 'El municipio es obligatorio.',
        'tax_regime.required' => 'El régimen tributario es obligatorio.',
        'email.email' => 'Ingrese un correo electrónico válido.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('suppliers.view'), 403, 'No tienes permisos para ver proveedores.');
        
        $this->schoolId = session('selected_school_id');
        
        if (!$this->schoolId) {
            return redirect()->route('dashboard')->with('error', 'Debes seleccionar un colegio primero.');
        }

        $this->departments = Department::orderBy('name')->get();
    }

    public function updatedDepartmentId($value)
    {
        $this->municipality_id = '';
        $this->municipalities = $value 
            ? Municipality::where('department_id', $value)->orderBy('name')->get() 
            : [];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedDocumentType($value)
    {
        // Si es NIT, cambiar a persona jurídica automáticamente
        if ($value === 'NIT') {
            $this->person_type = 'juridica';
        } else {
            $this->person_type = 'natural';
            $this->dv = ''; // Limpiar DV si no es NIT
        }
    }

    public function updatedPersonType($value)
    {
        // Si es persona jurídica, cambiar a NIT
        if ($value === 'juridica') {
            $this->document_type = 'NIT';
        }
    }

    /**
     * Calcular DV automáticamente
     */
    public function calculateDv()
    {
        if ($this->document_type === 'NIT' && !empty($this->document_number)) {
            $this->dv = Supplier::calculateDv($this->document_number);
        }
    }

    public function getSuppliersProperty()
    {
        return Supplier::forSchool($this->schoolId)
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->filterStatus !== '', function ($q) {
                $q->where('is_active', $this->filterStatus === '1');
            })
            ->when($this->filterPersonType, fn($q) => $q->where('person_type', $this->filterPersonType))
            ->when($this->filterTaxRegime, fn($q) => $q->where('tax_regime', $this->filterTaxRegime))
            ->orderBy('first_surname')
            ->paginate($this->perPage);
    }

    public function openCreateModal()
    {
        if (!auth()->user()->can('suppliers.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear proveedores.', type: 'error');
            return;
        }

        $this->resetForm();
        $this->showModal = true;
    }

    public function editSupplier($id)
    {
        if (!auth()->user()->can('suppliers.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para editar proveedores.', type: 'error');
            return;
        }

        $supplier = Supplier::forSchool($this->schoolId)->findOrFail($id);

        $this->supplierId = $supplier->id;
        $this->document_type = $supplier->document_type;
        $this->document_number = $supplier->document_number;
        $this->dv = $supplier->dv;
        $this->first_name = $supplier->first_name;
        $this->second_name = $supplier->second_name;
        $this->first_surname = $supplier->first_surname;
        $this->second_surname = $supplier->second_surname;
        $this->person_type = $supplier->person_type;
        $this->tax_regime = $supplier->tax_regime;
        $this->address = $supplier->address;
        $this->department_id = $supplier->department_id;
        if ($this->department_id) {
            $this->municipalities = Municipality::where('department_id', $this->department_id)->orderBy('name')->get();
        }
        $this->municipality_id = $supplier->municipality_id;
        $this->phone = $supplier->phone;
        $this->mobile = $supplier->mobile;
        $this->email = $supplier->email;
        $this->bank_name = $supplier->bank_name;
        $this->account_type = $supplier->account_type;
        $this->account_number = $supplier->account_number;
        $this->is_active = $supplier->is_active;
        $this->notes = $supplier->notes;

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $permission = $this->isEditing ? 'suppliers.edit' : 'suppliers.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }

        $this->validate();

        // Si es NIT y no tiene DV, calcularlo
        if ($this->document_type === 'NIT' && empty($this->dv)) {
            $this->dv = Supplier::calculateDv($this->document_number);
        }

        $data = [
            'school_id' => $this->schoolId,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'dv' => $this->document_type === 'NIT' ? $this->dv : null,
            'first_name' => $this->person_type === 'natural' ? $this->first_name : null,
            'second_name' => $this->person_type === 'natural' ? $this->second_name : null,
            'first_surname' => strtoupper($this->first_surname),
            'second_surname' => $this->person_type === 'natural' ? strtoupper($this->second_surname) : null,
            'person_type' => $this->person_type,
            'tax_regime' => $this->tax_regime,
            'address' => $this->address,
            'department_id' => $this->department_id,
            'municipality_id' => $this->municipality_id,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'email' => $this->email ? strtolower($this->email) : null,
            'bank_name' => $this->bank_name,
            'account_type' => $this->account_type ?: null,
            'account_number' => $this->account_number,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
        ];

        if ($this->isEditing) {
            $supplier = Supplier::forSchool($this->schoolId)->findOrFail($this->supplierId);
            $supplier->update($data);
            $this->dispatch('toast', message: 'Proveedor actualizado exitosamente.', type: 'success');
        } else {
            Supplier::create($data);
            $this->dispatch('toast', message: 'Proveedor creado exitosamente.', type: 'success');
        }

        $this->closeModal();
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->can('suppliers.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar proveedores.', type: 'error');
            return;
        }

        $this->supplierToDelete = Supplier::forSchool($this->schoolId)->findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function deleteSupplier()
    {
        if (!auth()->user()->can('suppliers.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar proveedores.', type: 'error');
            return;
        }

        if ($this->supplierToDelete) {
            $name = $this->supplierToDelete->full_name;
            $this->supplierToDelete->delete();
            $this->dispatch('toast', message: "Proveedor '{$name}' eliminado exitosamente.", type: 'success');
        }

        $this->closeDeleteModal();
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->can('suppliers.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para modificar proveedores.', type: 'error');
            return;
        }

        $supplier = Supplier::forSchool($this->schoolId)->findOrFail($id);
        $supplier->update(['is_active' => !$supplier->is_active]);

        $status = $supplier->is_active ? 'activado' : 'desactivado';
        $this->dispatch('toast', message: "Proveedor {$status} exitosamente.", type: 'success');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->supplierToDelete = null;
    }

    public function resetForm()
    {
        $this->supplierId = null;
        $this->document_type = 'CC';
        $this->document_number = '';
        $this->dv = '';
        $this->first_name = '';
        $this->second_name = '';
        $this->first_surname = '';
        $this->second_surname = '';
        $this->person_type = 'natural';
        $this->tax_regime = 'simplificado';
        $this->address = '';
        $this->department_id = '';
        $this->municipality_id = '';
        $this->municipalities = [];
        $this->phone = '';
        $this->mobile = '';
        $this->email = '';
        $this->bank_name = '';
        $this->account_type = '';
        $this->account_number = '';
        $this->is_active = true;
        $this->notes = '';
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterStatus', 'filterPersonType', 'filterTaxRegime']);
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.supplier-management');
    }
}

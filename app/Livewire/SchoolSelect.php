<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\School;
use Illuminate\Support\Facades\Auth;

class SchoolSelect extends Component
{
    use WithPagination;

    // Propiedades de búsqueda y modal
    public $search = '';
    public $openSchoolModal = false; // Controla si se ve el modal grande
    public $showCreateModal = false; // Controla el modal interno de crear/editar
    public $isEditing = false;
    public $editingId = null;

    // Campos del formulario de Colegio
    public $name, $nit, $dane_code, $municipality, $rector_name, $rector_document;
    public $pagador_name, $current_validity, $address, $email, $phone, $website;
    public $budget_agreement_number, $budget_approval_date, $contracting_manual_approval_number, $contracting_manual_approval_date;
    public $dian_resolution_1, $dian_range_1, $dian_expiration_1, $dian_resolution_2, $dian_range_2, $dian_expiration_2;

    public function mount()
    {
        // Only Admin can use this component
        if (!auth()->user()->hasRole('Admin')) {
            abort(403, 'No tienes permiso para acceder a esta funcionalidad.');
        }
    }

    // Reiniciar paginación al buscar
    public function updatingSearch()
    {
        $this->resetPage();
    }

    // Acción principal: Seleccionar un colegio
    public function selectSchool($schoolId)
    {
        // Verify user has permission
        if (!auth()->user()->can('schools.view')) {
            $this->dispatch('notify', message: 'No tienes permiso para seleccionar colegios.', type: 'error');
            return;
        }

        // Guardamos en sesión el ID del colegio seleccionado
        session(['selected_school_id' => $schoolId]);

        $this->dispatch('notify', message: 'Colegio seleccionado correctamente.', type: 'success');

        // Redirigimos al dashboard para que recargue con la info nueva
        return redirect()->route('dashboard');
    }

    // Abrir modal de creación
    public function openCreateModal()
    {
        if (!auth()->user()->can('schools.create')) {
            $this->dispatch('notify', message: 'No tienes permiso para crear colegios.', type: 'error');
            return;
        }

        $this->resetValidation();
        $this->resetForm();
        $this->isEditing = false;
        $this->showCreateModal = true;
    }

    // Abrir modal de edición
    public function editSchool($id)
    {
        if (!auth()->user()->can('schools.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar colegios.', type: 'error');
            return;
        }

        $this->resetValidation();
        $this->resetForm();
        $this->isEditing = true;
        $this->editingId = $id;

        $school = School::find($id);
        
        // Rellenar formulario
        $this->name = $school->name;
        $this->nit = $school->nit;
        $this->dane_code = $school->dane_code;
        $this->municipality = $school->municipality;
        $this->rector_name = $school->rector_name;
        $this->rector_document = $school->rector_document;
        $this->pagador_name = $school->pagador_name;
        $this->current_validity = $school->current_validity;
        $this->address = $school->address;
        $this->email = $school->email;
        $this->phone = $school->phone;
        $this->website = $school->website;
        $this->budget_agreement_number = $school->budget_agreement_number;
        $this->budget_approval_date = $school->budget_approval_date;
        $this->contracting_manual_approval_number = $school->contracting_manual_approval_number;
        $this->contracting_manual_approval_date = $school->contracting_manual_approval_date;
        $this->dian_resolution_1 = $school->dian_resolution_1;
        $this->dian_range_1 = $school->dian_range_1;
        $this->dian_expiration_1 = $school->dian_expiration_1;
        $this->dian_resolution_2 = $school->dian_resolution_2;
        $this->dian_range_2 = $school->dian_range_2;
        $this->dian_expiration_2 = $school->dian_expiration_2;

        $this->showCreateModal = true;
    }

    // Guardar (Crear o Actualizar)
    public function saveSchool()
    {
        // Check permission based on action
        $permission = $this->isEditing ? 'schools.edit' : 'schools.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('notify', message: 'No tienes permiso para realizar esta acción.', type: 'error');
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'nit' => 'required|string|max:50|unique:schools,nit,' . $this->editingId,
            'dane_code' => 'required|string|max:50',
            'municipality' => 'required|string|max:255',
            'rector_name' => 'required|string|max:255',
            'rector_document' => 'required|string|max:50',
            'pagador_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'budget_agreement_number' => 'required|string|max:50',
            'budget_approval_date' => 'required|date',
            'current_validity' => 'required|integer|min:2000|max:2100',
            'contracting_manual_approval_number' => 'nullable|string|max:50',
            'contracting_manual_approval_date' => 'nullable|date',
            'dian_resolution_1' => 'nullable|string|max:50',
            'dian_range_1' => 'nullable|string|max:50',
            'dian_expiration_1' => 'nullable|date',
            'dian_resolution_2' => 'nullable|string|max:50',
            'dian_range_2' => 'nullable|string|max:50',
            'dian_expiration_2' => 'nullable|date',
        ]);

        $data = [
            'name' => $this->name,
            'nit' => $this->nit,
            'dane_code' => $this->dane_code,
            'municipality' => $this->municipality,
            'rector_name' => $this->rector_name,
            'rector_document' => $this->rector_document,
            'pagador_name' => $this->pagador_name ?: null,
            'current_validity' => $this->current_validity,
            'address' => $this->address ?: null,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'website' => $this->website ?: null,
            'budget_agreement_number' => $this->budget_agreement_number,
            'budget_approval_date' => $this->budget_approval_date,
            'contracting_manual_approval_number' => $this->contracting_manual_approval_number ?: null,
            'contracting_manual_approval_date' => $this->contracting_manual_approval_date ?: null,
            'dian_resolution_1' => $this->dian_resolution_1 ?: null,
            'dian_range_1' => $this->dian_range_1 ?: null,
            'dian_expiration_1' => $this->dian_expiration_1 ?: null,
            'dian_resolution_2' => $this->dian_resolution_2 ?: null,
            'dian_range_2' => $this->dian_range_2 ?: null,
            'dian_expiration_2' => $this->dian_expiration_2 ?: null,
        ];

        if ($this->isEditing) {
            School::find($this->editingId)->update($data);
            $this->dispatch('notify', message: 'Colegio actualizado exitosamente.', type: 'success');
        } else {
            School::create($data);
            $this->dispatch('notify', message: 'Colegio creado exitosamente.', type: 'success');
        }

        $this->showCreateModal = false;
        $this->resetForm();
    }

    // Eliminar
    public function deleteSchool($id)
    {
        if (!auth()->user()->can('schools.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para eliminar colegios.', type: 'error');
            return;
        }

        $school = School::find($id);
        
        // Check if school has users
        if ($school->users()->count() > 0) {
            $this->dispatch('notify', message: 'No puedes eliminar un colegio que tiene usuarios asignados.', type: 'error');
            return;
        }

        $school->delete();
        $this->dispatch('notify', message: 'Colegio eliminado exitosamente.', type: 'success');
    }

    private function resetForm()
    {
        $this->reset([
            'name', 'nit', 'dane_code', 'municipality', 'rector_name', 'rector_document',
            'pagador_name', 'current_validity', 'address', 'email', 'phone', 'website',
            'budget_agreement_number', 'budget_approval_date', 'contracting_manual_approval_number',
            'contracting_manual_approval_date', 'dian_resolution_1', 'dian_range_1',
            'dian_expiration_1', 'dian_resolution_2', 'dian_range_2', 'dian_expiration_2',
            'editingId', 'isEditing'
        ]);
    }

    public function render()
    {
        // Consultar escuelas con buscador
        $schools = School::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('nit', 'like', '%' . $this->search . '%')
                      ->orWhere('municipality', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(9);

        return view('livewire.school-select', [
            'schools' => $schools
        ]);
    }
}

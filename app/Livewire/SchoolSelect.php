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

    // Reiniciar paginación al buscar
    public function updatingSearch()
    {
        $this->resetPage();
    }

    // Acción principal: Seleccionar un colegio
    public function selectSchool($schoolId)
    {
        // Guardamos en sesión el ID del colegio seleccionado
        session(['selected_school_id' => $schoolId]);

        // Redirigimos al dashboard para que recargue con la info nueva
        return redirect()->route('dashboard');
    }

    // Abrir modal de creación
    public function openCreateModal()
    {
        $this->resetValidation();
        $this->resetForm();
        $this->isEditing = false;
        $this->showCreateModal = true;
    }

    // Abrir modal de edición
    public function editSchool($id)
    {
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
        $this->validate([
            'name' => 'required',
            'nit' => 'required',
            'dane_code' => 'required',
            'municipality' => 'required',
            'rector_name' => 'required',
            'rector_document' => 'required',
            'email' => 'required|email',
            'budget_agreement_number' => 'required',
            'budget_approval_date' => 'required|date',
            'current_validity' => 'required|numeric',
        ]);

        $data = [
            'name' => $this->name,
            'nit' => $this->nit,
            'dane_code' => $this->dane_code,
            'municipality' => $this->municipality,
            'rector_name' => $this->rector_name,
            'rector_document' => $this->rector_document,
            'pagador_name' => $this->pagador_name,
            'current_validity' => $this->current_validity,
            'address' => $this->address,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'budget_agreement_number' => $this->budget_agreement_number,
            'budget_approval_date' => $this->budget_approval_date,
            'contracting_manual_approval_number' => $this->contracting_manual_approval_number,
            'contracting_manual_approval_date' => $this->contracting_manual_approval_date,
            'dian_resolution_1' => $this->dian_resolution_1,
            'dian_range_1' => $this->dian_range_1,
            'dian_expiration_1' => $this->dian_expiration_1,
            'dian_resolution_2' => $this->dian_resolution_2,
            'dian_range_2' => $this->dian_range_2,
            'dian_expiration_2' => $this->dian_expiration_2,
        ];

        if ($this->isEditing) {
            School::find($this->editingId)->update($data);
        } else {
            School::create($data);
        }

        $this->showCreateModal = false;
        $this->resetForm();
    }

    // Eliminar
    public function deleteSchool($id)
    {
        School::find($id)->delete();
    }

    private function resetForm()
    {
        $this->reset([
            'name', 'nit', 'dane_code', 'municipality', 'rector_name', 'rector_document',
            'pagador_name', 'current_validity', 'address', 'email', 'phone', 'website',
            'budget_agreement_number', 'budget_approval_date', 'contracting_manual_approval_number',
            'contracting_manual_approval_date', 'dian_resolution_1', 'dian_range_1',
            'dian_expiration_1', 'dian_resolution_2', 'dian_range_2', 'dian_expiration_2'
        ]);
    }

    public function render()
    {
        // Consultar escuelas con buscador
        $schools = School::query()
            ->where('name', 'like', '%' . $this->search . '%')
            ->orWhere('nit', 'like', '%' . $this->search . '%')
            ->orWhere('municipality', 'like', '%' . $this->search . '%')
            ->orderBy('created_at', 'desc')
            ->paginate(9);

        return view('livewire.school-select', [
            'schools' => $schools
        ]);
    }
}

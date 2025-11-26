<?php

namespace App\Livewire;

use App\Models\School;
use Livewire\Component;
use Livewire\WithPagination;

class SchoolSelection extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 12;
    public $showCreateModal = false;
    
    // Form fields for creating school
    public $name = '';
    public $nit = '';
    public $dane_code = '';
    public $municipality = '';
    public $rector_name = '';
    public $rector_document = '';
    public $pagador_name = '';
    public $address = '';
    public $email = '';
    public $phone = '';
    public $website = '';
    public $budget_agreement_number = '';
    public $budget_approval_date = '';
    public $current_validity = '';
    public $contracting_manual_approval_number = '';
    public $contracting_manual_approval_date = '';
    public $dian_resolution_1 = '';
    public $dian_resolution_2 = '';
    public $dian_range_1 = '';
    public $dian_range_2 = '';
    public $dian_expiration_1 = '';
    public $dian_expiration_2 = '';

    public $schoolId = null;
    public $isEditing = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function selectSchool($schoolId)
    {
        session(['selected_school_id' => $schoolId]);
        
        $this->dispatch('notify', message: 'Colegio seleccionado correctamente.', type: 'success');
        
        return redirect()->route('dashboard');
    }

    public function openCreateModal()
    {
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->reset([
            'name', 'nit', 'dane_code', 'municipality', 'rector_name', 
            'rector_document', 'pagador_name', 'address', 'email', 'phone',
            'website', 'budget_agreement_number', 'budget_approval_date',
            'current_validity', 'contracting_manual_approval_number',
            'contracting_manual_approval_date', 'dian_resolution_1',
            'dian_resolution_2', 'dian_range_1', 'dian_range_2',
            'dian_expiration_1', 'dian_expiration_2', 'schoolId', 'isEditing'
        ]);
    }

    public function editSchool($id)
    {
        $this->resetValidation();
        $school = School::findOrFail($id);
        $this->schoolId = $school->id;
        $this->name = $school->name;
        $this->nit = $school->nit;
        $this->dane_code = $school->dane_code;
        $this->municipality = $school->municipality;
        $this->rector_name = $school->rector_name;
        $this->rector_document = $school->rector_document;
        $this->pagador_name = $school->pagador_name;
        $this->address = $school->address;
        $this->email = $school->email;
        $this->phone = $school->phone;
        $this->website = $school->website;
        $this->budget_agreement_number = $school->budget_agreement_number;
        $this->budget_approval_date = $school->budget_approval_date;
        $this->current_validity = $school->current_validity;
        $this->contracting_manual_approval_number = $school->contracting_manual_approval_number;
        $this->contracting_manual_approval_date = $school->contracting_manual_approval_date;
        $this->dian_resolution_1 = $school->dian_resolution_1;
        $this->dian_resolution_2 = $school->dian_resolution_2;
        $this->dian_range_1 = $school->dian_range_1;
        $this->dian_range_2 = $school->dian_range_2;
        $this->dian_expiration_1 = $school->dian_expiration_1;
        $this->dian_expiration_2 = $school->dian_expiration_2;
        
        $this->isEditing = true;
        $this->showCreateModal = true;
    }

    public function saveSchool()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'nit' => 'required|string|max:20|unique:schools,nit,' . $this->schoolId,
            'dane_code' => 'nullable|string|max:50',
            'municipality' => 'required|string|max:100',
            'rector_name' => 'nullable|string|max:255',
            'rector_document' => 'nullable|string|max:50',
            'pagador_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'budget_agreement_number' => 'nullable|string|max:50',
            'budget_approval_date' => 'nullable|date',
            'current_validity' => 'nullable|integer|min:2000|max:2100',
            'contracting_manual_approval_number' => 'nullable|string|max:50',
            'contracting_manual_approval_date' => 'nullable|date',
            'dian_resolution_1' => 'nullable|string|max:50',
            'dian_resolution_2' => 'nullable|string|max:50',
            'dian_range_1' => 'nullable|string|max:50',
            'dian_range_2' => 'nullable|string|max:50',
            'dian_expiration_1' => 'nullable|date',
            'dian_expiration_2' => 'nullable|date',
        ];

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'nit' => $this->nit,
            'dane_code' => $this->dane_code,
            'municipality' => $this->municipality,
            'rector_name' => $this->rector_name,
            'rector_document' => $this->rector_document,
            'pagador_name' => $this->pagador_name,
            'address' => $this->address,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'budget_agreement_number' => $this->budget_agreement_number,
            'budget_approval_date' => $this->budget_approval_date ?: null,
            'current_validity' => $this->current_validity,
            'contracting_manual_approval_number' => $this->contracting_manual_approval_number,
            'contracting_manual_approval_date' => $this->contracting_manual_approval_date ?: null,
            'dian_resolution_1' => $this->dian_resolution_1,
            'dian_resolution_2' => $this->dian_resolution_2,
            'dian_range_1' => $this->dian_range_1,
            'dian_range_2' => $this->dian_range_2,
            'dian_expiration_1' => $this->dian_expiration_1 ?: null,
            'dian_expiration_2' => $this->dian_expiration_2 ?: null,
        ];

        if ($this->isEditing) {
            $school = School::findOrFail($this->schoolId);
            $school->update($data);
            $this->dispatch('notify', message: 'Colegio actualizado exitosamente.', type: 'success');
        } else {
            $school = School::create($data);
            
            // Assign current user as admin of this school if they created it?
            // Or just leave it. Admin can access everything.
            
            $this->dispatch('notify', message: 'Colegio creado exitosamente.', type: 'success');
        }

        $this->showCreateModal = false;
        $this->reset(['name', 'nit', 'dane_code', 'municipality', 'rector_name', 'rector_document', 'pagador_name', 'address', 'email', 'phone', 'website', 'budget_agreement_number', 'budget_approval_date', 'current_validity', 'contracting_manual_approval_number', 'contracting_manual_approval_date', 'dian_resolution_1', 'dian_resolution_2', 'dian_range_1', 'dian_range_2', 'dian_expiration_1', 'dian_expiration_2', 'schoolId', 'isEditing']);
    }

    public function deleteSchool($id)
    {
        $school = School::findOrFail($id);
        $school->delete();
        $this->dispatch('notify', message: 'Colegio eliminado exitosamente.', type: 'success');
    }

    public function render()
    {
        $query = School::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('nit', 'like', '%' . $this->search . '%')
                  ->orWhere('municipality', 'like', '%' . $this->search . '%');
            });
        }

        return view('livewire.school-selection', [
            'schools' => $query->orderBy('created_at', 'desc')->paginate($this->perPage)
        ])->layout('layouts.school-select');
    }
}

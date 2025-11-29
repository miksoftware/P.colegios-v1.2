<?php

namespace App\Livewire;

use App\Models\School;
use Livewire\Component;

class SchoolInfo extends Component
{
    public $school;
    public $isEditing = false;
    
    // Form fields
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

    public function mount()
    {
        // Get the school assigned to the current user (non-admin users have only one school)
        $user = auth()->user();
        
        // If user is admin, redirect to school management
        if ($user->hasRole('Admin')) {
            return redirect()->route('dashboard');
        }

        $this->school = $user->schools()->first();
        
        if (!$this->school) {
            session()->flash('error', 'No tienes ningún colegio asignado.');
            return redirect()->route('dashboard');
        }

        // Also set the school in session for consistency
        session(['selected_school_id' => $this->school->id]);
        
        $this->loadSchoolData();
    }

    public function loadSchoolData()
    {
        $this->name = $this->school->name;
        $this->nit = $this->school->nit;
        $this->dane_code = $this->school->dane_code;
        $this->municipality = $this->school->municipality;
        $this->rector_name = $this->school->rector_name;
        $this->rector_document = $this->school->rector_document;
        $this->pagador_name = $this->school->pagador_name;
        $this->address = $this->school->address;
        $this->email = $this->school->email;
        $this->phone = $this->school->phone;
        $this->website = $this->school->website;
        $this->budget_agreement_number = $this->school->budget_agreement_number;
        $this->budget_approval_date = $this->school->budget_approval_date;
        $this->current_validity = $this->school->current_validity;
        $this->contracting_manual_approval_number = $this->school->contracting_manual_approval_number;
        $this->contracting_manual_approval_date = $this->school->contracting_manual_approval_date;
        $this->dian_resolution_1 = $this->school->dian_resolution_1;
        $this->dian_resolution_2 = $this->school->dian_resolution_2;
        $this->dian_range_1 = $this->school->dian_range_1;
        $this->dian_range_2 = $this->school->dian_range_2;
        $this->dian_expiration_1 = $this->school->dian_expiration_1;
        $this->dian_expiration_2 = $this->school->dian_expiration_2;
    }

    public function toggleEdit()
    {
        // Check if user has permission to edit
        if (!auth()->user()->can('school_info.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar la información del colegio.', type: 'error');
            return;
        }

        if ($this->isEditing) {
            $this->loadSchoolData(); // Reset to original data
        }
        $this->isEditing = !$this->isEditing;
    }

    public function updateSchool()
    {
        // Double-check permission
        if (!auth()->user()->can('school_info.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar la información del colegio.', type: 'error');
            return;
        }

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'nit' => 'required|string|max:50',
            'dane_code' => 'required|string|max:50',
            'municipality' => 'required|string|max:255',
            'rector_name' => 'required|string|max:255',
            'rector_document' => 'required|string|max:50',
            'pagador_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'website' => 'nullable|string|max:255',
            'budget_agreement_number' => 'required|string|max:50',
            'budget_approval_date' => 'required|date',
            'current_validity' => 'required|integer|min:2000|max:2100',
            'contracting_manual_approval_number' => 'nullable|string|max:50',
            'contracting_manual_approval_date' => 'nullable|date',
            'dian_resolution_1' => 'required|string|max:50',
            'dian_resolution_2' => 'nullable|string|max:50',
            'dian_range_1' => 'required|string|max:50',
            'dian_range_2' => 'nullable|string|max:50',
            'dian_expiration_1' => 'required|date',
            'dian_expiration_2' => 'nullable|date',
        ]);

        // Convert empty strings to null for optional fields
        $validated['website'] = $validated['website'] ?: null;
        $validated['contracting_manual_approval_number'] = $validated['contracting_manual_approval_number'] ?: null;
        $validated['contracting_manual_approval_date'] = $validated['contracting_manual_approval_date'] ?: null;
        $validated['dian_resolution_2'] = $validated['dian_resolution_2'] ?: null;
        $validated['dian_range_2'] = $validated['dian_range_2'] ?: null;
        $validated['dian_expiration_2'] = $validated['dian_expiration_2'] ?: null;

        $this->school->update($validated);
        
        $this->dispatch('notify', message: 'Información del colegio actualizada exitosamente.', type: 'success');
        $this->isEditing = false;
        $this->school->refresh();
        $this->loadSchoolData();
    }

    public function render()
    {
        return view('livewire.school-info')->layout('layouts.app');
    }
}

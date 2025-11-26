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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function selectSchool($schoolId)
    {
        session(['selected_school_id' => $schoolId]);
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
            'dian_expiration_1', 'dian_expiration_2'
        ]);
    }

    public function createSchool()
    {
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

        $school = School::create($validated);
        
        session(['selected_school_id' => $school->id]);
        session()->flash('message', 'Colegio creado exitosamente.');
        
        $this->closeCreateModal();
        return redirect()->route('dashboard');
    }

    public function render()
    {
        $schools = School::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('nit', 'like', '%' . $this->search . '%')
                    ->orWhere('municipality', 'like', '%' . $this->search . '%');
            })
            ->paginate($this->perPage);

        return view('livewire.school-selection', [
            'schools' => $schools,
        ])->layout('layouts.school-select');
    }
}

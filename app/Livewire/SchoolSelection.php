<?php

namespace App\Livewire;

use App\Models\School;
use Livewire\Component;

class SchoolSelection extends Component
{
    public function selectSchool($schoolId)
    {
        session(['selected_school_id' => $schoolId]);
        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.school-selection', [
            'schools' => School::all(),
        ])->layout('layouts.app');
    }
}

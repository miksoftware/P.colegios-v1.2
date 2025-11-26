<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\School;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;

class UserManagement extends Component
{
    public $users;
    public $roles;
    public $schoolId;
    
    // Form fields
    public $name = '';
    public $surname = '';
    public $identification_type = '';
    public $identification_number = '';
    public $email = '';
    public $password = '';
    public $role = '';
    public $userId = null;
    
    public $isEditing = false;
    public $showModal = false;

    public function mount()
    {
        abort_if(!auth()->user()->can('gestionar usuarios'), 403, 'No tienes permisos para gestionar usuarios.');

        $this->schoolId = session('selected_school_id');
        
        if (!$this->schoolId && !auth()->user()->hasRole('Admin')) {
            return redirect()->route('school.select');
        }

        $this->loadData();
    }

    public function loadData()
    {
        $this->roles = Role::all();
        
        if ($this->schoolId) {
            $school = School::find($this->schoolId);
            $this->users = $school ? $school->users()->with('roles')->get() : collect();
        } else {
            // Admin viewing all users or specific logic for no-school admin
            $this->users = User::with('roles')->get();
        }
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['name', 'surname', 'identification_type', 'identification_number', 'email', 'password', 'role', 'userId', 'isEditing']);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function editUser($id)
    {
        $this->resetValidation();
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->surname = $user->surname;
        $this->identification_type = $user->identification_type;
        $this->identification_number = $user->identification_number;
        $this->email = $user->email;
        // Password is not loaded for security
        $this->role = $user->roles->first()?->name ?? '';
        
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'identification_type' => 'required|string',
            'identification_number' => 'required|string|max:50',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->userId)],
            'role' => 'required|exists:roles,name',
        ];

        if (!$this->isEditing) {
            $rules['password'] = 'required|min:8';
        } else {
            $rules['password'] = 'nullable|min:8';
        }

        $validated = $this->validate($rules);

        if ($this->isEditing) {
            $user = User::findOrFail($this->userId);
            $updateData = [
                'name' => $this->name,
                'surname' => $this->surname,
                'identification_type' => $this->identification_type,
                'identification_number' => $this->identification_number,
                'email' => $this->email,
            ];
            
            if (!empty($this->password)) {
                $updateData['password'] = Hash::make($this->password);
            }
            
            $user->update($updateData);
            $user->syncRoles([$this->role]);
            
            // Update role in school_user pivot if school is selected
            if ($this->schoolId) {
                $user->schools()->updateExistingPivot($this->schoolId, ['role' => $this->role]);
            }

            $this->dispatch('notify', message: 'Usuario actualizado exitosamente.', type: 'success');
        } else {
            $user = User::create([
                'name' => $this->name,
                'surname' => $this->surname,
                'identification_type' => $this->identification_type,
                'identification_number' => $this->identification_number,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);
            
            $user->assignRole($this->role);
            
            // Attach to school with role
            if ($this->schoolId) {
                $user->schools()->attach($this->schoolId, ['role' => $this->role]);
            }

            $this->dispatch('notify', message: 'Usuario creado exitosamente.', type: 'success');
        }

        $this->closeModal();
        $this->loadData();
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            $this->dispatch('notify', message: 'No puedes eliminar tu propio usuario.', type: 'error');
            return;
        }

        // If in school context, detach. If user has no other schools, maybe delete?
        // For now, just detach from school if schoolId is set
        if ($this->schoolId) {
            $user->schools()->detach($this->schoolId);
            $this->dispatch('notify', message: 'Usuario eliminado del colegio.', type: 'success');
        } else {
            // Admin deleting globally? Or just detach?
            // Let's assume global delete for now if not in school context
            $user->delete();
            $this->dispatch('notify', message: 'Usuario eliminado del sistema.', type: 'success');
        }
        
        $this->loadData();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.user-management');
    }
}

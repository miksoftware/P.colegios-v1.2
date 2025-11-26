<?php

namespace App\Livewire;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Attributes\Layout;

class RoleManagement extends Component
{
    public $roles;
    public $permissions;
    public $name = '';
    public $selectedPermissions = [];
    public $roleId = null;
    public $isEditing = false;
    public $showModal = false;

    public function mount()
    {
        abort_if(!auth()->user()->can('gestionar roles'), 403, 'No tienes permisos para gestionar roles.');
        $this->loadData();
    }

    public function loadData()
    {
        $this->roles = Role::with('permissions')->get();
        $this->permissions = Permission::all();
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['name', 'selectedPermissions', 'roleId', 'isEditing']);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function editRole($id)
    {
        $this->resetValidation();
        $role = Role::findOrFail($id);
        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|unique:roles,name,' . $this->roleId,
            'selectedPermissions' => 'array'
        ]);

        if ($this->isEditing) {
            $role = Role::findOrFail($this->roleId);
            $role->update(['name' => $this->name]);
            $role->syncPermissions($this->selectedPermissions);
            $this->dispatch('notify', message: 'Rol actualizado exitosamente.', type: 'success');
        } else {
            $role = Role::create(['name' => $this->name]);
            $role->syncPermissions($this->selectedPermissions);
            $this->dispatch('notify', message: 'Rol creado exitosamente.', type: 'success');
        }

        $this->closeModal();
        $this->loadData();
    }

    public function deleteRole($id)
    {
        $role = Role::findOrFail($id);
        
        if ($role->name === 'Admin') {
            $this->dispatch('notify', message: 'No puedes eliminar el rol de Admin.', type: 'error');
            return;
        }

        $role->delete();
        $this->loadData();
        $this->dispatch('notify', message: 'Rol eliminado exitosamente.', type: 'success');
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.role-management');
    }
}

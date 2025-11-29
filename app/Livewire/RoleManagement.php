<?php

namespace App\Livewire;

use App\Models\Module;
use App\Models\Permission;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Livewire\Attributes\Layout;

class RoleManagement extends Component
{
    public $roles;
    public $modules;
    public $name = '';
    public $selectedPermissions = [];
    public $roleId = null;
    public $isEditing = false;
    public $showModal = false;

    public function mount()
    {
        abort_if(!auth()->user()->can('roles.view'), 403, 'No tienes permisos para ver roles.');
        $this->loadData();
    }

    public function loadData()
    {
        $this->roles = Role::with('permissions')->get();
        // Load modules with their permissions for organized display
        $this->modules = Module::with('permissions')->orderBy('order')->get();
    }

    public function openModal()
    {
        if (!auth()->user()->can('roles.create')) {
            $this->dispatch('notify', message: 'No tienes permiso para crear roles.', type: 'error');
            return;
        }

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
        if (!auth()->user()->can('roles.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar roles.', type: 'error');
            return;
        }

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
        $permission = $this->isEditing ? 'roles.edit' : 'roles.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('notify', message: 'No tienes permiso para realizar esta acción.', type: 'error');
            return;
        }

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
            $role = Role::create(['name' => $this->name, 'guard_name' => 'web']);
            $role->syncPermissions($this->selectedPermissions);
            $this->dispatch('notify', message: 'Rol creado exitosamente.', type: 'success');
        }

        $this->closeModal();
        $this->loadData();
    }

    public function deleteRole($id)
    {
        if (!auth()->user()->can('roles.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para eliminar roles.', type: 'error');
            return;
        }

        $role = Role::findOrFail($id);
        
        if ($role->name === 'Admin') {
            $this->dispatch('notify', message: 'No puedes eliminar el rol de Admin.', type: 'error');
            return;
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            $this->dispatch('notify', message: 'No puedes eliminar un rol que tiene usuarios asignados.', type: 'error');
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

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Códigos Presupuestales de Gasto</h1>
                <p class="text-gray-500 mt-1">Catálogo de códigos para clasificación de gastos</p>
            </div>
            @can('expense_codes.create')
            <button wire:click="openCreateModal" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo Código
            </button>
            @endcan
        </div>

        {{-- Resumen --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Códigos</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $this->totals['total'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-xl">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Códigos Activos</p>
                        <p class="text-2xl font-bold text-green-600">{{ $this->totals['active'] }}</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-xl">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border-gray-300" placeholder="Código o nombre...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select wire:model.live="filterStatus" class="w-full rounded-xl border-gray-300">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button wire:click="clearFilters" class="text-sm text-gray-500 hover:text-gray-700">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Limpiar filtros
                </button>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($this->expenseCodes as $expenseCode)
                    <tr class="hover:bg-gray-50" wire:key="expense-code-{{ $expenseCode->id }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-sm font-medium text-blue-600">{{ $expenseCode->code }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $expenseCode->name }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <button 
                                wire:click="toggleStatus({{ $expenseCode->id }})"
                                @cannot('expense_codes.edit') disabled @endcannot
                                class="px-2 py-1 text-xs font-medium rounded-full transition-colors {{ $expenseCode->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }} @cannot('expense_codes.edit') cursor-not-allowed opacity-50 @endcannot"
                            >
                                {{ $expenseCode->is_active ? 'Activo' : 'Inactivo' }}
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                @can('expense_codes.edit')
                                <button wire:click="edit({{ $expenseCode->id }})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @endcan
                                @can('expense_codes.delete')
                                <button wire:click="confirmDelete({{ $expenseCode->id }})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            <p class="mt-2">No se encontraron códigos</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if($this->expenseCodes->hasPages())
            <div class="px-6 py-4 border-t">
                {{ $this->expenseCodes->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- Modal Crear/Editar --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="closeModal"></div>
            
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl transform transition-all sm:my-8 w-full max-w-lg">
                <form wire:submit="save">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ $isEditing ? 'Editar Código' : 'Nuevo Código Presupuestal' }}
                        </h3>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="code" class="w-full rounded-xl border-gray-300 font-mono" placeholder="2.1.2.01.01.003.01.06">
                            @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                            <textarea wire:model="name" rows="3" class="w-full rounded-xl border-gray-300" placeholder="Descripción del código presupuestal..."></textarea>
                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model="is_active" id="is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="is_active" class="text-sm text-gray-700">Código activo</label>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors">
                            {{ $isEditing ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Confirmar Eliminación --}}
    @if($showDeleteModal && $itemToDelete)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="closeDeleteModal"></div>
            
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl transform transition-all sm:my-8 w-full max-w-md">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 text-center mb-2">Eliminar Código</h3>
                    <p class="text-sm text-gray-500 text-center mb-4">
                        ¿Estás seguro de eliminar el código <span class="font-mono font-medium text-gray-700">{{ $itemToDelete->code }}</span>?
                    </p>
                    <p class="text-xs text-gray-400 text-center">Esta acción no se puede deshacer.</p>
                </div>
                
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                    <button type="button" wire:click="closeDeleteModal" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button type="button" wire:click="delete" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
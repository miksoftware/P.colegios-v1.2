<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Cuentas Contables (Inventarios)</h1>
        
        @can('inventory_accounting_accounts.create')
            <button 
                wire:click="openCreateModal"
                class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-sm"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Cuenta
            </button>
        @endcan
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input 
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Código o nombre..."
                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Código</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Años Depreciación</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($this->accounts as $account)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $account->code }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $account->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center">
                                {{ $account->depreciation_years }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $account->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $account->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @can('inventory_accounting_accounts.edit')
                                    <button 
                                        wire:click="edit({{ $account->id }})"
                                        class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors"
                                    >
                                        Editar
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay cuentas contables</h3>
                                <p class="mt-1 text-sm text-gray-500">Comienza creando una nueva cuenta contable para el inventario.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->accounts->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $this->accounts->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit="save">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="mb-5 border-b pb-4">
                                <h3 class="text-xl leading-6 font-bold text-gray-900" id="modal-title">
                                    {{ $isEditing ? 'Editar Cuenta Contable' : 'Nueva Cuenta Contable' }}
                                </h3>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                                    <input type="text" wire:model="code" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('code') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la cuenta</label>
                                    <input type="text" wire:model="name" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Años de Depreciación</label>
                                    <input type="number" wire:model="depreciation_years" min="0" max="100" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('depreciation_years') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="flex items-center mt-4">
                                        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span class="ml-2 text-sm text-gray-600">Cuenta Activa</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-4 sm:px-6 sm:flex sm:flex-row-reverse border-t">
                            <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Guardar
                            </button>
                            <button type="button" wire:click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

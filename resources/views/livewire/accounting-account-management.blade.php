<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Plan de Cuentas</h1>
                <p class="text-gray-500 mt-1">Gestiona la estructura contable del colegio</p>
            </div>
            <div class="flex items-center gap-3">
                <button 
                    wire:click="collapseAll"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                    Colapsar
                </button>
                <button 
                    wire:click="expandAll"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                    Expandir
                </button>
                @can('accounting_accounts.create')
                <button 
                    wire:click="openCreateModal"
                    class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 transition-all transform hover:-translate-y-0.5"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva Clase
                </button>
                @endcan
            </div>
        </div>

        <!-- Leyenda de niveles -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <div class="flex flex-wrap items-center gap-4 text-sm">
                <span class="text-gray-500 font-medium">Niveles:</span>
                @foreach(\App\Models\AccountingAccount::LEVELS as $level => $name)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ \App\Models\AccountingAccount::LEVEL_COLORS[$level] }}">
                        {{ $name }}
                    </span>
                @endforeach
            </div>
        </div>

        <!-- Árbol de cuentas -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            @if($this->accounts->count() > 0)
                <div class="divide-y divide-gray-100">
                    @foreach($this->accounts as $account)
                        @include('livewire.partials.accounting-account-row', ['account' => $account, 'depth' => 0])
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">No hay cuentas contables</h3>
                    <p class="text-gray-500 mt-1 mb-4">Comienza creando la primera clase contable.</p>
                    @can('accounting_accounts.create')
                    <button 
                        wire:click="openCreateModal"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Crear Primera Clase
                    </button>
                    @endcan
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Crear/Editar -->
    <div 
        x-data="{ show: @entangle('showModal') }"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div 
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500/75 transition-opacity"
                @click="show = false"
            ></div>

            <div 
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg w-full"
            >
                <div class="bg-white px-6 py-5">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                                {{ $isEditing ? 'Editar Cuenta' : 'Nueva Cuenta' }}
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ \App\Models\AccountingAccount::LEVEL_COLORS[$level] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ \App\Models\AccountingAccount::LEVELS[$level] ?? 'Desconocido' }}
                                </span>
                            </h3>
                            @if($parentName)
                                <p class="text-sm text-gray-500 mt-1">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    Padre: <span class="font-medium text-gray-700">{{ $parentName }}</span>
                                </p>
                            @endif
                        </div>
                        <button @click="show = false" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="space-y-5">
                        <!-- Código -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
                            <input 
                                type="text" 
                                wire:model="code" 
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-lg"
                                placeholder="Ej: 1105"
                            >
                            @error('code') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                            <input 
                                type="text" 
                                wire:model="name" 
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase"
                                placeholder="Ej: CAJA"
                            >
                            @error('name') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Descripción -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <textarea 
                                wire:model="description" 
                                rows="2"
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Descripción opcional..."
                            ></textarea>
                        </div>

                        <!-- Naturaleza y Opciones -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Naturaleza</label>
                                <select wire:model="nature" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="D">Débito</option>
                                    <option value="C">Crédito</option>
                                </select>
                            </div>
                            <div class="flex items-end pb-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        wire:model="allowsMovement"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                    <span class="text-sm text-gray-700">Permite movimientos</span>
                                </label>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    wire:model="isActive"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                <span class="text-sm text-gray-700">Cuenta activa</span>
                            </label>
                        </div>
                    </form>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button 
                        type="button"
                        @click="show = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button 
                        wire:click="save"
                        class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all"
                    >
                        <span wire:loading.remove wire:target="save">{{ $isEditing ? 'Actualizar' : 'Crear Cuenta' }}</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    @if($showDeleteModal && $accountToDelete)
    <div 
        x-data="{ show: true }"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
    >
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div 
                class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                wire:click="closeDeleteModal"
            ></div>

            <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg w-full">
                @if($deleteStep === 1)
                    <!-- Primera Confirmación -->
                    <div class="bg-white px-6 py-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900">¿Eliminar cuenta contable?</h3>
                                <div class="mt-3 space-y-3">
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $accountToDelete->level_color }}">
                                                {{ $accountToDelete->level_name }}
                                            </span>
                                            <span class="font-mono text-sm font-semibold text-gray-900">{{ $accountToDelete->code }}</span>
                                        </div>
                                        <p class="mt-2 text-sm font-medium text-gray-900">{{ $accountToDelete->name }}</p>
                                    </div>

                                    @if($childrenCount > 0)
                                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                                            <div class="flex items-start gap-3">
                                                <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                </svg>
                                                <div>
                                                    <p class="text-sm font-semibold text-amber-800">
                                                        Esta cuenta tiene {{ $childrenCount }} subcuenta(s)
                                                    </p>
                                                    <p class="text-sm text-amber-700 mt-1">
                                                        Se eliminarán todas las subcuentas de forma permanente.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-600">
                                            Esta cuenta no tiene subcuentas y se eliminará de forma permanente.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button 
                            wire:click="closeDeleteModal"
                            class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                        >
                            Cancelar
                        </button>
                        <button 
                            wire:click="{{ $childrenCount > 0 ? 'proceedToSecondConfirmation' : 'executeDelete' }}"
                            class="px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors"
                        >
                            {{ $childrenCount > 0 ? 'Continuar' : 'Eliminar cuenta' }}
                        </button>
                    </div>
                @else
                    <!-- Segunda Confirmación (solo si tiene hijos) -->
                    <div class="bg-white px-6 py-6">
                        <div class="text-center">
                            <div class="mx-auto w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Confirmar eliminación en cascada</h3>
                            <div class="mt-4 bg-red-50 border border-red-200 rounded-xl p-4 text-left">
                                <p class="text-sm text-red-800">
                                    <strong>¡Atención!</strong> Estás a punto de eliminar:
                                </p>
                                <ul class="mt-3 space-y-2 text-sm text-red-700">
                                    <li class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        <span class="font-mono font-semibold">{{ $accountToDelete->code }}</span> - {{ $accountToDelete->name }}
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                        <strong>{{ $childrenCount }} subcuenta(s)</strong> que dependen de esta cuenta
                                    </li>
                                </ul>
                                <p class="mt-4 text-sm font-semibold text-red-800">
                                    Total: {{ $childrenCount + 1 }} cuenta(s) serán eliminadas permanentemente.
                                </p>
                            </div>
                            <p class="mt-4 text-sm text-gray-600">
                                Esta acción <strong>no se puede deshacer</strong>. ¿Deseas continuar?
                            </p>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex justify-center gap-3">
                        <button 
                            wire:click="closeDeleteModal"
                            class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                        >
                            No, cancelar
                        </button>
                        <button 
                            wire:click="executeDelete"
                            class="px-5 py-2.5 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700 shadow-lg shadow-red-500/30 transition-all"
                        >
                            <span wire:loading.remove wire:target="executeDelete">Sí, eliminar todo</span>
                            <span wire:loading wire:target="executeDelete">Eliminando...</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

<div class="min-h-screen bg-gradient-to-br from-blue-600 via-blue-400 to-white flex items-center justify-center p-4 sm:p-6 lg:p-8 relative overflow-hidden">
    <!-- Decorative Circles -->
    <div class="absolute top-0 left-0 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-teal-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
    <div class="absolute bottom-0 left-1/2 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
    
    <!-- School Selection Container -->
    <div class="w-full max-w-7xl relative z-10">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-2xl shadow-blue-500/30 mb-6 transform hover:scale-105 transition-transform duration-300">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-12 h-12 object-contain">
            </div>
            <h1 class="text-4xl sm:text-5xl font-bold text-white mb-3 drop-shadow-lg">
                Selecciona un Colegio
            </h1>
            <p class="text-blue-100 text-lg sm:text-xl">Elige la institución que deseas administrar</p>
        </div>

        <!-- Search Bar -->
        <div class="mb-6">
            <div class="max-w-2xl mx-auto">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por nombre, NIT o municipio..."
                        class="w-full pl-11 pr-4 py-4 bg-white/90 backdrop-blur-sm border border-white/20 rounded-2xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-white/50 focus:bg-white transition-all shadow-xl"
                    >
                </div>
            </div>
        </div>

        <!-- Schools Grid -->
        @if($schools->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach($schools as $index => $school)
                    <div
                        class="group bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl shadow-gray-200/50 p-6 border border-white/20 hover:shadow-2xl hover:shadow-blue-500/20 hover:-translate-y-1 transition-all duration-300 overflow-hidden relative school-card cursor-pointer"
                        style="animation-delay: {{ $index * 0.05 }}s"
                        wire:click="selectSchool({{ $school->id }})"
                    >
                        <!-- Gradient Overlay on Hover -->
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-600/5 to-teal-600/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-2xl"></div>
                        
                        <div class="relative">
                            <!-- Icon -->
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-600 to-blue-500 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300 shadow-lg shadow-blue-500/30">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>

                            <!-- School Name -->
                            <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors">
                                {{ $school->name }}
                            </h3>

                            <!-- School Info -->
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span class="font-medium">NIT:</span>
                                    <span>{{ $school->nit }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span class="font-medium">Municipio:</span>
                                    <span>{{ $school->municipality }}</span>
                                </div>
                            </div>

                            <!-- Arrow Icon and Actions -->
                            <div class="mt-4 flex items-center justify-between">
                                @if(auth()->user()->hasRole('Admin'))
                                    <div class="flex gap-2" @click.stop>
                                        <button 
                                            wire:click="editSchool({{ $school->id }})"
                                            class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors z-20 relative"
                                            title="Editar Colegio"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button 
                                            wire:confirm="¿Estás seguro de eliminar este colegio? Esta acción no se puede deshacer."
                                            wire:click="deleteSchool({{ $school->id }})"
                                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors z-20 relative"
                                            title="Eliminar Colegio"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                @else
                                    <div></div>
                                @endif

                                <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-500 rounded-full flex items-center justify-center group-hover:translate-x-1 transition-transform duration-300">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="flex justify-center">
                {{ $schools->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6 backdrop-blur-sm">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-white mb-2">No se encontraron colegios</h3>
                <p class="text-blue-100">Intenta con otra búsqueda o crea un nuevo colegio</p>
            </div>
        @endif

        <!-- Floating Action Button for Create -->
        @if(auth()->user()->hasRole('Admin'))
            <button 
                wire:click="openCreateModal"
                class="fixed bottom-8 right-8 w-16 h-16 bg-gradient-to-r from-blue-600 to-teal-500 text-white rounded-full shadow-2xl flex items-center justify-center hover:scale-110 transition-transform duration-300 z-50 group"
            >
                <svg class="w-8 h-8 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
        @endif

        <!-- Footer -->
        <p class="text-center text-sm text-white/80 mt-12 drop-shadow">
            © 2025 Sistema de Presupuesto Escolar
        </p>
    </div>

    <!-- Create/Edit School Modal -->
    <div 
        x-data="{ show: @entangle('showCreateModal') }"
        x-show="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div 
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity" 
                aria-hidden="true"
                @click="show = false"
            >
                <div class="absolute inset-0 bg-gray-900 opacity-75 backdrop-blur-sm"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div 
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full"
            >
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button 
                        @click="show = false" 
                        class="text-gray-400 hover:text-gray-500 focus:outline-none"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-2xl leading-6 font-bold text-gray-900 mb-8" id="modal-title">
                                {{ $isEditing ? 'Editar Colegio' : 'Registrar Nuevo Colegio' }}
                            </h3>
                            
                            <form wire:submit="saveSchool" class="space-y-8">
                                <!-- Basic Information -->
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-4">Información Básica</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Nombre del Colegio *</label>
                                            <input type="text" wire:model="name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">NIT *</label>
                                            <input type="text" wire:model="nit" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('nit') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Código DANE *</label>
                                            <input type="text" wire:model="dane_code" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('dane_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Municipio *</label>
                                            <input type="text" wire:model="municipality" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('municipality') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Rector Information -->
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-4">Información del Rector</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Nombre del Rector *</label>
                                            <input type="text" wire:model="rector_name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('rector_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Documento del Rector *</label>
                                            <input type="text" wire:model="rector_document" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('rector_document') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Nombre del Pagador *</label>
                                            <input type="text" wire:model="pagador_name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('pagador_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Vigencia Actual *</label>
                                            <input type="number" wire:model="current_validity" required min="2000" max="2100" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('current_validity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact Information -->
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-4">Información de Contacto</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Dirección *</label>
                                            <input type="text" wire:model="address" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Email *</label>
                                            <input type="email" wire:model="email" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Teléfono *</label>
                                            <input type="text" wire:model="phone" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Sitio Web</label>
                                            <input type="text" wire:model="website" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>
                                </div>

                                <!-- Budget Information -->
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-4">Información Presupuestal</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">N° Acuerdo Presupuesto *</label>
                                            <input type="text" wire:model="budget_agreement_number" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('budget_agreement_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Fecha Aprobación Presupuesto *</label>
                                            <input type="date" wire:model="budget_approval_date" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('budget_approval_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">N° Aprob. Manual Contratación</label>
                                            <input type="text" wire:model="contracting_manual_approval_number" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Fecha Aprob. Manual Contratación</label>
                                            <input type="date" wire:model="contracting_manual_approval_date" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>
                                </div>

                                <!-- DIAN Information -->
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-4">Información DIAN</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Resolución DIAN 1 *</label>
                                            <input type="text" wire:model="dian_resolution_1" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('dian_resolution_1') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Rango Resolución 1 *</label>
                                            <input type="text" wire:model="dian_range_1" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('dian_range_1') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Vencimiento Resolución 1 *</label>
                                            <input type="date" wire:model="dian_expiration_1" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            @error('dian_expiration_1') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Resolución DIAN 2</label>
                                            <input type="text" wire:model="dian_resolution_2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Rango Resolución 2</label>
                                            <input type="text" wire:model="dian_range_2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-900 mb-2">Vencimiento Resolución 2</label>
                                            <input type="date" wire:model="dian_expiration_2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200">
                                    <button type="button" wire:click="$set('showCreateModal', false)" class="px-6 py-3 text-gray-700 hover:bg-gray-100 rounded-xl font-semibold transition-colors">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-teal-600 hover:from-blue-700 hover:to-teal-700 text-white rounded-xl font-semibold shadow-lg shadow-blue-500/30 transition-all">
                                        {{ $isEditing ? 'Actualizar' : 'Crear' }} Colegio
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        document.addEventListener('livewire:init', () => {
            // Add entrance animation to cards
            const cards = document.querySelectorAll('.school-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });
    </script>

    <!-- Loading Overlay for School Selection -->
    <div 
        wire:loading.flex
        wire:target="selectSchool"
        class="fixed inset-0 z-[9999] items-center justify-center bg-black/50 backdrop-blur-sm"
        style="display: none;"
    >
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="flex flex-col items-center">
                <div class="relative w-16 h-16 mb-4">
                    <div class="absolute inset-0 border-4 border-blue-200 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-transparent border-t-blue-600 rounded-full animate-spin"></div>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Cargando...</h3>
            </div>
        </div>
    </div>

    <!-- Loading Overlay for School Creation/Update -->
    <div 
        wire:loading.flex
        wire:target="saveSchool"
        class="fixed inset-0 z-[9999] items-center justify-center bg-black/50 backdrop-blur-sm"
        style="display: none;"
    >
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="flex flex-col items-center">
                <div class="relative w-16 h-16 mb-4">
                    <div class="absolute inset-0 border-4 border-blue-200 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-transparent border-t-blue-600 rounded-full animate-spin"></div>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Procesando...</h3>
            </div>
        </div>
    </div>

    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        .school-modal {
            animation: slideIn 0.3s ease-out;
        }
    </style>
</div>

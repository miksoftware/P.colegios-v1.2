<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Mi Colegio</h2>
                <p class="text-gray-600 mt-1">Información de tu colegio asignado</p>
            </div>
            
            @can('school_info.edit')
                <button 
                    wire:click="toggleEdit" 
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 transition-all"
                >
                    @if($isEditing)
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Cancelar Edición
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar Información
                    @endif
                </button>
            @endcan
        </div>

        <!-- School Name Banner -->
        @if($school)
        <div class="bg-gradient-to-r from-blue-600 to-teal-600 rounded-2xl shadow-lg p-6 mb-6 text-white">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 rounded-xl p-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-2xl font-bold">{{ $school->name }}</h3>
                    <p class="text-white/80">NIT: {{ $school->nit }} | DANE: {{ $school->dane_code }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Success Message -->
        @if (session()->has('message'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-green-800 font-medium">{{ session('message') }}</span>
            </div>
        @endif

        <form wire:submit.prevent="updateSchool">
            <!-- Basic Information -->
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-8 border border-gray-100 mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Información Básica
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Nombre del Colegio *</label>
                        @if($isEditing)
                            <input type="text" wire:model="name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('name') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->name }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">NIT *</label>
                        @if($isEditing)
                            <input type="text" wire:model="nit" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('nit') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->nit }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Código DANE *</label>
                        @if($isEditing)
                            <input type="text" wire:model="dane_code" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('dane_code') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->dane_code }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Municipio *</label>
                        @if($isEditing)
                            <input type="text" wire:model="municipality" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('municipality') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->municipality }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Rector Information -->
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-8 border border-gray-100 mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Información del Rector
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Nombre del Rector *</label>
                        @if($isEditing)
                            <input type="text" wire:model="rector_name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('rector_name') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->rector_name }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Documento del Rector *</label>
                        @if($isEditing)
                            <input type="text" wire:model="rector_document" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('rector_document') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->rector_document }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Nombre del Pagador *</label>
                        @if($isEditing)
                            <input type="text" wire:model="pagador_name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('pagador_name') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->pagador_name }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Vigencia Actual *</label>
                        @if($isEditing)
                            <input type="number" wire:model="current_validity" required min="2000" max="2100" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('current_validity') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->current_validity }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-8 border border-gray-100 mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Información de Contacto
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Dirección *</label>
                        @if($isEditing)
                            <input type="text" wire:model="address" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('address') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->address }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Email *</label>
                        @if($isEditing)
                            <input type="email" wire:model="email" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('email') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->email }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Teléfono *</label>
                        @if($isEditing)
                            <input type="text" wire:model="phone" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('phone') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->phone }}</p>
                        @endif
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Sitio Web</label>
                        @if($isEditing)
                            <input type="text" wire:model="website" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->website ?: 'No especificado' }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Budget Information -->
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-8 border border-gray-100 mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Información Presupuestal
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">N° Acuerdo Presupuesto *</label>
                        @if($isEditing)
                            <input type="text" wire:model="budget_agreement_number" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('budget_agreement_number') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->budget_agreement_number }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Fecha Aprobación Presupuesto *</label>
                        @if($isEditing)
                            <input type="date" wire:model="budget_approval_date" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('budget_approval_date') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->budget_approval_date ? \Carbon\Carbon::parse($school->budget_approval_date)->format('d/m/Y') : 'No especificado' }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">N° Aprob. Manual Contratación</label>
                        @if($isEditing)
                            <input type="text" wire:model="contracting_manual_approval_number" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->contracting_manual_approval_number ?: 'No especificado' }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Fecha Aprob. Manual Contratación</label>
                        @if($isEditing)
                            <input type="date" wire:model="contracting_manual_approval_date" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->contracting_manual_approval_date ? \Carbon\Carbon::parse($school->contracting_manual_approval_date)->format('d/m/Y') : 'No especificado' }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- DIAN Information -->
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-8 border border-gray-100 mb-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Información DIAN
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Resolution 1 -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Resolución DIAN 1 *</label>
                        @if($isEditing)
                            <input type="text" wire:model="dian_resolution_1" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('dian_resolution_1') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->dian_resolution_1 }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Rango Resolución 1 *</label>
                        @if($isEditing)
                            <input type="text" wire:model="dian_range_1" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('dian_range_1') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->dian_range_1 }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Vencimiento Resolución 1 *</label>
                        @if($isEditing)
                            <input type="date" wire:model="dian_expiration_1" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('dian_expiration_1') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->dian_expiration_1 ? \Carbon\Carbon::parse($school->dian_expiration_1)->format('d/m/Y') : 'No especificado' }}</p>
                        @endif
                    </div>
                    
                    <!-- Resolution 2 -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Resolución DIAN 2</label>
                        @if($isEditing)
                            <input type="text" wire:model="dian_resolution_2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->dian_resolution_2 ?: 'No especificado' }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Rango Resolución 2</label>
                        @if($isEditing)
                            <input type="text" wire:model="dian_range_2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->dian_range_2 ?: 'No especificado' }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">Vencimiento Resolución 2</label>
                        @if($isEditing)
                            <input type="date" wire:model="dian_expiration_2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @else
                            <p class="text-gray-900 px-4 py-3 bg-gray-50 rounded-xl">{{ $school->dian_expiration_2 ? \Carbon\Carbon::parse($school->dian_expiration_2)->format('d/m/Y') : 'No especificado' }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Save Button (only visible when editing) -->
            @if($isEditing)
                <div class="flex items-center justify-end gap-4">
                    <button 
                        type="button" 
                        wire:click="toggleEdit" 
                        class="px-6 py-3 text-gray-700 hover:bg-gray-100 rounded-xl font-semibold transition-colors"
                    >
                        Cancelar
                    </button>
                    <button 
                        type="submit" 
                        class="px-8 py-3 bg-gradient-to-r from-blue-600 to-teal-600 hover:from-blue-700 hover:to-teal-700 text-white rounded-xl font-semibold shadow-lg shadow-blue-500/30 transition-all"
                    >
                        Guardar Cambios
                    </button>
                </div>
            @endif
        </form>

        <!-- Loading Overlay for School Update -->
        <div 
            wire:loading.flex
            wire:target="updateSchool"
            class="fixed inset-0 z-[9999] items-center justify-center bg-black/50 backdrop-blur-sm"
            style="display: none;"
        >
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <div class="flex flex-col items-center">
                    <div class="relative w-16 h-16 mb-4">
                        <div class="absolute inset-0 border-4 border-blue-200 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-transparent border-t-blue-600 rounded-full animate-spin"></div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Actualizando...</h3>
                </div>
            </div>
        </div>
    </div>
</div>

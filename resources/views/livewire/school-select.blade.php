<div>
    <style>
        [x-cloak] { display: none !important; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        /* Animación suave para la entrada de tarjetas */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-card { animation: fadeInUp 0.4s ease-out forwards; opacity: 0; }
    </style>

    <div
        x-data="{ show: false }"
        x-on:open-school-modal.window="show = true"
        x-on:keydown.escape.window="show = false"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-700/60 backdrop-blur-sm transition-opacity"
            @click="show = false"
        ></div>

        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative transform overflow-hidden rounded-2xl bg-gray-50 text-left shadow-2xl transition-all sm:my-8 w-full max-w-6xl border border-gray-100"
            >
                <div class="relative bg-white px-8 py-8 border-b border-gray-100">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Mis Colegios</h2>
                            <p class="mt-2 text-gray-500">Selecciona la institución para gestionar.</p>
                        </div>
                        <button @click="show = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-100 transition-colors">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="mt-8 relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-6 w-6 text-gray-400 group-focus-within:text-indigo-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input 
                            wire:model.live.debounce.300ms="search"
                            type="text" 
                            class="block w-full pl-[50px] pr-4 py-4 bg-gray-50 border-0 text-gray-900 rounded-xl ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-500 focus:bg-white text-lg transition-all shadow-sm group-hover:shadow-md"
                            placeholder="Buscar por nombre, NIT, código DANE..."
                            autofocus
                        >
                    </div>
                </div>

                <div class="px-8 py-8 bg-gray-50 min-h-[400px] max-h-[60vh] overflow-y-auto">
                    @if($schools->count() > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($schools as $index => $school)
                                <div 
                                    wire:click="selectSchool({{ $school->id }})"
                                    class="animate-card group relative bg-white rounded-2xl p-6 cursor-pointer border border-gray-100 shadow-sm hover:shadow-xl hover:border-indigo-200 hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between h-full"
                                    style="animation-delay: {{ $index * 50 }}ms;"
                                >
                                    <div>
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="h-14 w-14 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xl shadow-inner group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300">
                                                {{ substr($school->name, 0, 1) }}
                                            </div>
                                            @if(auth()->user()->hasRole('Admin'))
                                                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200 scale-90 group-hover:scale-100" @click.stop>
                                                    <button wire:click="editSchool({{ $school->id }})" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                                    <button wire:click="deleteSchool({{ $school->id }})" wire:confirm="¿Eliminar?" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                                </div>
                                            @endif
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900 leading-snug mb-3 group-hover:text-indigo-600 transition-colors line-clamp-2">{{ $school->name }}</h3>
                                        <div class="space-y-2.5 text-sm text-gray-500">
                                            <div class="flex items-center"><span class="font-medium text-gray-700 mr-1">NIT:</span> {{ $school->nit }}</div>
                                            <div class="flex items-center">{{ $school->municipality }}</div>
                                        </div>
                                    </div>
                                    <div class="mt-6 pt-4 border-t border-gray-50 flex justify-end items-center">
                                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 opacity-0 group-hover:opacity-100 transition-all transform translate-x-4 group-hover:translate-x-0 flex items-center">
                                            Entrar <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-8">{{ $schools->links() }}</div>
                    @else
                        <div class="flex flex-col items-center justify-center py-16 text-center">
                            <h3 class="text-lg font-semibold text-gray-900">No encontramos resultados</h3>
                            <p class="text-gray-500 mt-1">Intenta otra búsqueda o crea un nuevo colegio.</p>
                        </div>
                    @endif
                </div>

                @if(auth()->user()->hasRole('Admin'))
                    <div class="bg-gray-50 px-8 py-5 border-t border-gray-200 flex justify-end">
                        <button wire:click="openCreateModal" class="inline-flex items-center px-6 py-3 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-all shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Registrar Nuevo Colegio
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div
        x-data="{ show: @entangle('showCreateModal') }"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-[70] overflow-y-auto" 
        aria-labelledby="modal-form"
        role="dialog"
        aria-modal="true"
    >
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
            @click="show = false"
        ></div>

        <div class="flex min-h-screen items-center justify-center p-4">
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col max-h-[90vh] overflow-hidden relative z-10"
            >
                <div class="px-8 py-5 border-b border-gray-100 flex justify-between items-center bg-white sticky top-0 z-20">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">{{ $isEditing ? 'Editar Institución' : 'Registrar Nuevo Colegio' }}</h3>
                        <p class="text-sm text-gray-500">Diligencia la información completa de la institución.</p>
                    </div>
                    <button @click="show = false" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-8 bg-gray-50 custom-scrollbar">
                    <form wire:submit="saveSchool" class="space-y-8">
                        
                        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                            <h4 class="text-sm font-bold text-indigo-600 uppercase tracking-wider mb-6 border-b pb-2">Información Básica</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Colegio *</label>
                                    <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase transition-all placeholder-gray-400" placeholder="Ej: COLEGIO SAN JUAN">
                                    @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">NIT *</label>
                                    <input type="text" wire:model="nit" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 placeholder-gray-400" placeholder="800.000.000-1">
                                    @error('nit') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Código DANE *</label>
                                    <input type="text" wire:model="dane_code" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('dane_code') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Municipio *</label>
                                    <input type="text" wire:model="municipality" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase">
                                    @error('municipality') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                            <h4 class="text-sm font-bold text-indigo-600 uppercase tracking-wider mb-6 border-b pb-2">Información del Rector</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Rector *</label>
                                    <input type="text" wire:model="rector_name" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase">
                                    @error('rector_name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Documento del Rector *</label>
                                    <input type="text" wire:model="rector_document" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('rector_document') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Pagador</label>
                                    <input type="text" wire:model="pagador_name" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia Actual *</label>
                                    <input type="number" wire:model="current_validity" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ date('Y') }}">
                                    @error('current_validity') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                            <h4 class="text-sm font-bold text-indigo-600 uppercase tracking-wider mb-6 border-b pb-2">Información de Contacto</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección Física *</label>
                                    <input type="text" wire:model="address" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 uppercase">
                                    @error('address') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico *</label>
                                    <input type="email" wire:model="email" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono *</label>
                                    <input type="text" wire:model="phone" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('phone') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sitio Web</label>
                                    <input type="url" wire:model="website" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="https://...">
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                            <h4 class="text-sm font-bold text-indigo-600 uppercase tracking-wider mb-6 border-b pb-2">Información Presupuestal</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">N° Acuerdo Presupuesto *</label>
                                    <input type="text" wire:model="budget_agreement_number" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('budget_agreement_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Aprobación Presupuesto *</label>
                                    <input type="date" wire:model="budget_approval_date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('budget_approval_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">N° Aprob. Manual Contratación</label>
                                    <input type="text" wire:model="contracting_manual_approval_number" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Aprob. Manual Contratación</label>
                                    <input type="date" wire:model="contracting_manual_approval_date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                            <h4 class="text-sm font-bold text-indigo-600 uppercase tracking-wider mb-6 border-b pb-2">Información DIAN</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Resolución DIAN 1 *</label>
                                    <input type="text" wire:model="dian_resolution_1" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('dian_resolution_1') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Rango Resolución 1 *</label>
                                    <input type="text" wire:model="dian_range_1" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('dian_range_1') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Vencimiento Resolución 1 *</label>
                                    <input type="date" wire:model="dian_expiration_1" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('dian_expiration_1') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="md:col-span-2 mt-4 pt-4 border-t border-dashed border-gray-200">
                                    <p class="text-xs text-gray-400 font-semibold uppercase mb-4">Resolución Opcional</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Resolución DIAN 2</label>
                                    <input type="text" wire:model="dian_resolution_2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Rango Resolución 2</label>
                                    <input type="text" wire:model="dian_range_2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Vencimiento Resolución 2</label>
                                    <input type="date" wire:model="dian_expiration_2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 sticky bottom-0 bg-white py-4 -mb-4 z-10">
                            <button type="button" @click="show = false" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" class="px-6 py-2.5 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all flex items-center">
                                <span wire:loading.remove wire:target="saveSchool">{{ $isEditing ? 'Actualizar Datos' : 'Crear Colegio' }}</span>
                                <span wire:loading wire:target="saveSchool" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> 
                                    Guardando...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div 
        wire:loading.flex 
        wire:target="selectSchool" 
        class="fixed inset-0 z-[100] items-center justify-center bg-white transition-opacity duration-300"
    >
        <div class="flex flex-col items-center">
            <div class="relative w-16 h-16">
                <div class="absolute inset-0 border-4 border-gray-100 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-indigo-600 rounded-full border-t-transparent animate-spin"></div>
            </div>
            <h2 class="mt-4 text-xl font-bold text-gray-900">Cargando Institución</h2>
            <p class="text-gray-500 text-sm mt-1">Por favor espere un momento...</p>
        </div>
    </div>
</div>
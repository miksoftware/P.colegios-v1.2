<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Proveedores</h1>
                <p class="text-gray-500 mt-1">Gestiona los proveedores del colegio</p>
            </div>
            @can('suppliers.create')
            <button 
                wire:click="openCreateModal"
                class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 transition-all transform hover:-translate-y-0.5"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Proveedor
            </button>
            @endcan
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Búsqueda -->
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search"
                            class="w-full pl-10 rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Nombre, documento, correo..."
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Tipo de persona -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select wire:model.live="filterPersonType" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach(\App\Models\Supplier::PERSON_TYPES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Régimen -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Régimen</label>
                    <select wire:model.live="filterTaxRegime" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        @foreach(\App\Models\Supplier::TAX_REGIMES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select wire:model.live="filterStatus" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button 
                    wire:click="clearFilters"
                    class="text-sm text-gray-500 hover:text-gray-700 transition-colors"
                >
                    Limpiar filtros
                </button>
            </div>
        </div>

        <!-- Tabla de proveedores -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proveedor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Régimen</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($this->suppliers as $supplier)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold text-white text-sm
                                            {{ $supplier->person_type === 'juridica' ? 'bg-gradient-to-br from-purple-500 to-purple-600' : 'bg-gradient-to-br from-blue-500 to-teal-400' }}">
                                            {{ $supplier->person_type === 'juridica' ? 'E' : substr($supplier->first_name ?? $supplier->first_surname, 0, 1) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-gray-900 {{ !$supplier->is_active ? 'line-through text-gray-400' : '' }}">
                                                {{ $supplier->full_name }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $supplier->person_type_name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-mono text-gray-900">{{ $supplier->full_document }}</div>
                                    <div class="text-xs text-gray-500">{{ $supplier->document_type_name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $supplier->city }}</div>
                                    @if($supplier->phone || $supplier->mobile)
                                        <div class="text-xs text-gray-500">{{ $supplier->phone ?? $supplier->mobile }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-700">
                                        {{ $supplier->tax_regime_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button 
                                        wire:click="toggleStatus({{ $supplier->id }})"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-full transition-colors {{ $supplier->is_active ? 'bg-green-100 text-green-600 hover:bg-green-200' : 'bg-gray-100 text-gray-400 hover:bg-gray-200' }}"
                                        title="{{ $supplier->is_active ? 'Activo - Clic para desactivar' : 'Inactivo - Clic para activar' }}"
                                    >
                                        @if($supplier->is_active)
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        @endif
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @can('suppliers.edit')
                                        <button 
                                            wire:click="editSupplier({{ $supplier->id }})"
                                            class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Editar"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        @endcan
                                        @can('suppliers.delete')
                                        <button 
                                            wire:click="confirmDelete({{ $supplier->id }})"
                                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Eliminar"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-semibold text-gray-900">No hay proveedores</h3>
                                        <p class="text-gray-500 mt-1">
                                            @if($search || $filterStatus !== '' || $filterPersonType || $filterTaxRegime)
                                                No se encontraron proveedores con los filtros aplicados.
                                            @else
                                                Comienza agregando el primer proveedor.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($this->suppliers->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $this->suppliers->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Crear/Editar -->
    @if($showModal)
    <div 
        x-data="{ show: true }"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
    >
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="closeModal"></div>

            <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 w-full max-w-3xl">
                <!-- Header del modal -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white">
                                {{ $isEditing ? 'Editar Proveedor' : 'Nuevo Proveedor' }}
                            </h3>
                            <p class="text-blue-100 text-sm mt-1">
                                {{ $person_type === 'juridica' ? 'Persona Jurídica' : 'Persona Natural' }}
                            </p>
                        </div>
                        <button wire:click="closeModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Contenido del formulario con scroll -->
                <div class="px-6 py-5 max-h-[calc(100vh-250px)] overflow-y-auto">
                    <form wire:submit="save" class="space-y-6">
                        
                        <!-- Tipo de persona y documento -->
                        <div class="bg-gray-50 rounded-xl p-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                                </svg>
                                Identificación
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Tipo de persona -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Persona *</label>
                                    <select 
                                        wire:model.live="person_type" 
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        @foreach(\App\Models\Supplier::PERSON_TYPES as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Tipo de documento -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Documento *</label>
                                    <select 
                                        wire:model.live="document_type" 
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        @foreach(\App\Models\Supplier::DOCUMENT_TYPES as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Número de documento + DV -->
                                <div class="min-w-0">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ $document_type === 'NIT' ? 'NIT *' : 'Número Documento *' }}
                                    </label>
                                    <div class="flex gap-2">
                                        <input 
                                            type="text" 
                                            wire:model="document_number"
                                            wire:blur="calculateDv"
                                            class="flex-1 min-w-0 rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono"
                                            placeholder="{{ $document_type === 'NIT' ? '900123456' : '12345678' }}"
                                        >
                                        @if($document_type === 'NIT')
                                            <div class="flex items-center gap-1 flex-shrink-0">
                                                <span class="text-gray-500">-</span>
                                                <input 
                                                    type="text" 
                                                    wire:model="dv"
                                                    class="w-12 rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-center"
                                                    placeholder="DV"
                                                    maxlength="1"
                                                >
                                            </div>
                                        @endif
                                    </div>
                                    @error('document_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Datos de la persona/empresa -->
                        <div class="bg-gray-50 rounded-xl p-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ $person_type === 'juridica' ? 'Datos de la Empresa' : 'Datos Personales' }}
                            </h4>
                            
                            @if($person_type === 'juridica')
                                <!-- Solo razón social para persona jurídica -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
                                    <input 
                                        type="text" 
                                        wire:model="first_surname"
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase"
                                        placeholder="NOMBRE DE LA EMPRESA S.A.S."
                                    >
                                    @error('first_surname') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            @else
                                <!-- Campos para persona natural -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Primer Apellido *</label>
                                        <input 
                                            type="text" 
                                            wire:model="first_surname"
                                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase"
                                            placeholder="PÉREZ"
                                        >
                                        @error('first_surname') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Segundo Apellido</label>
                                        <input 
                                            type="text" 
                                            wire:model="second_surname"
                                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase"
                                            placeholder="GARCÍA"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Primer Nombre</label>
                                        <input 
                                            type="text" 
                                            wire:model="first_name"
                                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="Juan"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Segundo Nombre</label>
                                        <input 
                                            type="text" 
                                            wire:model="second_name"
                                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="Carlos"
                                        >
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Información tributaria -->
                        <div class="bg-gray-50 rounded-xl p-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                </svg>
                                Información Tributaria
                            </h4>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Régimen Tributario *</label>
                                <select 
                                    wire:model="tax_regime" 
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    @foreach(\App\Models\Supplier::TAX_REGIMES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('tax_regime') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Información de contacto -->
                        <div class="bg-gray-50 rounded-xl p-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Información de Contacto
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección *</label>
                                    <input 
                                        type="text" 
                                        wire:model="address"
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Calle 123 # 45-67"
                                    >
                                    @error('address') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Departamento *</label>
                                    <select 
                                        wire:model.live="department_id"
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        <option value="">Seleccione...</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('department_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Municipio *</label>
                                    <select 
                                        wire:model="municipality_id"
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        {{ empty($department_id) ? 'disabled' : '' }}
                                    >
                                        <option value="">{{ empty($department_id) ? 'Seleccione departamento primero' : 'Seleccione...' }}</option>
                                        @foreach($municipalities as $mun)
                                            <option value="{{ $mun->id }}">{{ $mun->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('municipality_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono Fijo</label>
                                    <input 
                                        type="text" 
                                        wire:model="phone"
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="(601) 1234567"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Celular</label>
                                    <input 
                                        type="text" 
                                        wire:model="mobile"
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="300 1234567"
                                    >
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                                    <input 
                                        type="email" 
                                        wire:model="email"
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="correo@ejemplo.com"
                                    >
                                    @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Información bancaria (colapsable) -->
                        <div x-data="{ open: {{ ($bank_name || $account_number) ? 'true' : 'false' }} }" class="bg-gray-50 rounded-xl overflow-hidden">
                            <button 
                                type="button"
                                @click="open = !open"
                                class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-gray-100 transition-colors"
                            >
                                <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    Información Bancaria (Opcional)
                                </h4>
                                <svg 
                                    class="w-5 h-5 text-gray-400 transition-transform"
                                    :class="{ 'rotate-180': open }"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-collapse class="px-4 pb-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                                        <input 
                                            type="text" 
                                            wire:model="bank_name"
                                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="Bancolombia"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Cuenta</label>
                                        <select 
                                            wire:model="account_type" 
                                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option value="">Seleccionar...</option>
                                            @foreach(\App\Models\Supplier::ACCOUNT_TYPES as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Número de Cuenta</label>
                                        <input 
                                            type="text" 
                                            wire:model="account_number"
                                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono"
                                            placeholder="123456789"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notas y estado -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                                <textarea 
                                    wire:model="notes"
                                    rows="3"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Observaciones adicionales..."
                                ></textarea>
                            </div>
                            <div class="flex items-start pt-6">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        wire:model="is_active"
                                        class="w-5 h-5 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">Proveedor activo</span>
                                        <p class="text-xs text-gray-500">Los proveedores inactivos no aparecen en selecciones</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Footer del modal -->
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t">
                    <button 
                        type="button"
                        wire:click="closeModal"
                        class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button 
                        wire:click="save"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all"
                    >
                        <span wire:loading.remove wire:target="save">{{ $isEditing ? 'Actualizar' : 'Crear Proveedor' }}</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Confirmar Eliminación -->
    @if($showDeleteModal && $supplierToDelete)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="closeDeleteModal"></div>

            <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-md w-full">
                <div class="bg-white px-6 py-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900">¿Eliminar proveedor?</h3>
                            <div class="mt-3">
                                <div class="bg-gray-50 rounded-xl p-4">
                                    <p class="text-sm font-semibold text-gray-900">{{ $supplierToDelete->full_name }}</p>
                                    <p class="text-sm text-gray-500 mt-1">{{ $supplierToDelete->full_document }}</p>
                                </div>
                                <p class="text-sm text-gray-600 mt-3">
                                    Esta acción no se puede deshacer. El proveedor será eliminado permanentemente.
                                </p>
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
                        wire:click="deleteSupplier"
                        class="px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700 transition-colors"
                    >
                        <span wire:loading.remove wire:target="deleteSupplier">Eliminar</span>
                        <span wire:loading wire:target="deleteSupplier">Eliminando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Bases de Retenciones</h1>
                <p class="text-gray-500 mt-1">Tarifas y bases mínimas por vigencia fiscal para el cálculo de retenciones en los pagos</p>
            </div>
            <div class="flex items-center gap-2">
                @can('retention_configs.create')
                    <button wire:click="openCopyYearModal"
                        class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold rounded-xl transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                        </svg>
                        Copiar de otra vigencia
                    </button>
                @endcan

                @if(auth()->user()->isAdmin())
                @can('retention_configs.copy')
                    <button wire:click="openCopyToSchoolsModal"
                        class="inline-flex items-center px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl shadow-lg shadow-purple-500/30 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Copiar a otros colegios
                    </button>
                @endcan
                @endif

                @can('retention_configs.create')
                    <button wire:click="openCreateModal"
                        class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nueva Configuración
                    </button>
                @endcan
            </div>
        </div>

        {{-- Resumen --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Total Configuraciones</p>
                <p class="text-2xl font-bold text-gray-900">{{ $this->summary['total'] }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Activas</p>
                <p class="text-2xl font-bold text-green-600">{{ $this->summary['active'] }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Vigencias</p>
                <p class="text-2xl font-bold text-blue-600">{{ $this->summary['years'] }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Retefuente</p>
                <p class="text-2xl font-bold text-red-600">{{ $this->summary['retefuente'] }}</p>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="w-full rounded-xl border-gray-300"
                        placeholder="Concepto, nombre o código contable...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia</label>
                    <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                        <option value="">Todas</option>
                        @foreach($this->availableYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                    <select wire:model.live="filterCategory" class="w-full rounded-xl border-gray-300">
                        <option value="">Todas</option>
                        @foreach(\App\Models\RetentionConfig::CATEGORIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Estado:</label>
                    <select wire:model.live="filterStatus" class="rounded-xl border-gray-300 text-sm">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
                <button wire:click="clearFilters" class="text-sm text-gray-500 hover:text-gray-700">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Limpiar filtros
                </button>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vigencia</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Concepto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">No declara</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Declara</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tarifa</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Base mínima</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código contable</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->configs as $config)
                            <tr class="hover:bg-gray-50" wire:key="retention-config-{{ $config->id }}">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-blue-700">{{ $config->fiscal_year }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $config->display_name }}</div>
                                    <div class="text-xs text-gray-500 font-mono">{{ $config->concept }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $config->category_color }}">
                                        {{ $config->category_name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-700">
                                    @if($config->category === 'retefuente')
                                        {{ number_format((float) $config->rate_not_declares, 2, ',', '.') }}%
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-700">
                                    @if($config->category === 'retefuente')
                                        {{ number_format((float) $config->rate_declares, 2, ',', '.') }}%
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-700">
                                    @if($config->category !== 'retefuente')
                                        {{ number_format((float) $config->rate, 2, ',', '.') }}%
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-700 font-mono">
                                    ${{ number_format((float) $config->min_base, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 font-mono">
                                    {{ $config->accounting_code ?? '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <button wire:click="toggleStatus({{ $config->id }})"
                                        @cannot('retention_configs.edit') disabled @endcannot
                                        class="px-2 py-1 text-xs font-medium rounded-full transition-colors {{ $config->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }} @cannot('retention_configs.edit') cursor-not-allowed opacity-50 @endcannot">
                                        {{ $config->is_active ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @can('retention_configs.edit')
                                            <button wire:click="edit({{ $config->id }})"
                                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                        @endcan
                                        @can('retention_configs.delete')
                                            <button wire:click="confirmDelete({{ $config->id }})"
                                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="mt-2">No se encontraron configuraciones.</p>
                                    <p class="text-xs mt-1">Crea una nueva o copia las de otra vigencia.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($this->configs->hasPages())
                <div class="px-6 py-4 border-t">
                    {{ $this->configs->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Crear/Editar --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="closeModal"></div>

            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl transform transition-all sm:my-8 w-full max-w-2xl">
                <form wire:submit="save">
                    <div class="px-6 py-4 border-b border-gray-200 bg-blue-50">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-blue-900">
                                    {{ $isEditing ? 'Editar Configuración' : 'Nueva Configuración de Retención' }}
                                </h3>
                                <p class="text-sm text-blue-700">Base de retención por vigencia fiscal</p>
                            </div>
                            <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Vigencia fiscal <span class="text-red-500">*</span>
                                </label>
                                <input type="number" wire:model="fiscal_year" min="2020" max="2100"
                                    @if($isEditing) readonly @endif
                                    class="w-full rounded-xl border-gray-300 {{ $isEditing ? 'bg-gray-100' : '' }}">
                                @error('fiscal_year') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Concepto <span class="text-red-500">*</span>
                                </label>
                                @if($isEditing)
                                    <input type="text" value="{{ $concept }}" readonly
                                        class="w-full rounded-xl border-gray-300 bg-gray-100 font-mono text-sm">
                                @else
                                    <select wire:model.live="concept" class="w-full rounded-xl border-gray-300">
                                        <option value="">Seleccionar concepto...</option>
                                        @foreach($this->availableConcepts as $opt)
                                            <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                @error('concept') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre para mostrar <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="display_name" class="w-full rounded-xl border-gray-300"
                                placeholder="Ej: Retefuente Compras">
                            @error('display_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Categoría <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="category" class="w-full rounded-xl border-gray-300">
                                @foreach(\App\Models\RetentionConfig::CATEGORIES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        @if($category === 'retefuente')
                            <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                                <p class="text-sm font-medium text-red-900 mb-3">Tarifas Retefuente</p>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-red-700 mb-1">
                                            No declara renta (%) <span class="text-red-500">*</span>
                                        </label>
                                        <div class="flex">
                                            <input type="number" wire:model="rate_not_declares" step="0.01" min="0" max="100"
                                                class="flex-1 rounded-l-xl border-gray-300">
                                            <span class="inline-flex items-center px-3 rounded-r-xl border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">%</span>
                                        </div>
                                        @error('rate_not_declares') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-red-700 mb-1">
                                            Declara renta (%) <span class="text-red-500">*</span>
                                        </label>
                                        <div class="flex">
                                            <input type="number" wire:model="rate_declares" step="0.01" min="0" max="100"
                                                class="flex-1 rounded-l-xl border-gray-300">
                                            <span class="inline-flex items-center px-3 rounded-r-xl border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">%</span>
                                        </div>
                                        @error('rate_declares') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        @else
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tarifa (%) <span class="text-red-500">*</span>
                                </label>
                                <div class="flex">
                                    <input type="number" wire:model="rate" step="0.01" min="0" max="100"
                                        class="flex-1 rounded-l-xl border-gray-300">
                                    <span class="inline-flex items-center px-3 rounded-r-xl border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">%</span>
                                </div>
                                @error('rate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Base mínima para aplicar <span class="text-red-500">*</span>
                            </label>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">$</span>
                                <input type="number" wire:model="min_base" step="0.01" min="0"
                                    class="flex-1 rounded-r-xl border-gray-300">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Usa 0 o 1 si la retención aplica sin base mínima.</p>
                            @error('min_base') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código contable</label>
                            <input type="text" wire:model="accounting_code"
                                class="w-full rounded-xl border-gray-300 font-mono text-sm"
                                placeholder="Ej: 243608 - Retención de Compras">
                            @error('accounting_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                            <textarea wire:model="notes" rows="2" class="w-full rounded-xl border-gray-300"
                                placeholder="Observaciones o justificación del cambio..."></textarea>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" wire:model="is_active" id="is_active"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="is_active" class="text-sm text-gray-700">Configuración activa</label>
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
    @if($showDeleteModal && $configToDelete)
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
                    <h3 class="text-lg font-bold text-gray-900 text-center mb-2">Eliminar Configuración</h3>
                    <p class="text-sm text-gray-500 text-center mb-2">
                        ¿Eliminar <span class="font-medium text-gray-700">{{ $configToDelete->display_name }}</span>
                        de la vigencia <span class="font-medium text-gray-700">{{ $configToDelete->fiscal_year }}</span>?
                    </p>
                    <p class="text-xs text-gray-400 text-center">Las órdenes de pago ya creadas mantienen los valores calculados al momento del registro.</p>
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

    {{-- Modal Copiar de vigencia anterior --}}
    @if($showCopyYearModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="closeCopyYearModal"></div>

            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl transform transition-all sm:my-8 w-full max-w-md">
                <form wire:submit="copyConfigsFromYear">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-900">Copiar configuraciones entre vigencias</h3>
                        <p class="text-sm text-gray-600 mt-1">Duplica todas las configuraciones de una vigencia a otra en este colegio.</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia origen <span class="text-red-500">*</span></label>
                            <select wire:model="copyFromYear" class="w-full rounded-xl border-gray-300">
                                <option value="">Seleccionar...</option>
                                @foreach($this->availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                            @error('copyFromYear') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia destino <span class="text-red-500">*</span></label>
                            <input type="number" wire:model="copyToYear" min="2020" max="2100"
                                class="w-full rounded-xl border-gray-300" placeholder="Ej: 2027">
                            @error('copyToYear') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-xs text-blue-800">
                            Las configuraciones existentes en la vigencia destino no se sobrescriben; se omiten automáticamente.
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                        <button type="button" wire:click="closeCopyYearModal" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors">
                            Copiar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Copiar a otros colegios --}}
    @if($showCopyToSchoolsModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" wire:click="closeCopyToSchoolsModal"></div>

            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl transform transition-all sm:my-8 w-full max-w-2xl">
                <form wire:submit="copyToSchools">
                    <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
                        <h3 class="text-lg font-bold text-purple-900">Copiar configuraciones a otros colegios</h3>
                        <p class="text-sm text-purple-700 mt-1">Replica la vigencia seleccionada del colegio actual en los colegios destino.</p>
                    </div>
                    <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia a copiar <span class="text-red-500">*</span></label>
                            <select wire:model="copyYearSource" class="w-full rounded-xl border-gray-300">
                                <option value="">Seleccionar...</option>
                                @foreach($this->availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                            @error('copyYearSource') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Colegios destino <span class="text-red-500">*</span></label>
                            <div class="border border-gray-200 rounded-xl max-h-72 overflow-y-auto">
                                @foreach($this->allSchools as $s)
                                    @if($s->id !== (int) $schoolId)
                                        <label class="flex items-center gap-2 px-3 py-2 border-b last:border-b-0 hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox"
                                                wire:click="toggleCopySchool({{ $s->id }})"
                                                @checked(in_array($s->id, $copySchoolIds))
                                                class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                            <div class="flex-1">
                                                <div class="text-sm font-medium text-gray-900">{{ $s->name }}</div>
                                                <div class="text-xs text-gray-500">NIT: {{ $s->nit }} · {{ $s->municipality }}</div>
                                            </div>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                            @error('copySchoolIds') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-800">
                            Los conceptos ya existentes en los colegios destino (para la misma vigencia) no se sobrescriben; se omiten automáticamente.
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                        <button type="button" wire:click="closeCopyToSchoolsModal" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors">
                            Copiar a {{ count($copySchoolIds) }} colegio(s)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Fuentes de Financiación</h1>
                <p class="text-gray-500 mt-1">Fuentes por rubro según el Ministerio de Educación Nacional</p>
            </div>
            @can('funding_sources.create')
            <button wire:click="openCreateModal" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nueva Fuente
            </button>
            @endcan
        </div>

        {{-- Info sobre códigos estándar --}}
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 mb-6">
            <div class="flex">
                <svg class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-medium mb-1">Códigos estándar del Ministerio:</p>
                    <p class="text-blue-700">
                        <span class="inline-block mr-4"><strong>1</strong> = RP (Recursos Propios)</span>
                        <span class="inline-block mr-4"><strong>2</strong> = SGP (Sistema General de Participaciones)</span>
                        <span class="inline-block mr-4"><strong>33</strong> = RB RP (Recursos de Balance - Propios)</span>
                        <span class="inline-block"><strong>34</strong> = RB SGP (Recursos de Balance - SGP)</span>
                    </p>
                    <p class="text-blue-600 mt-2">
                        <strong>Importante:</strong> Cada fuente de financiación debe estar asociada a un rubro específico.
                    </p>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border-gray-300" placeholder="Código o nombre...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rubro</label>
                    <select wire:model.live="filterBudgetItem" class="w-full rounded-xl border-gray-300">
                        <option value="">Todos los rubros</option>
                        @foreach($budgetItems as $item)
                            <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select wire:model.live="filterType" class="w-full rounded-xl border-gray-300">
                        <option value="">Todos</option>
                        <option value="sgp">SGP</option>
                        <option value="rp">Recursos Propios</option>
                        <option value="rb">Recursos de Balance</option>
                        <option value="other">Otros</option>
                    </select>
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
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rubro</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre Fuente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($this->fundingSources as $source)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $source->budgetItem?->code ?? '-' }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ Str::limit($source->budgetItem?->name ?? 'Sin rubro', 30) }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center justify-center w-12 h-8 bg-gray-100 text-gray-800 font-mono font-bold rounded-lg">
                                {{ $source->code }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $source->name }}</div>
                            @if($source->description)
                            <div class="text-sm text-gray-500">{{ Str::limit($source->description, 40) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $source->type_color }}">
                                {{ $source->type_name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button wire:click="toggleStatus({{ $source->id }})" class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $source->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $source->is_active ? 'Activo' : 'Inactivo' }}
                            </button>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-1">
                                @can('funding_sources.edit')
                                <button wire:click="editFundingSource({{ $source->id }})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @endcan
                                @can('funding_sources.delete')
                                <button wire:click="confirmDelete({{ $source->id }})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <p class="mt-2">No se encontraron fuentes de financiación</p>
                            <p class="text-sm text-gray-400">Primero debe crear rubros y luego asignarles fuentes de financiación</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @if($this->fundingSources->hasPages())
            <div class="px-6 py-4 border-t">{{ $this->fundingSources->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Modal Crear/Editar --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-lg">
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white">{{ $isEditing ? 'Editar Fuente' : 'Nueva Fuente de Financiación' }}</h3>
                            <p class="text-blue-100 text-sm mt-1">Catálogo del Ministerio de Educación</p>
                        </div>
                        <button type="button" wire:click="closeModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <form wire:submit="save" class="px-6 py-5 space-y-5">
                    {{-- Rubro --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rubro *</label>
                        <select wire:model="budget_item_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Seleccione un rubro --</option>
                            @foreach($budgetItems as $item)
                                <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">La fuente de financiación quedará asociada a este rubro</p>
                        @error('budget_item_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Código --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Código del Ministerio *</label>
                        <input type="text" wire:model="code" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono" placeholder="Ej: 1, 2, 33, 34..." maxlength="10">
                        <p class="text-xs text-gray-500 mt-1">Código asignado por el Ministerio de Educación Nacional</p>
                        @error('code') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Nombre --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" wire:model="name" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ej: Recursos Propios, SGP...">
                        @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Tipo --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Fuente *</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative flex items-center p-3 border-2 rounded-xl cursor-pointer transition-all {{ $type === 'rp' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <input type="radio" wire:model.live="type" value="rp" class="sr-only">
                                <div>
                                    <span class="block text-sm font-medium {{ $type === 'rp' ? 'text-green-700' : 'text-gray-600' }}">RP</span>
                                    <span class="block text-xs {{ $type === 'rp' ? 'text-green-600' : 'text-gray-400' }}">Recursos Propios</span>
                                </div>
                            </label>
                            <label class="relative flex items-center p-3 border-2 rounded-xl cursor-pointer transition-all {{ $type === 'sgp' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <input type="radio" wire:model.live="type" value="sgp" class="sr-only">
                                <div>
                                    <span class="block text-sm font-medium {{ $type === 'sgp' ? 'text-blue-700' : 'text-gray-600' }}">SGP</span>
                                    <span class="block text-xs {{ $type === 'sgp' ? 'text-blue-600' : 'text-gray-400' }}">Sistema General</span>
                                </div>
                            </label>
                            <label class="relative flex items-center p-3 border-2 rounded-xl cursor-pointer transition-all {{ $type === 'rb' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <input type="radio" wire:model.live="type" value="rb" class="sr-only">
                                <div>
                                    <span class="block text-sm font-medium {{ $type === 'rb' ? 'text-yellow-700' : 'text-gray-600' }}">RB</span>
                                    <span class="block text-xs {{ $type === 'rb' ? 'text-yellow-600' : 'text-gray-400' }}">Recursos de Balance</span>
                                </div>
                            </label>
                            <label class="relative flex items-center p-3 border-2 rounded-xl cursor-pointer transition-all {{ $type === 'other' ? 'border-gray-500 bg-gray-50' : 'border-gray-200 hover:border-gray-300' }}">
                                <input type="radio" wire:model.live="type" value="other" class="sr-only">
                                <div>
                                    <span class="block text-sm font-medium {{ $type === 'other' ? 'text-gray-700' : 'text-gray-600' }}">Otros</span>
                                    <span class="block text-xs {{ $type === 'other' ? 'text-gray-600' : 'text-gray-400' }}">Otras fuentes</span>
                                </div>
                            </label>
                        </div>
                        @error('type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Descripción --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción <span class="text-gray-400 font-normal">(opcional)</span></label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-xl border-gray-300" placeholder="Descripción de la fuente..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium">{{ $isEditing ? 'Actualizar' : 'Crear' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Eliminar --}}
    @if($showDeleteModal && $itemToDelete)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeDeleteModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-md">
                <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white">Eliminar Fuente</h3>
                        <button type="button" wire:click="closeDeleteModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <p class="text-gray-600">
                        ¿Está seguro de eliminar la fuente de financiación
                        <strong class="text-gray-900">{{ $itemToDelete->code }} - {{ $itemToDelete->name }}</strong>?
                    </p>
                    <p class="text-sm text-gray-500 mt-2">Esta acción no se puede deshacer.</p>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeDeleteModal" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-xl font-medium">Cancelar</button>
                    <button type="button" wire:click="deleteFundingSource" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

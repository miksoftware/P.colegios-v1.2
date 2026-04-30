<div>
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reintegros y Traslados</h1>
            <p class="text-sm text-gray-500">Mueve artículos de una ubicación a otra generando actas de entrega.</p>
        </div>
        
        <div class="flex items-center gap-3 w-full md:w-auto">
            <div class="relative w-full md:w-64">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar actas..." class="w-full pl-10 pr-4 py-2 border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 shadow-sm text-sm">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <button wire:click="create" class="flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-sm whitespace-nowrap">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo Reintegro
            </button>
        </div>
    </div>

    @if(!$isCreating)
    <!-- Tabla Actas -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">Acta No.</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">Fecha</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">Entrega</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">Recibe</th>
                        <th class="px-6 py-4 text-center font-semibold text-gray-600">Items</th>
                        <th class="px-6 py-4 text-right font-semibold text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->transfers as $transfer)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-bold text-gray-900">{{ $transfer->consecutive }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $transfer->transfer_date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $transfer->from_name }}</div>
                                <div class="text-xs text-gray-500">{{ $transfer->from_location }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $transfer->to_name }}</div>
                                <div class="text-xs text-gray-500">{{ $transfer->to_location }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $transfer->items->count() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('inventory.transfers.pdf', $transfer) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                No se encontraron actas de reintegro.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->transfers->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $this->transfers->links() }}
            </div>
        @endif
    </div>

    @else
    <!-- Formulario Crear Acta -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
        <div class="flex items-center justify-between mb-8 pb-4 border-b border-gray-100">
            <h2 class="text-xl font-bold text-gray-900">Nueva Acta de Reintegro / Traslado</h2>
            <button wire:click="cancel" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form wire:submit="save" class="space-y-8">
            
            <!-- Datos Generales -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Quien Entrega -->
                <div class="p-5 bg-gray-50 rounded-xl border border-gray-200 space-y-4">
                    <h3 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        Datos de Quien Entrega
                    </h3>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="from_name" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('from_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Documento</label>
                            <input type="text" wire:model="from_document" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Ubicación / Dependencia <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="from_location" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            @error('from_location') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Quien Recibe -->
                <div class="p-5 bg-blue-50 rounded-xl border border-blue-100 space-y-4">
                    <h3 class="text-sm font-bold text-blue-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        Datos de Quien Recibe
                    </h3>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="to_name" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm bg-white">
                        @error('to_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Documento</label>
                            <input type="text" wire:model="to_document" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">NUEVA Ubicación <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="to_location" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm bg-white" placeholder="Ej: Laboratorio, Aula 1">
                            @error('to_location') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selección de Artículos -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Artículos a Trasladar</h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Buscador -->
                    <div class="space-y-4">
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="itemSearch" placeholder="Buscar por nombre o placa..." class="w-full pl-10 pr-4 py-2 border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 shadow-sm text-sm">
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>

                        <div class="border border-gray-200 rounded-xl bg-white overflow-hidden max-h-64 overflow-y-auto">
                            @forelse($this->availableItems as $item)
                                <div wire:click="toggleItemSelection({{ $item->id }})" class="p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer flex items-center justify-between transition-colors {{ in_array($item->id, $selectedItems) ? 'bg-blue-50/50' : '' }}">
                                    <div>
                                        <div class="font-medium text-sm text-gray-900">{{ $item->name }}</div>
                                        <div class="text-xs text-gray-500 flex gap-2">
                                            <span>Placa: {{ $item->current_tag ?? 'N/A' }}</span>
                                            <span>&bull;</span>
                                            <span>Ubic: {{ $item->location ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <div class="w-5 h-5 rounded border flex items-center justify-center {{ in_array($item->id, $selectedItems) ? 'bg-blue-600 border-blue-600' : 'border-gray-300' }}">
                                            @if(in_array($item->id, $selectedItems))
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-sm text-gray-500">
                                    @if(empty($itemSearch))
                                        Usa el buscador para encontrar artículos.
                                    @else
                                        No se encontraron artículos.
                                    @endif
                                </div>
                            @endforelse
                        </div>
                        @error('selectedItems') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Seleccionados -->
                    <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                        <h4 class="text-sm font-bold text-gray-700 mb-3">Artículos Seleccionados ({{ count($selectedItems) }})</h4>
                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            @forelse($this->selectedItemsList as $item)
                                <div class="flex items-center justify-between p-2 bg-white rounded-lg border border-gray-200 shadow-sm">
                                    <div class="truncate mr-2">
                                        <div class="font-medium text-xs text-gray-900 truncate">{{ $item->name }}</div>
                                        <div class="text-[10px] text-gray-500">Placa: {{ $item->current_tag }}</div>
                                    </div>
                                    <button type="button" wire:click="toggleItemSelection({{ $item->id }})" class="text-red-500 hover:text-red-700 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            @empty
                                <div class="text-xs text-gray-500 text-center py-4">Ningún artículo seleccionado.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-200 pt-6 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="w-full md:w-1/3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Fecha de Acta</label>
                    <input type="date" wire:model="transfer_date" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>
                
                <div class="flex items-center gap-3 w-full md:w-auto mt-4 md:mt-0">
                    <button type="button" wire:click="cancel" class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors w-full md:w-auto">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-sm w-full md:w-auto flex items-center justify-center gap-2">
                        <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Guardar Reintegro
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endif
</div>

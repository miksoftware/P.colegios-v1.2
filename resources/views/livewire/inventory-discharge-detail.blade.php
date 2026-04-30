<div>
    <div class="mb-6">
        <a href="{{ route('inventory.discharges') }}" class="text-red-600 hover:text-red-800 text-sm font-medium flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Volver a Bajas
        </a>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900">
                    Detalle Resolución de Baja N° {{ str_pad($discharge->consecutive, 4, '0', STR_PAD_LEFT) }}
                </h1>
                @if($this->isFinished)
                    <span class="px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-800 border border-green-300 uppercase tracking-wider">
                        ✓ Terminado
                    </span>
                @else
                    <span class="px-3 py-1 text-xs font-bold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-300 uppercase tracking-wider">
                        Borrador
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Advertencia de estado --}}
    @if($this->isFinished)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6 flex items-start gap-3">
            <svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.832c-.77-.833-2.194-.833-2.964 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <div>
                <p class="font-semibold text-amber-800">Esta resolución de baja ya está terminada.</p>
                <p class="text-sm text-amber-700 mt-1">
                    Ya tiene artículos asignados por lo tanto no se puede editar la información de la resolución.
                    Si necesita hacer cambios, primero debe revertir todos los artículos de esta baja.
                </p>
            </div>
        </div>
    @endif

    <!-- Info General de la Baja -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <span class="block text-sm font-medium text-gray-500">Fecha de Baja</span>
                <span class="block text-base font-semibold text-gray-900">{{ $discharge->date->format('d/m/Y') }}</span>
            </div>
            <div>
                <span class="block text-sm font-medium text-gray-500">N° Resolución / Acta</span>
                <span class="block text-base font-semibold text-gray-900">{{ $discharge->resolution_number ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="block text-sm font-medium text-gray-500">Valor Total en Libros (Dados de baja)</span>
                <span class="block text-2xl font-bold text-red-600">${{ number_format($discharge->total_value, 2) }}</span>
            </div>
            @if($discharge->observations)
                <div class="col-span-1 md:col-span-3">
                    <span class="block text-sm font-medium text-gray-500">Motivos / Observaciones</span>
                    <span class="block text-sm text-gray-700">{{ $discharge->observations }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-800">Artículos Retirados ({{ $discharge->items->count() }})</h2>
        <div class="flex gap-2">
            @if(!$this->isFinished)
                <button wire:click="openSelectModal" class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    Dar de Baja Artículos
                </button>
            @endif
        </div>
    </div>

    <!-- Tabla de Artículos de esta baja -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Placa</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Descripción</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Cuenta</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">N° Entrada Orig.</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Valor Histórico</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($discharge->items as $item)
                        <tr class="hover:bg-red-50">
                            <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $item->current_tag ?? 'N/A' }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $item->name }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                {{ $item->account->code ?? 'N/A' }} - {{ Str::limit($item->account->name ?? '', 25) }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                @if($item->entry)
                                    Entrada N° {{ str_pad($item->entry->consecutive, 4, '0', STR_PAD_LEFT) }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm font-medium text-right text-gray-900">${{ number_format($item->initial_value, 2) }}</td>
                            <td class="px-6 py-3 text-right text-sm">
                                <button wire:click="removeItem({{ $item->id }})" wire:confirm="¿Seguro que desea revertir la baja de este artículo? Volverá al inventario activo." class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors text-xs font-medium" title="Revertir Baja y recuperar artículo">
                                    Revertir
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay artículos dados de baja</h3>
                                <p class="mt-1 text-sm text-gray-500">Use el botón "Dar de Baja Artículos" para buscar y seleccionar los bienes a retirar.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Buscar y Asignar Artículos para dar de Baja -->
    @if($showSelectModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showSelectModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-red-50 px-6 py-4 border-b border-red-100">
                        <h3 class="text-lg leading-6 font-bold text-red-900">Buscar y Seleccionar Bienes para dar de Baja</h3>
                        <p class="text-sm text-red-700 mt-1">Busque por nombre, placa o ubicación. Solo se muestran artículos activos con entrada.</p>
                    </div>
                    <div class="bg-white px-6 py-4">
                        {{-- Buscador --}}
                        <div class="mb-4">
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input 
                                    wire:model.live.debounce.300ms="itemSearch" 
                                    type="text" 
                                    placeholder="Escriba el nombre, placa o ubicación del artículo..." 
                                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                    autofocus
                                >
                            </div>
                            @if(!empty($itemSearch) && $this->availableItems->isEmpty())
                                <p class="text-sm text-gray-500 mt-2 text-center">No se encontraron artículos con "{{ $itemSearch }}"</p>
                            @endif
                            @if(empty($itemSearch))
                                <p class="text-sm text-gray-400 mt-2 text-center">Escriba al menos un carácter para buscar artículos.</p>
                            @endif
                        </div>

                        {{-- Seleccionados --}}
                        @if(count($selectedItems) > 0)
                            <div class="mb-3 px-3 py-2 bg-red-50 rounded-xl border border-red-200">
                                <span class="text-sm font-medium text-red-800">{{ count($selectedItems) }} artículo(s) seleccionado(s) para dar de baja</span>
                            </div>
                        @endif

                        {{-- Resultados --}}
                        <div class="max-h-80 overflow-y-auto border rounded-xl">
                            <table class="w-full">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 w-10">Sel.</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Placa</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Descripción</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Cuenta</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Ubicación</th>
                                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Valor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($this->availableItems as $aItem)
                                        <tr class="hover:bg-gray-50 cursor-pointer">
                                            <td class="px-4 py-2">
                                                <input type="checkbox" wire:model.live="selectedItems" value="{{ $aItem->id }}" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                            </td>
                                            <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $aItem->current_tag ?? 'S/P' }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ Str::limit($aItem->name, 35) }}</td>
                                            <td class="px-4 py-2 text-xs text-gray-500">{{ $aItem->account->code ?? '' }}</td>
                                            <td class="px-4 py-2 text-xs text-gray-500">{{ $aItem->location ?? 'N/A' }}</td>
                                            <td class="px-4 py-2 text-sm text-right font-medium">${{ number_format($aItem->initial_value, 2) }}</td>
                                        </tr>
                                    @empty
                                        @if(!empty($itemSearch))
                                        @endif
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:flex sm:flex-row-reverse border-t">
                        <button 
                            wire:click="assignSelectedItems" 
                            class="w-full sm:ml-3 sm:w-auto inline-flex justify-center rounded-xl bg-red-600 px-4 py-2 text-white shadow-sm hover:bg-red-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                            @if(count($selectedItems) === 0) disabled @endif
                        >
                            Ejecutar Baja ({{ count($selectedItems) }})
                        </button>
                        <button wire:click="$set('showSelectModal', false)" class="mt-3 w-full sm:mt-0 sm:w-auto inline-flex justify-center rounded-xl bg-white px-4 py-2 text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

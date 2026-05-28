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
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showSelectModal', false)"></div>

                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-4xl overflow-hidden">

                    {{-- Header --}}
                    <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-red-900">Seleccionar Artículos para dar de Baja</h3>
                            <p class="text-sm text-red-600 mt-0.5">Busque y haga clic sobre cada artículo para seleccionarlo. Solo se muestran artículos activos con entrada.</p>
                        </div>
                        <button wire:click="$set('showSelectModal', false)" class="text-gray-400 hover:text-gray-600 ml-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Two-panel body --}}
                    <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">

                        {{-- LEFT: Buscador + lista --}}
                        <div class="space-y-3">
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input
                                    wire:model.live.debounce.300ms="itemSearch"
                                    type="text"
                                    placeholder="Buscar por nombre, placa o ubicación..."
                                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm"
                                    autofocus
                                >
                            </div>

                            <div class="border border-gray-200 rounded-xl bg-white overflow-hidden max-h-72 overflow-y-auto">
                                @forelse($this->availableItems as $aItem)
                                    <div
                                        wire:click="toggleItemSelection({{ $aItem->id }})"
                                        class="p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer flex items-center justify-between transition-colors {{ in_array($aItem->id, $selectedItems) ? 'bg-red-50/60' : '' }}"
                                    >
                                        <div class="min-w-0 mr-3">
                                            <div class="font-medium text-sm text-gray-900 truncate">{{ $aItem->name }}</div>
                                            <div class="text-xs text-gray-500 flex flex-wrap gap-x-2 mt-0.5">
                                                <span>Placa: {{ $aItem->current_tag ?? 'S/P' }}</span>
                                                @if($aItem->location)
                                                    <span>&bull; {{ $aItem->location }}</span>
                                                @endif
                                                @if($aItem->account)
                                                    <span>&bull; {{ $aItem->account->code }}</span>
                                                @endif
                                                <span class="font-semibold text-gray-700">&bull; ${{ number_format($aItem->initial_value, 2) }}</span>
                                            </div>
                                        </div>
                                        <div class="shrink-0">
                                            <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors {{ in_array($aItem->id, $selectedItems) ? 'bg-red-600 border-red-600' : 'border-gray-300' }}">
                                                @if(in_array($aItem->id, $selectedItems))
                                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-8 text-center text-sm text-gray-500">
                                        @if(empty($itemSearch))
                                            Use el buscador para encontrar artículos.
                                        @else
                                            No se encontraron artículos con "{{ $itemSearch }}".
                                        @endif
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- RIGHT: Seleccionados --}}
                        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 flex flex-col">
                            <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-600 text-white text-xs font-bold">
                                    {{ count($selectedItems) }}
                                </span>
                                Artículos seleccionados para dar de baja
                            </h4>

                            <div class="space-y-2 flex-1 max-h-64 overflow-y-auto">
                                @forelse($this->selectedItemsList as $sItem)
                                    <div class="flex items-center justify-between p-2.5 bg-white rounded-lg border border-red-100 shadow-sm">
                                        <div class="truncate mr-2">
                                            <div class="font-medium text-xs text-gray-900 truncate">{{ $sItem->name }}</div>
                                            <div class="text-[10px] text-gray-500">
                                                Placa: {{ $sItem->current_tag ?? 'S/P' }}
                                                &bull; ${{ number_format($sItem->initial_value, 2) }}
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="toggleItemSelection({{ $sItem->id }})"
                                            class="shrink-0 text-red-400 hover:text-red-600 p-1 rounded transition-colors"
                                            title="Quitar"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                @empty
                                    <div class="text-xs text-gray-400 text-center py-6">
                                        Haga clic en los artículos de la izquierda para agregarlos.
                                    </div>
                                @endforelse
                            </div>

                            @if(count($selectedItems) > 0)
                                <div class="mt-3 pt-3 border-t border-gray-200 text-xs text-gray-600 font-medium">
                                    Valor total a dar de baja:
                                    <span class="text-red-600 font-bold">
                                        ${{ number_format($this->selectedItemsList->sum('initial_value'), 2) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="bg-gray-50 px-6 py-4 border-t flex flex-row-reverse gap-3">
                        <button
                            wire:click="assignSelectedItems"
                            @if(count($selectedItems) === 0) disabled @endif
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-red-600 text-white font-medium hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Ejecutar Baja ({{ count($selectedItems) }})
                        </button>
                        <button
                            wire:click="$set('showSelectModal', false)"
                            class="inline-flex items-center px-5 py-2 rounded-xl bg-white text-gray-700 font-medium ring-1 ring-gray-300 hover:bg-gray-50 transition-colors"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

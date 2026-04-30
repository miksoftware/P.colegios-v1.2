<div>
    <div class="mb-6">
        <a href="{{ route('inventory.entries') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Volver a Entradas
        </a>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">
                Detalle Comprobante de Entrada N° {{ str_pad($entry->consecutive, 4, '0', STR_PAD_LEFT) }}
            </h1>
        </div>
    </div>

    <!-- Info General de la Entrada -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <span class="block text-sm font-medium text-gray-500">Fecha</span>
                <span class="block text-base font-semibold text-gray-900">{{ $entry->date->format('d/m/Y') }}</span>
            </div>
            <div>
                <span class="block text-sm font-medium text-gray-500">Proveedor</span>
                <span class="block text-base font-semibold text-gray-900">{{ $entry->supplier->full_name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="block text-sm font-medium text-gray-500">Factura / Remisión</span>
                <span class="block text-base font-semibold text-gray-900">{{ $entry->invoice_number ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="block text-sm font-medium text-gray-500">Valor Total Calculado</span>
                <span class="block text-2xl font-bold text-green-600">${{ number_format($entry->total_value, 2) }}</span>
            </div>
            @if($entry->observations)
                <div class="col-span-1 md:col-span-4">
                    <span class="block text-sm font-medium text-gray-500">Observaciones</span>
                    <span class="block text-sm text-gray-700">{{ $entry->observations }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-800">Artículos Asignados ({{ $entry->items->count() }})</h2>
        <div class="flex gap-2">
            <button wire:click="openSelectModal" class="flex items-center gap-2 px-4 py-2 bg-white text-gray-700 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                Asignar Existentes
            </button>
            <button wire:click="openCreateModal" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Crear Múltiples (Idénticos)
            </button>
        </div>
    </div>

    <!-- Tabla de Artículos de esta entrada -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Placa</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Descripción</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Cuenta</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Valor</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($entry->items as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $item->current_tag ?? 'N/A' }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $item->name }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $item->account->code }}</td>
                            <td class="px-6 py-3 text-sm font-medium text-right text-gray-900">${{ number_format($item->initial_value, 2) }}</td>
                            <td class="px-6 py-3 text-center text-sm">
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">{{ $item->state_name }}</span>
                            </td>
                            <td class="px-6 py-3 text-right text-sm">
                                <button wire:click="removeItem({{ $item->id }})" class="text-red-600 hover:text-red-900" title="Desvincular de esta entrada">
                                    Quitar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                No hay artículos asociados a esta entrada. Agrega o crea nuevos artículos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Crear Múltiples Artículos -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showCreateModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form wire:submit="createMultipleItems">
                        <div class="bg-blue-50 px-6 py-4 border-b border-blue-100">
                            <h3 class="text-lg leading-6 font-bold text-blue-900">Creación Automática de Artículos</h3>
                            <p class="text-sm text-blue-700 mt-1">Llena los datos una vez. El sistema creará individualmente la cantidad que indiques.</p>
                        </div>
                        <div class="bg-white px-6 py-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad a generar *</label>
                                    <input type="number" wire:model="quantity" min="1" max="100" class="w-full rounded-xl border-gray-300 bg-yellow-50 focus:border-yellow-500 focus:ring-yellow-500 font-bold text-lg text-center">
                                    @error('quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor Unitario *</label>
                                    <input type="number" step="0.01" wire:model="initial_value" class="w-full rounded-xl border-gray-300 shadow-sm">
                                    @error('initial_value') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción idéntica para todos *</label>
                                    <input type="text" wire:model="name" class="w-full rounded-xl border-gray-300 shadow-sm">
                                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Contable *</label>
                                    <select wire:model="inventory_accounting_account_id" class="w-full rounded-xl border-gray-300 shadow-sm">
                                        <option value="">Seleccione...</option>
                                        @foreach($this->accounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('inventory_accounting_account_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado Físico *</label>
                                    <select wire:model="state" class="w-full rounded-xl border-gray-300 shadow-sm">
                                        @foreach(\App\Models\InventoryItem::STATES as $key => $val)
                                            <option value="{{ $key }}">{{ $val }}</option>
                                        @endforeach
                                    </select>
                                    @error('state') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Inventario *</label>
                                    <select wire:model="inventory_type" class="w-full rounded-xl border-gray-300 shadow-sm">
                                        @foreach(\App\Models\InventoryItem::INVENTORY_TYPES as $key => $val)
                                            <option value="{{ $key }}">{{ $val }}</option>
                                        @endforeach
                                    </select>
                                    @error('inventory_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Prefijo de Placa (Opcional)</label>
                                    <input type="text" wire:model="base_tag" placeholder="Ej: SILLA" class="w-full rounded-xl border-gray-300 shadow-sm">
                                    <span class="text-xs text-gray-500">Generará: SILLA-001, SILLA-002...</span>
                                    @error('base_tag') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-4 sm:flex sm:flex-row-reverse border-t">
                            <button type="submit" class="w-full sm:ml-3 sm:w-auto inline-flex justify-center rounded-xl bg-blue-600 px-4 py-2 text-white shadow-sm hover:bg-blue-700 font-medium">Generar Artículos</button>
                            <button type="button" wire:click="$set('showCreateModal', false)" class="mt-3 w-full sm:mt-0 sm:w-auto inline-flex justify-center rounded-xl bg-white px-4 py-2 text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Asignar Artículos Existentes -->
    @if($showSelectModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showSelectModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-6 py-5">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Seleccionar Artículos Disponibles</h3>
                        <div class="max-h-96 overflow-y-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Sel.</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Descripción</th>
                                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Valor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($this->availableItems as $aItem)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2">
                                                <input type="checkbox" wire:model="selectedItems" value="{{ $aItem->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-700">
                                                {{ $aItem->name }} 
                                                <span class="text-xs text-gray-400 block">{{ $aItem->current_tag }}</span>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-right font-medium">${{ number_format($aItem->initial_value, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-8 text-center text-gray-500 text-sm">No hay artículos libres (sin entrada) en este colegio.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:flex sm:flex-row-reverse border-t">
                        <button wire:click="assignSelectedItems" class="w-full sm:ml-3 sm:w-auto inline-flex justify-center rounded-xl bg-blue-600 px-4 py-2 text-white shadow-sm hover:bg-blue-700 font-medium">Vincular Seleccionados</button>
                        <button wire:click="$set('showSelectModal', false)" class="mt-3 w-full sm:mt-0 sm:w-auto inline-flex justify-center rounded-xl bg-white px-4 py-2 text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

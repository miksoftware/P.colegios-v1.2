<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Salidas / Bajas de Inventario</h1>
        
        @can('inventory_discharges.create')
            <button wire:click="openCreateModal" class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Baja
            </button>
        @endcan
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="N°, Resolución u observaciones..." class="w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" />
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">N° Interno</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">N° Resolución / Acta</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Valor de Baja</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($this->discharges as $discharge)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-center">
                                #{{ str_pad($discharge->consecutive, 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $discharge->date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $discharge->resolution_number ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">
                                ${{ number_format($discharge->total_value, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($discharge->items_count > 0)
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                        ✓ Terminado
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">
                                        Borrador
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="{{ route('inventory.discharges.details', $discharge->id) }}" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors inline-block">
                                    Detalles / Artículos
                                </a>
                                @if($discharge->items_count > 0)
                                    <a href="{{ route('inventory.discharges.pdf', $discharge->id) }}" target="_blank" class="text-orange-600 hover:text-orange-900 bg-orange-50 hover:bg-orange-100 px-3 py-1.5 rounded-lg transition-colors inline-block">
                                        PDF
                                    </a>
                                @endif
                                @can('inventory_discharges.edit')
                                    @if($discharge->items_count === 0)
                                        <button wire:click="edit({{ $discharge->id }})" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors">
                                            Editar
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay comprobantes de baja</h3>
                                <p class="mt-1 text-sm text-gray-500">Registra una nueva baja cuando un bien sea retirado de la institución.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->discharges->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $this->discharges->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                    <form wire:submit="save">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="mb-5 border-b pb-4">
                                <h3 class="text-xl leading-6 font-bold text-gray-900" id="modal-title">
                                    {{ $isEditing ? 'Editar Baja N° ' . str_pad($discharges->firstWhere('id', $dischargeId)->consecutive ?? '', 4, '0', STR_PAD_LEFT) : 'Nueva Resolución de Baja' }}
                                </h3>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de la Baja *</label>
                                    <input type="date" wire:model="date" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                    @error('date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">N° de Resolución / Acta Legal</label>
                                    <input type="text" wire:model="resolution_number" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                    <span class="text-xs text-gray-500 block mt-1">Ej: Resolución N° 045</span>
                                    @error('resolution_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Motivos / Observaciones</label>
                                    <textarea wire:model="observations" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"></textarea>
                                    @error('observations') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="flex items-center mt-4">
                                        <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                                        <span class="ml-2 text-sm text-gray-600">Documento Activo</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-4 sm:px-6 sm:flex sm:flex-row-reverse border-t">
                            <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Guardar
                            </button>
                            <button type="button" wire:click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

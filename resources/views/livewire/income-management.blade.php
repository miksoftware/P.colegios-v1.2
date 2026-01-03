<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Ingresos Reales</h1>
                <p class="text-gray-500 mt-1">Gestión del recaudo y seguimiento presupuestal</p>
            </div>
            @can('incomes.create')
            <button wire:click="openCreateModal" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Registrar Ingreso
            </button>
            @endcan
        </div>

        <!-- Summary Cards -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center px-4 py-2 border-r border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Presupuestado</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">${{ number_format($this->summary['budgeted'], 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">Año {{ $filterYear }}</p>
                </div>
                <div class="text-center px-4 py-2 border-r border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Recaudado</p>
                    <p class="text-xl font-bold text-green-600 mt-1">${{ number_format($this->summary['executed'], 0, ',', '.') }}</p>
                    <p class="text-xs text-green-600">{{ number_format($this->summary['percentage'], 1) }}% ejecutado</p>
                </div>
                <div class="text-center px-4 py-2 border-r border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Pendiente</p>
                    <p class="text-xl font-bold text-orange-600 mt-1">${{ number_format($this->summary['pending'], 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">Por gestionar</p>
                </div>
                <div class="text-center px-4 py-2">
                    <p class="text-xs font-medium text-gray-500 uppercase">Cumplimiento</p>
                    <p class="text-xl font-bold text-blue-600 mt-1">{{ number_format($this->summary['percentage'], 1) }}%</p>
                    <div class="mt-1 w-full bg-gray-200 rounded-full h-1.5">
                        <div class="bg-blue-600 rounded-full h-1.5" style="width: {{ min($this->summary['percentage'], 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border-gray-300" placeholder="Nombre, descripción...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fuente</label>
                    <select wire:model.live="filterSource" class="w-full rounded-xl border-gray-300">
                        <option value="">Todas</option>
                        @foreach($fundingSources as $source)
                            <option value="{{ $source->id }}">{{ $source->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                    <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                        @foreach($this->availableYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button wire:click="resetFilters" class="w-full px-4 py-2 text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Limpiar
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fuente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($this->incomes as $income)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $income->date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $income->name }}</div>
                            @if($income->description)
                                <div class="text-sm text-gray-500">{{ Str::limit($income->description, 40) }}</div>
                            @endif
                            @if($income->transaction_reference)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 mt-1">
                                    Ref: {{ $income->transaction_reference }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $income->fundingSource->type_color }}">
                                {{ $income->fundingSource->name }}
                            </span>
                            @if($income->fundingSource->budgetItem)
                                <div class="text-xs text-gray-400 mt-1">{{ $income->fundingSource->budgetItem->code }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize">
                            {{ $income->payment_method ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                            ${{ number_format($income->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-1">
                                @can('incomes.edit')
                                <button wire:click="edit({{ $income->id }})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @endcan
                                @can('incomes.delete')
                                <button wire:click="confirmDelete({{ $income->id }})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">No se encontraron ingresos registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($this->incomes->hasPages())<div class="px-6 py-4 border-t">{{ $this->incomes->links() }}</div>@endif
        </div>
    </div>

    <!-- Modal Crear/Editar -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-lg">
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white">{{ $isEditing ? 'Editar Ingreso' : 'Registrar Ingreso' }}</h3>
                            <p class="text-blue-100 text-sm mt-1">Registro de recaudo real</p>
                        </div>
                        <button type="button" wire:click="closeModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <form wire:submit="save" class="px-6 py-5 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fuente de Financiación *</label>
                        <select wire:model="funding_source_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Seleccione...</option>
                            @foreach($fundingSources as $source)
                                <option value="{{ $source->id }}">{{ $source->name }} {{ $source->budgetItem ? '('.$source->budgetItem->code.')' : '' }}</option>
                            @endforeach
                        </select>
                        @error('funding_source_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre / Concepto *</label>
                        <input type="text" wire:model="name" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ej: Matrícula Estudiante X">
                        @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Descripción opcional..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">$</span>
                                <input type="number" wire:model="amount" step="0.01" min="0" class="flex-1 rounded-r-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="0.00">
                            </div>
                            @error('amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                            <input type="date" wire:model="date" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pago</label>
                            <select wire:model="payment_method" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Seleccione...</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="cheque">Cheque</option>
                                <option value="consignacion">Consignación</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                            <input type="text" wire:model="transaction_reference" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ej: TRX-12345">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium">{{ $isEditing ? 'Actualizar' : 'Guardar' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Eliminar -->
    @if($showDeleteModal && $itemToDelete)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeDeleteModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-md">
                <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white">Eliminar Ingreso</h3>
                        <button type="button" wire:click="closeDeleteModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <p class="text-gray-600">¿Está seguro de eliminar el ingreso <strong class="text-gray-900">{{ $itemToDelete->name }}</strong>?</p>
                    <p class="text-sm text-gray-500 mt-2">Monto: ${{ number_format($itemToDelete->amount, 0, ',', '.') }}</p>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeDeleteModal" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-xl font-medium">Cancelar</button>
                    <button type="button" wire:click="delete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

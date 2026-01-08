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
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                <div class="text-center px-4 py-2 border-r border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Presupuestado</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">${{ number_format($this->summary['budgeted'], 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">Año {{ $filterYear }}</p>
                </div>
                <div class="text-center px-4 py-2 border-r border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Recaudado</p>
                    <p class="text-xl font-bold text-green-600 mt-1">${{ number_format($this->summary['executed'], 0, ',', '.') }}</p>
                    <p class="text-xs text-green-600">{{ number_format($this->summary['percentage'], 1) }}%</p>
                </div>
                <div class="text-center px-4 py-2 border-r border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Pendiente</p>
                    <p class="text-xl font-bold text-orange-600 mt-1">${{ number_format($this->summary['pending'], 0, ',', '.') }}</p>
                </div>
                <div class="text-center px-4 py-2 border-r border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Cumplimiento</p>
                    <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 rounded-full h-2 transition-all" style="width: {{ min($this->summary['percentage'], 100) }}%"></div>
                    </div>
                    <p class="text-sm font-bold text-blue-600 mt-1">{{ number_format($this->summary['percentage'], 1) }}%</p>
                </div>
                <div class="text-center px-4 py-2 border-r border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Sin Recaudar</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $this->summary['count_pending'] }}</p>
                    <p class="text-xs text-yellow-600">Pendientes</p>
                </div>
                <div class="text-center px-4 py-2 border-r border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Parciales</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ $this->summary['count_partial'] }}</p>
                    <p class="text-xs text-blue-600">En proceso</p>
                </div>
                <div class="text-center px-4 py-2">
                    <p class="text-xs font-medium text-gray-500 uppercase">Completos</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ $this->summary['count_completed'] }}</p>
                    <p class="text-xs text-green-600">Finalizados</p>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button wire:click="$set('filterStatus', '')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ empty($filterStatus) ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Todos los Presupuestos
                    </button>
                    <button wire:click="$set('filterStatus', 'pending')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $filterStatus === 'pending' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <span class="inline-flex items-center">
                            Pendientes
                            @if($this->summary['count_pending'] > 0)
                            <span class="ml-2 bg-yellow-100 text-yellow-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ $this->summary['count_pending'] }}</span>
                            @endif
                        </span>
                    </button>
                    <button wire:click="$set('filterStatus', 'partial')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $filterStatus === 'partial' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <span class="inline-flex items-center">
                            Parciales
                            @if($this->summary['count_partial'] > 0)
                            <span class="ml-2 bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ $this->summary['count_partial'] }}</span>
                            @endif
                        </span>
                    </button>
                    <button wire:click="$set('filterStatus', 'completed')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $filterStatus === 'completed' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Completados
                    </button>
                </nav>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filtrar por Rubro</label>
                    <select wire:model.live="filterBudgetItem" class="w-full rounded-xl border-gray-300">
                        <option value="">Todos los rubros</option>
                        @foreach($budgetItems as $item)
                            <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año Fiscal</label>
                    <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                        @foreach($this->availableYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar en Ingresos</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border-gray-300" placeholder="Nombre, referencia...">
                </div>
                <div class="flex items-end">
                    <button wire:click="resetFilters" class="w-full px-4 py-2 text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Limpiar filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla de Presupuestos Pendientes de Recaudo -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <h2 class="text-lg font-semibold text-gray-900">Estado de Recaudo por Fuente</h2>
                <p class="text-sm text-gray-500">Presupuestos de ingreso y su estado de recaudo</p>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rubro</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fuente</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Presupuestado</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Recaudado</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pendiente</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">%</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($this->pendingBudgets as $budget)
                    <tr class="hover:bg-gray-50 {{ $budget['status'] === 'pending' ? 'bg-yellow-50/50' : '' }}">
                        <td class="px-6 py-4">
                            <span class="text-sm font-mono font-bold text-gray-700">{{ $budget['budget_item']->code }}</span>
                            <div class="text-xs text-gray-500">{{ Str::limit($budget['budget_item']->name, 25) }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $budget['funding_source']->type_color }}">
                                {{ $budget['funding_source']->code }}
                            </span>
                            <div class="text-xs text-gray-500 mt-1">{{ $budget['funding_source']->name }}</div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                            ${{ number_format($budget['budgeted'], 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium text-green-600">
                            ${{ number_format($budget['collected'], 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium {{ $budget['pending'] > 0 ? 'text-orange-600' : ($budget['pending'] < 0 ? 'text-purple-600' : 'text-gray-500') }}">
                            ${{ number_format(abs($budget['pending']), 0, ',', '.') }}
                            @if($budget['pending'] < 0)
                            <span class="text-xs">(exceso)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex flex-col items-center">
                                <span class="text-sm font-bold {{ $budget['percentage'] >= 100 ? 'text-green-600' : ($budget['percentage'] >= 50 ? 'text-blue-600' : 'text-orange-600') }}">
                                    {{ $budget['percentage'] }}%
                                </span>
                                <div class="w-16 bg-gray-200 rounded-full h-1.5 mt-1">
                                    <div class="h-1.5 rounded-full {{ $budget['percentage'] >= 100 ? 'bg-green-500' : ($budget['percentage'] >= 50 ? 'bg-blue-500' : 'bg-orange-500') }}" style="width: {{ min($budget['percentage'], 100) }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $budget['status_color'] }}">
                                {{ $budget['status_label'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($budget['status'] === 'completed')
                                <span class="text-xs text-green-600 font-medium">✓ Completo</span>
                            @elseif($budget['status'] === 'exceeded')
                                <span class="text-xs text-purple-600 font-medium">✓ Excedido</span>
                            @else
                                <div class="flex justify-end gap-1">
                                    @can('incomes.create')
                                    <button wire:click="registerIncomeFor({{ $budget['id'] }})" class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all" title="Registrar ingreso">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Ingreso
                                    </button>
                                    @endcan
                                    @if($budget['status'] === 'partial')
                                    @can('budgets.modify')
                                    <button wire:click="confirmComplete({{ $budget['id'] }})" class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-orange-500 hover:bg-orange-600 rounded-lg transition-all" title="Marcar como completo (crear reducción)">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Cerrar
                                    </button>
                                    @endcan
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        @if(empty($filterStatus))
                            No hay presupuestos de ingreso para el año {{ $filterYear }}
                        @else
                            No hay presupuestos con estado "{{ $filterStatus }}"
                        @endif
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Tabla de Ingresos Registrados -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-green-50 to-white">
                <h2 class="text-lg font-semibold text-gray-900">Ingresos Registrados</h2>
                <p class="text-sm text-gray-500">Historial de recaudos realizados</p>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rubro / Fuente</th>
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
                            @if($income->fundingSource->budgetItem)
                                <div class="text-xs text-gray-500">{{ $income->fundingSource->budgetItem->code }}</div>
                            @endif
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $income->fundingSource->type_color }}">
                                {{ $income->fundingSource->name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize">
                            {{ $income->payment_method ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-green-600">
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
                    {{-- Rubro --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rubro Presupuestal *</label>
                        <select wire:model.live="budget_item_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Seleccione un rubro --</option>
                            @foreach($budgetItems as $item)
                                <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                            @endforeach
                        </select>
                        @error('budget_item_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Fuente de Financiación --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fuente de Financiación *</label>
                        @if(empty($budget_item_id))
                            <div class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-500">
                                <svg class="w-4 h-4 inline mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Primero seleccione un rubro para ver sus fuentes
                            </div>
                        @elseif(count($fundingSources) === 0)
                            <div class="w-full px-4 py-3 bg-yellow-50 border border-yellow-200 rounded-xl text-sm text-yellow-700">
                                <svg class="w-4 h-4 inline mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Este rubro no tiene presupuesto de ingreso para {{ $filterYear }}.
                            </div>
                        @else
                            <select wire:model.live="funding_source_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">-- Seleccione una fuente --</option>
                                @foreach($fundingSources as $source)
                                    <option value="{{ $source['id'] }}">
                                        {{ $source['name'] }} 
                                        (Pend: ${{ number_format($source['pending'], 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        @error('funding_source_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Info del Presupuesto Seleccionado --}}
                    @if($selectedBudgetInfo)
                    <div class="p-4 bg-blue-50 rounded-xl border border-blue-100">
                        <h4 class="text-sm font-semibold text-blue-800 mb-2">Información del Presupuesto</h4>
                        <div class="grid grid-cols-3 gap-2 text-sm">
                            <div>
                                <p class="text-xs text-blue-600">Presupuestado</p>
                                <p class="font-bold text-blue-900">${{ number_format($selectedBudgetInfo['budgeted'], 0, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-green-600">Ya Recaudado</p>
                                <p class="font-bold text-green-700">${{ number_format($selectedBudgetInfo['collected'], 0, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-orange-600">Pendiente</p>
                                <p class="font-bold {{ $selectedBudgetInfo['pending'] > 0 ? 'text-orange-700' : 'text-purple-700' }}">
                                    ${{ number_format(abs($selectedBudgetInfo['pending']), 0, ',', '.') }}
                                    @if($selectedBudgetInfo['pending'] < 0) <span class="text-xs">(exceso)</span> @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre / Concepto *</label>
                        <input type="text" wire:model="name" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ej: Recaudo SGP Enero">
                        @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Descripción opcional..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto del Ingreso *</label>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">$</span>
                                <input type="number" wire:model.live.debounce.500ms="amount" step="0.01" min="0" class="flex-1 rounded-r-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="0.00">
                            </div>
                            @error('amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                            <input type="date" wire:model="date" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Alerta de Ajuste Presupuestal --}}
                    @if($showAdjustmentWarning && $adjustmentType)
                    <div class="p-4 rounded-xl border {{ $adjustmentType === 'addition' ? 'bg-green-50 border-green-200' : 'bg-orange-50 border-orange-200' }}">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                @if($adjustmentType === 'addition')
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                                @else
                                <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                                </svg>
                                @endif
                            </div>
                            <div>
                                <h4 class="font-semibold {{ $adjustmentType === 'addition' ? 'text-green-800' : 'text-orange-800' }}">
                                    @if($adjustmentType === 'addition')
                                        ¡Se realizará una ADICIÓN al presupuesto!
                                    @else
                                        ¡Se realizará una REDUCCIÓN al presupuesto!
                                    @endif
                                </h4>
                                <p class="text-sm {{ $adjustmentType === 'addition' ? 'text-green-700' : 'text-orange-700' }} mt-1">
                                    El ingreso de <strong>${{ number_format($amount, 0, ',', '.') }}</strong> 
                                    @if($adjustmentType === 'addition')
                                        excede el monto pendiente de ${{ number_format($selectedBudgetInfo['pending'], 0, ',', '.') }}.
                                    @else
                                        es menor al monto pendiente de ${{ number_format($selectedBudgetInfo['pending'], 0, ',', '.') }}.
                                    @endif
                                </p>
                                <p class="text-sm font-medium {{ $adjustmentType === 'addition' ? 'text-green-800' : 'text-orange-800' }} mt-2">
                                    Se {{ $adjustmentType === 'addition' ? 'adicionarán' : 'reducirán' }} 
                                    <strong>${{ number_format($adjustmentAmount, 0, ',', '.') }}</strong> 
                                    al presupuesto automáticamente.
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif

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
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium">
                            @if($showAdjustmentWarning && $adjustmentType)
                                {{ $isEditing ? 'Actualizar' : 'Guardar' }} con {{ $adjustmentType === 'addition' ? 'Adición' : 'Reducción' }}
                            @else
                                {{ $isEditing ? 'Actualizar' : 'Guardar' }}
                            @endif
                        </button>
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
                    <div class="mt-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                        <p class="text-sm text-yellow-700">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <strong>Nota:</strong> Si este ingreso generó un ajuste presupuestal, deberá ajustar el presupuesto manualmente.
                        </p>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeDeleteModal" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-xl font-medium">Cancelar</button>
                    <button type="button" wire:click="delete" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Marcar como Completado -->
    @if($showCompleteModal && $budgetToComplete)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeCompleteModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-md">
                <div class="bg-gradient-to-r from-orange-500 to-orange-400 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white">Cerrar Recaudo</h3>
                        <button type="button" wire:click="closeCompleteModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <div class="mb-4">
                        <p class="text-sm text-gray-500">Rubro</p>
                        <p class="font-semibold text-gray-900">{{ $budgetToComplete['budget_item_code'] }} - {{ $budgetToComplete['budget_item_name'] }}</p>
                    </div>
                    <div class="mb-4">
                        <p class="text-sm text-gray-500">Fuente</p>
                        <p class="font-semibold text-gray-900">{{ $budgetToComplete['funding_source_code'] }} - {{ $budgetToComplete['funding_source_name'] }}</p>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-3 mb-4 p-3 bg-gray-50 rounded-xl">
                        <div class="text-center">
                            <p class="text-xs text-gray-500">Presupuestado</p>
                            <p class="font-bold text-gray-900">${{ number_format($budgetToComplete['budgeted'], 0, ',', '.') }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-green-600">Recaudado</p>
                            <p class="font-bold text-green-600">${{ number_format($budgetToComplete['collected'], 0, ',', '.') }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-orange-600">Faltante</p>
                            <p class="font-bold text-orange-600">${{ number_format($budgetToComplete['pending'], 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="p-4 bg-orange-50 rounded-xl border border-orange-200">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-orange-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <p class="font-semibold text-orange-800">Se creará una REDUCCIÓN presupuestal</p>
                                <p class="text-sm text-orange-700 mt-1">
                                    Al marcar como completo, se reducirá el presupuesto en 
                                    <strong>${{ number_format($budgetToComplete['pending'], 0, ',', '.') }}</strong>
                                    porque no se espera recibir más ingresos de esta fuente.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeCompleteModal" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-xl font-medium">Cancelar</button>
                    <button type="button" wire:click="markAsComplete" class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-medium">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Marcar Completo
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Adiciones y Reducciones</h1>
                <p class="text-gray-500 mt-1">Gestión de adiciones y reducciones presupuestales</p>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Presupuesto Inicial</p>
                        <p class="text-xl font-bold text-gray-900 mt-1">${{ number_format($this->totals['total_initial'], 0, ',', '.') }}</p>
                    </div>
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Total Adiciones</p>
                        <p class="text-xl font-bold text-green-600 mt-1">${{ number_format($this->totals['total_additions'], 0, ',', '.') }}</p>
                    </div>
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Total Reducciones</p>
                        <p class="text-xl font-bold text-orange-600 mt-1">${{ number_format($this->totals['total_reductions'], 0, ',', '.') }}</p>
                    </div>
                    <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Presupuesto Actual</p>
                        <p class="text-xl font-bold text-gray-900 mt-1">${{ number_format($this->totals['total_current'], 0, ',', '.') }}</p>
                    </div>
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border-gray-300" placeholder="Código o nombre de rubro, fuente...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                    <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                        @foreach($this->availableYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Budgets Table (Grouped) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">Presupuestos</h2>
                <p class="text-sm text-gray-500">Seleccione un presupuesto para aplicar adición o reducción (se aplica a ingreso y gasto simultáneamente)</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rubro</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fuente</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto Inicial</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Adiciones</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Reducciones</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto Actual</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->groupedBudgets as $group)
                        @php
                            $incomeBudget = $group['income'];
                            $expenseBudget = $group['expense'];
                            // Usar el de ingreso como referencia para los valores
                            $ref = $incomeBudget ?? $expenseBudget;
                        @endphp
                        @if($ref)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $group['budget_item']->code ?? '' }}</div>
                                <div class="text-xs text-gray-500">{{ $group['budget_item']->name ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $group['funding_source']->name ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $group['funding_source']->code ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-600">
                                ${{ number_format($ref->initial_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium text-green-600">
                                @if($ref->total_additions > 0)
                                    +${{ number_format($ref->total_additions, 0, ',', '.') }}
                                @else
                                    <span class="text-gray-400">$0</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium text-orange-600">
                                @if($ref->total_reductions > 0)
                                    -${{ number_format($ref->total_reductions, 0, ',', '.') }}
                                @else
                                    <span class="text-gray-400">$0</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">
                                ${{ number_format($ref->current_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($incomeBudget && $expenseBudget)
                                <div class="flex items-center justify-end gap-1">
                                    @can('budget_modifications.create')
                                    <button wire:click="openModal({{ $incomeBudget->id }}, {{ $expenseBudget->id }}, 'addition')" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Adición">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                    <button wire:click="openModal({{ $incomeBudget->id }}, {{ $expenseBudget->id }}, 'reduction')" class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg" title="Reducción">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                    </button>
                                    @endcan
                                    <button wire:click="openHistoryModal({{ $incomeBudget->id }}, {{ $expenseBudget->id }})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Historial">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">No hay presupuestos registrados para este período</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($this->groupedBudgets->hasPages())
            <div class="px-6 py-4 border-t">{{ $this->groupedBudgets->links() }}</div>
            @endif
        </div>

        <!-- Recent Modifications -->
        @if($this->modificationHistory->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">Últimas Modificaciones</h2>
                <p class="text-sm text-gray-500">Historial reciente de adiciones y reducciones</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rubro</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Observación</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registrado por</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($this->modificationHistory as $mod)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $mod->type_color }}">{{ $mod->formatted_number }}</span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $mod->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $mod->type_color }}">{{ $mod->type_name }}</span>
                            </td>
                            <td class="px-6 py-3">
                                <div class="text-sm text-gray-900">{{ $mod->budget->budgetItem->code ?? '' }} - {{ $mod->budget->budgetItem->name ?? '' }}</div>
                                <div class="text-xs text-gray-500">{{ $mod->budget->fundingSource->name ?? '' }}</div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-semibold {{ $mod->type === 'addition' ? 'text-green-600' : 'text-orange-600' }}">
                                {{ $mod->type === 'addition' ? '+' : '-' }}${{ number_format($mod->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600 max-w-xs truncate" title="{{ $mod->reason }}">{{ \Illuminate\Support\Str::limit($mod->reason, 40) }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-600">{{ $mod->creator->name ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Modal Adición/Reducción -->
    @if($showModal && count($selectedBudgetInfo) > 0)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-2xl">
                <!-- Header -->
                <div class="bg-gradient-to-r {{ $operationType === 'addition' ? 'from-green-600 to-green-500' : 'from-orange-600 to-orange-500' }} px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white">
                                {{ $operationType === 'addition' ? 'Adición Presupuestal' : 'Reducción Presupuestal' }}
                            </h3>
                            <p class="text-white/80 text-sm mt-1">
                                {{ $operationType === 'addition' ? 'Incrementar el monto del presupuesto (ingreso y gasto)' : 'Disminuir el monto del presupuesto (ingreso y gasto)' }}
                            </p>
                        </div>
                        <button type="button" wire:click="closeModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <form wire:submit="save" class="px-6 py-5 space-y-5">
                    <!-- Info del presupuesto seleccionado -->
                    <div class="p-4 bg-blue-50 rounded-xl border border-blue-100">
                        <h4 class="text-sm font-semibold text-blue-700 mb-2">Presupuesto Seleccionado</h4>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-gray-500">Rubro:</span>
                                <span class="font-medium text-gray-900 ml-1">{{ $selectedBudgetInfo['budget_item_code'] }} - {{ $selectedBudgetInfo['budget_item_name'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Fuente:</span>
                                <span class="font-medium text-gray-900 ml-1">{{ $selectedBudgetInfo['funding_source_name'] }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Monto Inicial:</span>
                                <span class="font-medium text-gray-900 ml-1">${{ number_format($selectedBudgetInfo['initial_amount'], 0, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Aplica a:</span>
                                <div class="flex gap-1 mt-0.5">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-700">Ingreso</span>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-700">Gasto</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-blue-200 flex items-center justify-between">
                            <span class="text-sm text-gray-600">Monto Actual:</span>
                            <span class="text-lg font-bold text-blue-700">${{ number_format($selectedBudgetInfo['current_amount'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Alerta sobre distribuciones afectadas -->
                    @if(count($affectedDistributions) > 0)
                    <div class="p-4 bg-amber-50 rounded-xl border border-amber-200">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-amber-800">Distribuciones de gasto asociadas</h4>
                                <p class="text-xs text-amber-700 mt-1">
                                    Este presupuesto tiene <strong>{{ count($affectedDistributions) }}</strong> distribución(es) de gasto.
                                    {{ $operationType === 'reduction' ? 'La reducción no puede dejar el presupuesto por debajo del total distribuido.' : 'La adición incrementará el saldo disponible del presupuesto.' }}
                                </p>
                                <div class="mt-2 space-y-1">
                                    @foreach($affectedDistributions as $dist)
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-600">{{ $dist['expense_code_code'] }} - {{ $dist['expense_code'] }}</span>
                                        <span class="font-medium text-gray-900">${{ number_format($dist['amount'], 0, ',', '.') }}</span>
                                    </div>
                                    @endforeach
                                    <div class="flex justify-between text-xs font-bold pt-1 border-t border-amber-200">
                                        <span class="text-amber-800">Total Distribuido</span>
                                        <span class="text-amber-800">${{ number_format(collect($affectedDistributions)->sum('amount'), 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Monto -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Monto a {{ $operationType === 'addition' ? 'Adicionar' : 'Reducir' }} *
                        </label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">$</span>
                            <input type="number" wire:model="amount" step="0.01" min="0.01" class="flex-1 rounded-r-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="0.00">
                        </div>
                        @error('amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Observación -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observación *</label>
                        <textarea wire:model="reason" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ingrese la observación o justificación del movimiento..."></textarea>
                        @error('reason') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Documento -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número de Documento <span class="text-gray-400 text-xs">(Opcional)</span></label>
                        <input type="text" wire:model="document_number" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ej: RES-001-2024">
                    </div>

                    <!-- Preview del resultado -->
                    @if(count($selectedBudgetInfo) > 0 && $amount && is_numeric($amount) && $amount > 0)
                    <div class="p-4 {{ $operationType === 'addition' ? 'bg-green-50 border-green-100' : 'bg-orange-50 border-orange-100' }} rounded-xl border">
                        <h4 class="text-sm font-semibold {{ $operationType === 'addition' ? 'text-green-700' : 'text-orange-700' }} mb-2">Vista previa del movimiento</h4>
                        <div class="flex items-center justify-between text-sm">
                            <div>
                                <span class="text-gray-600">Monto Actual:</span>
                                <span class="font-medium ml-1">${{ number_format($selectedBudgetInfo['current_amount'], 0, ',', '.') }}</span>
                            </div>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            <div>
                                <span class="text-gray-600">Nuevo Monto:</span>
                                <span class="font-bold ml-1 {{ $operationType === 'addition' ? 'text-green-700' : 'text-orange-700' }}">
                                    ${{ number_format($operationType === 'addition' ? $selectedBudgetInfo['current_amount'] + (float)$amount : max(0, $selectedBudgetInfo['current_amount'] - (float)$amount), 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2 italic">* Este movimiento se aplicará tanto al presupuesto de ingreso como al de gasto.</p>
                    </div>
                    @endif

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium">Cancelar</button>
                        <button type="submit" class="px-4 py-2 {{ $operationType === 'addition' ? 'bg-green-600 hover:bg-green-700' : 'bg-orange-600 hover:bg-orange-700' }} text-white rounded-xl font-medium">
                            {{ $operationType === 'addition' ? 'Registrar Adición' : 'Registrar Reducción' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Historial -->
    @if($showHistoryModal && $historyIncomeBudget)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeHistoryModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-2xl">
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white">Historial de Modificaciones</h3>
                            <p class="text-blue-100 text-sm mt-1">{{ $historyIncomeBudget->budgetItem->code ?? '' }} - {{ $historyIncomeBudget->budgetItem->name ?? '' }}</p>
                        </div>
                        <button type="button" wire:click="closeHistoryModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <!-- Budget info -->
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="text-center p-3 bg-blue-50 rounded-xl">
                            <p class="text-xs text-gray-500">Monto Inicial</p>
                            <p class="text-sm font-bold text-blue-700">${{ number_format($historyIncomeBudget->initial_amount, 0, ',', '.') }}</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-xl">
                            <p class="text-xs text-gray-500">Fuente</p>
                            <p class="text-sm font-medium text-gray-900">{{ $historyIncomeBudget->fundingSource->name ?? 'N/A' }}</p>
                        </div>
                        <div class="text-center p-3 bg-indigo-50 rounded-xl">
                            <p class="text-xs text-gray-500">Monto Actual</p>
                            <p class="text-sm font-bold text-indigo-700">${{ number_format($historyIncomeBudget->current_amount, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <!-- Modifications list -->
                    @if($historyIncomeBudget->modifications->count() > 0)
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @foreach($historyIncomeBudget->modifications as $mod)
                        <div class="p-3 {{ $mod->type === 'addition' ? 'bg-green-50 border-green-100' : 'bg-orange-50 border-orange-100' }} rounded-xl border">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $mod->type_color }}">{{ $mod->type_name }}</span>
                                    <span class="text-xs text-gray-500">#{{ $mod->formatted_number }}</span>
                                </div>
                                <span class="text-lg font-bold {{ $mod->type === 'addition' ? 'text-green-600' : 'text-orange-600' }}">
                                    {{ $mod->type === 'addition' ? '+' : '-' }}${{ number_format($mod->amount, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex gap-2 text-xs text-gray-500 mb-1">
                                <span>${{ number_format($mod->previous_amount, 0, ',', '.') }}</span>
                                <span>→</span>
                                <span class="font-medium">${{ number_format($mod->new_amount, 0, ',', '.') }}</span>
                            </div>
                            <p class="text-xs text-gray-600">{{ $mod->reason }}</p>
                            <div class="flex justify-between mt-2 text-xs text-gray-400">
                                <span>{{ $mod->creator->name ?? 'N/A' }}</span>
                                <span>{{ $mod->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @if($mod->document_number)
                            <div class="text-xs text-gray-500 mt-1">Doc: {{ $mod->document_number }}</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p>No hay modificaciones registradas</p>
                    </div>
                    @endif
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button type="button" wire:click="closeHistoryModal" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-xl font-medium">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

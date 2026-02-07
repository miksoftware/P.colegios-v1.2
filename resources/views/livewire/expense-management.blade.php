<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Gestión de Gastos</h1>
                <p class="text-gray-500 mt-1">Distribución de presupuesto de gastos</p>
            </div>
        </div>

        {{-- Resumen General --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Presupuestado</p>
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($this->summary['budgeted'], 0, ',', '.') }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-xl">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Distribuido</p>
                        <p class="text-2xl font-bold text-purple-600">${{ number_format($this->summary['distributed'], 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $this->summary['distribution_percentage'] }}% del presupuesto</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-xl">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Sin Distribuir</p>
                        <p class="text-2xl font-bold text-orange-600">${{ number_format($this->summary['available'], 0, ',', '.') }}</p>
                    </div>
                    <div class="p-3 bg-orange-100 rounded-xl">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border-gray-300" placeholder="Rubro o fuente...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año Fiscal</label>
                    <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                        @for($y = date('Y') + 1; $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rubro</label>
                    <select wire:model.live="filterBudgetItem" class="w-full rounded-xl border-gray-300">
                        <option value="">Todos</option>
                        @foreach($this->budgetItems as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button wire:click="clearFilters" class="w-full px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Limpiar
                    </button>
                </div>
            </div>
        </div>

        {{-- Tabla de Presupuestos de Gasto --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Presupuestos de Gasto</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rubro / Fuente</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Presupuestado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Distribuido</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Progreso</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->expenseBudgets as $budget)
                            @php
                                $distributed = $budget->distributions->sum('amount');
                                $availableToDistribute = $budget->current_amount - $distributed;
                                $distributionPct = $budget->current_amount > 0 ? round(($distributed / $budget->current_amount) * 100, 1) : 0;
                            @endphp
                            <tr class="hover:bg-gray-50" wire:key="budget-{{ $budget->id }}">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $budget->budgetItem?->name ?? 'Sin rubro' }}</div>
                                    <div class="text-sm text-gray-500">{{ $budget->fundingSource?->name ?? 'Sin fuente' }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-semibold text-gray-900">${{ number_format($budget->current_amount, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-medium text-purple-600">${{ number_format($distributed, 0, ',', '.') }}</span>
                                    @if($availableToDistribute > 0)
                                        <div class="text-xs text-gray-400">Disponible: ${{ number_format($availableToDistribute, 0, ',', '.') }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-purple-500 h-2 rounded-full" style="width: {{ min($distributionPct, 100) }}%"></div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1 text-center">
                                        <span>{{ $distributionPct }}% distribuido</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button wire:click="openDetailModal({{ $budget->id }})" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg" title="Ver detalle">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        @can('expenses.distribute')
                                            @if($availableToDistribute > 0)
                                                <button wire:click="openDistributeModal({{ $budget->id }})" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg" title="Distribuir">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            {{-- Distribuciones del presupuesto --}}
                            @foreach($budget->distributions as $distribution)
                                <tr class="bg-gray-50/50" wire:key="dist-{{ $distribution->id }}">
                                    <td class="px-6 py-3 pl-12">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-400">└</span>
                                            <div>
                                                <span class="font-mono text-xs text-blue-600">{{ $distribution->expenseCode?->code }}</span>
                                                <div class="text-sm text-gray-700">{{ Str::limit($distribution->expenseCode?->name, 50) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm text-gray-600">
                                        ${{ number_format($distribution->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm text-gray-500">-</td>
                                    <td class="px-6 py-3 text-sm text-center text-gray-400">-</td>
                                    <td class="px-6 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            @can('precontractual.create')
                                                <a href="{{ route('precontractual.index', ['distribution_id' => $distribution->id]) }}" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Iniciar Precontractual">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                </a>
                                            @endcan
                                            @can('expenses.delete')
                                                <button wire:click="confirmDeleteDistribution({{ $distribution->id }})" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg" title="Eliminar distribución">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <p class="mt-2">No hay presupuestos de gasto para este período</p>
                                    <p class="text-sm text-gray-400 mt-1">Cree un presupuesto tipo "Gasto" en el módulo de Presupuestos</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($this->expenseBudgets->hasPages())
                <div class="px-6 py-4 border-t">{{ $this->expenseBudgets->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Modal Distribuir --}}
    @if($showDistributeModal && $selectedBudget)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeDistributeModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-lg">
                <form wire:submit="saveDistribution">
                    <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
                        <h3 class="text-lg font-bold text-purple-900">Distribuir Presupuesto</h3>
                        <p class="text-sm text-purple-700">{{ $selectedBudget->budgetItem?->name }} - {{ $selectedBudget->fundingSource?->name }}</p>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        {{-- Info del presupuesto --}}
                        <div class="bg-gray-50 rounded-xl p-4 grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Presupuestado:</span>
                                <span class="font-semibold text-gray-900 ml-2">${{ number_format($selectedBudget->current_amount, 0, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Ya distribuido:</span>
                                <span class="font-semibold text-purple-600 ml-2">${{ number_format($selectedBudget->distributions->sum('amount'), 0, ',', '.') }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-gray-500">Disponible para distribuir:</span>
                                <span class="font-bold text-green-600 ml-2">${{ number_format($selectedBudget->current_amount - $selectedBudget->distributions->sum('amount'), 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código de Gasto <span class="text-red-500">*</span></label>
                            <select wire:model="distributeExpenseCodeId" class="w-full rounded-xl border-gray-300">
                                <option value="">Seleccionar código...</option>
                                @foreach($this->expenseCodes as $code)
                                    <option value="{{ $code->id }}">{{ $code->code }} - {{ Str::limit($code->name, 60) }}</option>
                                @endforeach
                            </select>
                            @error('distributeExpenseCodeId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto a Distribuir <span class="text-red-500">*</span></label>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-300 bg-gray-50 text-gray-500">$</span>
                                <input type="number" wire:model="distributeAmount" step="0.01" min="0.01" class="flex-1 rounded-r-xl border-gray-300" placeholder="0.00">
                            </div>
                            @error('distributeAmount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción (opcional)</label>
                            <textarea wire:model="distributeDescription" rows="2" class="w-full rounded-xl border-gray-300" placeholder="Notas adicionales..."></textarea>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                        <button type="button" wire:click="closeDistributeModal" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700">Distribuir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Detalle --}}
    @if($showDetailModal && $detailBudget)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeDetailModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-4xl max-h-[80vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Detalle de Presupuesto de Gasto</h3>
                            <p class="text-sm text-gray-500">{{ $detailBudget->budgetItem?->name }} - {{ $detailBudget->fundingSource?->name }}</p>
                        </div>
                        <button wire:click="closeDetailModal" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                
                <div class="p-6">
                    {{-- Resumen --}}
                    @php
                        $totalDist = $detailBudget->distributions->sum('amount');
                    @endphp
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-blue-50 rounded-xl p-4 text-center">
                            <p class="text-xs text-blue-600 uppercase">Presupuestado</p>
                            <p class="text-xl font-bold text-blue-700">${{ number_format($detailBudget->current_amount, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-purple-50 rounded-xl p-4 text-center">
                            <p class="text-xs text-purple-600 uppercase">Distribuido</p>
                            <p class="text-xl font-bold text-purple-700">${{ number_format($totalDist, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-orange-50 rounded-xl p-4 text-center">
                            <p class="text-xs text-orange-600 uppercase">Sin Distribuir</p>
                            <p class="text-xl font-bold text-orange-700">${{ number_format($detailBudget->current_amount - $totalDist, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    {{-- Distribuciones --}}
                    @forelse($detailBudget->distributions as $dist)
                        <div class="border rounded-xl mb-4 overflow-hidden">
                            <div class="bg-gray-50 px-4 py-3 flex justify-between items-center">
                                <div>
                                    <span class="font-mono text-sm text-blue-600">{{ $dist->expenseCode?->code }}</span>
                                    <span class="text-gray-700 ml-2">{{ Str::limit($dist->expenseCode?->name, 60) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-semibold">${{ number_format($dist->amount, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            @if($dist->description)
                                <div class="px-4 py-3 text-sm text-gray-600">
                                    {{ $dist->description }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-center text-gray-500 py-8">No hay distribuciones para este presupuesto</p>
                    @endforelse
                </div>
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
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 text-center mb-2">
                        Eliminar Distribución
                    </h3>
                    <p class="text-sm text-gray-500 text-center">¿Estás seguro? Esta acción no se puede deshacer.</p>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                    <button type="button" wire:click="closeDeleteModal" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl">Cancelar</button>
                    <button type="button" wire:click="delete" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

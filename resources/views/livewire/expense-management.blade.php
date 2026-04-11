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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Presupuestado</p>
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($this->summary['budgeted'], 2, ',', '.') }}</p>
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
                        <p class="text-2xl font-bold text-purple-600">${{ number_format($this->summary['distributed'], 2, ',', '.') }}</p>
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
                        <p class="text-sm text-gray-500">Ejecutado (Pagado)</p>
                        <p class="text-2xl font-bold text-emerald-600">${{ number_format($this->summary['paid'], 2, ',', '.') }}</p>
                    </div>
                    <div class="p-3 bg-emerald-100 rounded-xl">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Sin Distribuir</p>
                        <p class="text-2xl font-bold text-orange-600">${{ number_format($this->summary['available'], 2, ',', '.') }}</p>
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
                                <td class="px-6 py-4 text-right" x-data="{ open: false }">
                                    <div class="relative">
                                        <button type="button" @click="open = !open" class="font-semibold text-gray-900 hover:text-blue-600 cursor-pointer inline-flex items-center gap-1">
                                            ${{ number_format($budget->current_amount, 2, ',', '.') }}
                                            @if($budget->current_amount != $budget->initial_amount)
                                                <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @endif
                                        </button>
                                        @if($budget->initial_amount != $budget->current_amount)
                                            <div class="text-[10px] text-gray-400">Inicial: ${{ number_format($budget->initial_amount, 2, ',', '.') }}</div>
                                        @endif
                                        {{-- Popover de desglose --}}
                                        <div x-show="open" @click.away="open = false" x-transition
                                            class="absolute right-0 top-full mt-1 z-50 bg-white border border-gray-200 rounded-xl shadow-2xl p-4 w-80 text-left" style="background-color: white;">
                                            <p class="text-xs font-semibold text-gray-700 mb-2 border-b pb-1">Desglose Presupuestal</p>
                                            <div class="space-y-1.5 text-xs">
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">Monto Inicial</span>
                                                    <span class="font-medium">${{ number_format($budget->initial_amount, 2, ',', '.') }}</span>
                                                </div>
                                                @php
                                                    $additions = $budget->modifications->where('type', 'addition');
                                                    $reductions = $budget->modifications->where('type', 'reduction');
                                                    $totalAdditions = $additions->sum('amount');
                                                    $totalReductions = $reductions->sum('amount');
                                                    $totalCreditos = $budget->incomingTransfers->sum('amount');
                                                    $totalContracreditos = $budget->outgoingTransfers->sum('amount');
                                                @endphp
                                                @if($totalAdditions > 0)
                                                    <div class="flex justify-between text-green-700">
                                                        <span>+ Adiciones ({{ $additions->count() }})</span>
                                                        <span class="font-medium">+ ${{ number_format($totalAdditions, 2, ',', '.') }}</span>
                                                    </div>
                                                    @foreach($additions as $mod)
                                                        <div class="flex justify-between pl-3 text-green-600/80">
                                                            <span class="truncate mr-2">{{ Str::limit($mod->reason, 35) }}</span>
                                                            <span>+ ${{ number_format($mod->amount, 2, ',', '.') }}</span>
                                                        </div>
                                                    @endforeach
                                                @endif
                                                @if($totalReductions > 0)
                                                    <div class="flex justify-between text-red-700">
                                                        <span>- Reducciones ({{ $reductions->count() }})</span>
                                                        <span class="font-medium">- ${{ number_format($totalReductions, 2, ',', '.') }}</span>
                                                    </div>
                                                    @foreach($reductions as $mod)
                                                        <div class="flex justify-between pl-3 text-red-600/80">
                                                            <span class="truncate mr-2">{{ Str::limit($mod->reason, 35) }}</span>
                                                            <span>- ${{ number_format($mod->amount, 2, ',', '.') }}</span>
                                                        </div>
                                                    @endforeach
                                                @endif
                                                @if($totalCreditos > 0)
                                                    <div class="flex justify-between text-blue-700">
                                                        <span>+ Créditos ({{ $budget->incomingTransfers->count() }})</span>
                                                        <span class="font-medium">+ ${{ number_format($totalCreditos, 2, ',', '.') }}</span>
                                                    </div>
                                                    @foreach($budget->incomingTransfers as $tr)
                                                        <div class="flex justify-between pl-3 text-blue-600/80">
                                                            <span class="truncate mr-2">Desde: {{ $tr->sourceBudget?->budgetItem?->name ?? '?' }} / {{ $tr->sourceBudget?->fundingSource?->name ?? '?' }}</span>
                                                            <span>+ ${{ number_format($tr->amount, 2, ',', '.') }}</span>
                                                        </div>
                                                    @endforeach
                                                @endif
                                                @if($totalContracreditos > 0)
                                                    <div class="flex justify-between text-orange-700">
                                                        <span>- Contracréditos ({{ $budget->outgoingTransfers->count() }})</span>
                                                        <span class="font-medium">- ${{ number_format($totalContracreditos, 2, ',', '.') }}</span>
                                                    </div>
                                                    @foreach($budget->outgoingTransfers as $tr)
                                                        <div class="flex justify-between pl-3 text-orange-600/80">
                                                            <span class="truncate mr-2">Hacia: {{ $tr->destinationBudget?->budgetItem?->name ?? '?' }} / {{ $tr->destinationBudget?->fundingSource?->name ?? '?' }}</span>
                                                            <span>- ${{ number_format($tr->amount, 2, ',', '.') }}</span>
                                                        </div>
                                                    @endforeach
                                                @endif
                                                <div class="flex justify-between pt-1.5 border-t font-semibold text-gray-900">
                                                    <span>Monto Actual</span>
                                                    <span>${{ number_format($budget->current_amount, 2, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-medium text-purple-600">${{ number_format($distributed, 2, ',', '.') }}</span>
                                    @if($availableToDistribute > 0)
                                        <div class="text-xs text-gray-400">Disponible: ${{ number_format($availableToDistribute, 2, ',', '.') }}</div>
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
                                @php
                                    $distCommitted = $distribution->total_committed;
                                    $distPaid = $distribution->total_paid;
                                    $distLocked = $distribution->total_locked;
                                    $distAvailable = $distribution->available_balance;
                                @endphp
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
                                        ${{ number_format($distribution->amount, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm" x-data="{ showDetail: false }">
                                        <div class="relative">
                                            @if($distPaid > 0 || $distCommitted > 0)
                                                <button type="button" @click="showDetail = !showDetail" class="text-right hover:opacity-80 cursor-pointer">
                                                    @if($distPaid > 0)
                                                        <span class="text-emerald-600 font-medium">${{ number_format($distPaid, 2, ',', '.') }}</span>
                                                        <div class="text-[10px] text-gray-400">Pagado</div>
                                                    @endif
                                                    @if($distCommitted > 0 && $distCommitted > $distPaid)
                                                        <span class="text-amber-600 {{ $distPaid > 0 ? 'text-xs' : '' }}">${{ number_format($distCommitted - $distPaid, 2, ',', '.') }}</span>
                                                        <div class="text-[10px] text-gray-400">Comprometido</div>
                                                    @endif
                                                </button>
                                                {{-- Popover detalle compromisos --}}
                                                <div x-show="showDetail" @click.away="showDetail = false" x-transition
                                                    class="absolute right-0 top-full mt-1 z-50 bg-white border border-gray-200 rounded-xl shadow-2xl p-4 w-96 text-left" style="background-color: white;"
                                                    <p class="text-xs font-semibold text-gray-700 mb-2 border-b pb-1">Detalle de Compromisos</p>
                                                    <div class="space-y-2 text-xs max-h-60 overflow-y-auto">
                                                        @php
                                                            $convDistributions = $distribution->convocatoriaDistributions ?? collect();
                                                            $paymentLines = $distribution->paymentOrderLines ?? collect();
                                                        @endphp
                                                        @forelse($convDistributions as $cd)
                                                            @php
                                                                $conv = $cd->convocatoria;
                                                                if (!$conv || $conv->status === 'cancelled') continue;
                                                                $contract = $conv->contract ?? null;
                                                                $paidForConv = $paymentLines
                                                                    ->filter(fn($line) => $line->paymentOrder && in_array($line->paymentOrder->status, ['draft', 'approved', 'paid']) && $line->paymentOrder->contract && $line->paymentOrder->contract->convocatoria_id == $conv->id)
                                                                    ->sum('total');
                                                            @endphp
                                                            <div class="border rounded-lg p-2 {{ $contract && $contract->status === 'annulled' ? 'opacity-50' : '' }}">
                                                                <div class="flex justify-between items-start">
                                                                    <div>
                                                                        <span class="font-semibold text-indigo-600">Conv. #{{ $conv->formatted_number }}</span>
                                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium {{ $conv->status_color }} ml-1">{{ $conv->status_name }}</span>
                                                                    </div>
                                                                    <span class="font-medium text-amber-700">${{ number_format($cd->amount, 2, ',', '.') }}</span>
                                                                </div>
                                                                <div class="text-[10px] text-gray-500 mt-0.5 truncate">{{ Str::limit($conv->object, 60) }}</div>
                                                                @if($contract)
                                                                    <div class="mt-1 pl-2 border-l-2 border-blue-200">
                                                                        <div class="flex justify-between">
                                                                            <span class="text-blue-700 font-medium">Contrato #{{ $contract->formatted_number }}</span>
                                                                            <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ match($contract->status) { 'active' => 'bg-blue-100 text-blue-700', 'in_execution' => 'bg-yellow-100 text-yellow-700', 'completed' => 'bg-green-100 text-green-700', 'annulled' => 'bg-red-100 text-red-700', default => 'bg-gray-100 text-gray-700' } }}">{{ $contract->status_name }}</span>
                                                                        </div>
                                                                        <div class="text-[10px] text-gray-500">{{ $contract->supplier?->full_name ?? '' }}</div>
                                                                        @if($paidForConv > 0)
                                                                            <div class="text-[10px] text-emerald-600 font-medium mt-0.5">Pagado: ${{ number_format($paidForConv, 2, ',', '.') }}</div>
                                                                        @endif
                                                                    </div>
                                                                @else
                                                                    <div class="text-[10px] text-gray-400 mt-0.5 italic">Sin contrato aún</div>
                                                                @endif
                                                            </div>
                                                        @empty
                                                            <p class="text-gray-400 italic">Sin convocatorias asociadas</p>
                                                        @endforelse
                                                        {{-- Resumen --}}
                                                        <div class="border-t pt-2 mt-2 space-y-1">
                                                            <div class="flex justify-between font-medium">
                                                                <span class="text-gray-600">Total comprometido</span>
                                                                <span class="text-amber-700">${{ number_format($distCommitted, 2, ',', '.') }}</span>
                                                            </div>
                                                            @if($distPaid > 0)
                                                                <div class="flex justify-between font-medium">
                                                                    <span class="text-gray-600">Total pagado</span>
                                                                    <span class="text-emerald-700">${{ number_format($distPaid, 2, ',', '.') }}</span>
                                                                </div>
                                                            @endif
                                                            <div class="flex justify-between font-medium">
                                                                <span class="text-gray-600">Disponible</span>
                                                                <span class="{{ $distAvailable > 0 ? 'text-green-700' : 'text-gray-400' }}">${{ number_format($distAvailable, 2, ',', '.') }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-center">
                                        @if($distAvailable > 0)
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">${{ number_format($distAvailable, 2, ',', '.') }} disp.</span>
                                        @elseif($distAvailable <= 0 && $distribution->amount > 0)
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Agotado</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
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
                                <span class="font-semibold text-gray-900 ml-2">${{ number_format($selectedBudget->current_amount, 2, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Ya distribuido:</span>
                                <span class="font-semibold text-purple-600 ml-2">${{ number_format($selectedBudget->distributions->sum('amount'), 2, ',', '.') }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-gray-500">Disponible para distribuir:</span>
                                <span class="font-bold text-green-600 ml-2">${{ number_format($selectedBudget->current_amount - $selectedBudget->distributions->sum('amount'), 2, ',', '.') }}</span>
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
                        $dAdditions = $detailBudget->modifications->where('type', 'addition');
                        $dReductions = $detailBudget->modifications->where('type', 'reduction');
                        $dTotalAdditions = $dAdditions->sum('amount');
                        $dTotalReductions = $dReductions->sum('amount');
                        $dTotalCreditos = $detailBudget->incomingTransfers->sum('amount');
                        $dTotalContracreditos = $detailBudget->outgoingTransfers->sum('amount');
                        $hasMovements = $detailBudget->initial_amount != $detailBudget->current_amount;
                    @endphp
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-blue-50 rounded-xl p-4 text-center">
                            <p class="text-xs text-blue-600 uppercase">Presupuestado</p>
                            <p class="text-xl font-bold text-blue-700">${{ number_format($detailBudget->current_amount, 2, ',', '.') }}</p>
                            @if($hasMovements)
                                <p class="text-[10px] text-blue-500 mt-0.5">Inicial: ${{ number_format($detailBudget->initial_amount, 2, ',', '.') }}</p>
                            @endif
                        </div>
                        <div class="bg-purple-50 rounded-xl p-4 text-center">
                            <p class="text-xs text-purple-600 uppercase">Distribuido</p>
                            <p class="text-xl font-bold text-purple-700">${{ number_format($totalDist, 2, ',', '.') }}</p>
                        </div>
                        <div class="bg-orange-50 rounded-xl p-4 text-center">
                            <p class="text-xs text-orange-600 uppercase">Sin Distribuir</p>
                            <p class="text-xl font-bold text-orange-700">${{ number_format($detailBudget->current_amount - $totalDist, 2, ',', '.') }}</p>
                        </div>
                    </div>

                    {{-- Desglose de movimientos --}}
                    @if($hasMovements)
                        <div class="bg-gray-50 rounded-xl p-4 mb-6">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Movimientos Presupuestales</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Monto Inicial</span>
                                    <span class="font-medium">${{ number_format($detailBudget->initial_amount, 2, ',', '.') }}</span>
                                </div>
                                @if($dTotalAdditions > 0)
                                    <div class="border-t pt-2">
                                        <div class="flex justify-between text-green-700 font-medium">
                                            <span>Adiciones</span>
                                            <span>+ ${{ number_format($dTotalAdditions, 2, ',', '.') }}</span>
                                        </div>
                                        @foreach($dAdditions as $mod)
                                            <div class="flex justify-between pl-4 text-xs text-green-600 mt-1">
                                                <span>{{ $mod->reason }} <span class="text-gray-400">({{ $mod->document_date?->format('d/m/Y') ?? $mod->created_at->format('d/m/Y') }})</span></span>
                                                <span>+ ${{ number_format($mod->amount, 2, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if($dTotalReductions > 0)
                                    <div class="border-t pt-2">
                                        <div class="flex justify-between text-red-700 font-medium">
                                            <span>Reducciones</span>
                                            <span>- ${{ number_format($dTotalReductions, 2, ',', '.') }}</span>
                                        </div>
                                        @foreach($dReductions as $mod)
                                            <div class="flex justify-between pl-4 text-xs text-red-600 mt-1">
                                                <span>{{ $mod->reason }} <span class="text-gray-400">({{ $mod->document_date?->format('d/m/Y') ?? $mod->created_at->format('d/m/Y') }})</span></span>
                                                <span>- ${{ number_format($mod->amount, 2, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if($dTotalCreditos > 0)
                                    <div class="border-t pt-2">
                                        <div class="flex justify-between text-blue-700 font-medium">
                                            <span>Créditos (traslados entrantes)</span>
                                            <span>+ ${{ number_format($dTotalCreditos, 2, ',', '.') }}</span>
                                        </div>
                                        @foreach($detailBudget->incomingTransfers as $tr)
                                            <div class="flex justify-between pl-4 text-xs text-blue-600 mt-1">
                                                <span>Desde: {{ $tr->sourceBudget?->budgetItem?->name }} / {{ $tr->sourceBudget?->fundingSource?->name }} <span class="text-gray-400">({{ $tr->document_date?->format('d/m/Y') ?? $tr->created_at->format('d/m/Y') }})</span></span>
                                                <span>+ ${{ number_format($tr->amount, 2, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if($dTotalContracreditos > 0)
                                    <div class="border-t pt-2">
                                        <div class="flex justify-between text-orange-700 font-medium">
                                            <span>Contracréditos (traslados salientes)</span>
                                            <span>- ${{ number_format($dTotalContracreditos, 2, ',', '.') }}</span>
                                        </div>
                                        @foreach($detailBudget->outgoingTransfers as $tr)
                                            <div class="flex justify-between pl-4 text-xs text-orange-600 mt-1">
                                                <span>Hacia: {{ $tr->destinationBudget?->budgetItem?->name }} / {{ $tr->destinationBudget?->fundingSource?->name }} <span class="text-gray-400">({{ $tr->document_date?->format('d/m/Y') ?? $tr->created_at->format('d/m/Y') }})</span></span>
                                                <span>- ${{ number_format($tr->amount, 2, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="flex justify-between pt-2 border-t font-bold text-gray-900">
                                    <span>Monto Actual</span>
                                    <span>${{ number_format($detailBudget->current_amount, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Distribuciones --}}
                    @forelse($detailBudget->distributions as $dist)
                        @php
                            $dCommitted = $dist->total_committed;
                            $dPaid = $dist->total_paid;
                            $dLocked = $dist->total_locked;
                            $dAvailable = $dist->available_balance;
                        @endphp
                        <div class="border rounded-xl mb-4 overflow-hidden">
                            <div class="bg-gray-50 px-4 py-3 flex justify-between items-center">
                                <div>
                                    <span class="font-mono text-sm text-blue-600">{{ $dist->expenseCode?->code }}</span>
                                    <span class="text-gray-700 ml-2">{{ Str::limit($dist->expenseCode?->name, 60) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-semibold">${{ number_format($dist->amount, 2, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="px-4 py-3 grid grid-cols-4 gap-3 text-center text-sm">
                                <div>
                                    <p class="text-xs text-amber-600">Comprometido</p>
                                    <p class="font-semibold text-amber-700">${{ number_format($dCommitted, 2, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-emerald-600">Pagado</p>
                                    <p class="font-semibold text-emerald-700">${{ number_format($dPaid, 2, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-red-600">Bloqueado</p>
                                    <p class="font-semibold text-red-700">${{ number_format($dLocked, 2, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-blue-600">Disponible</p>
                                    <p class="font-semibold {{ $dAvailable > 0 ? 'text-blue-700' : 'text-gray-400' }}">${{ number_format($dAvailable, 2, ',', '.') }}</p>
                                </div>
                            </div>
                            {{-- Detalle de convocatorias/contratos --}}
                            @php
                                $detailConvDists = $dist->convocatoriaDistributions ?? collect();
                                $detailPayLines = $dist->paymentOrderLines ?? collect();
                                $activeConvDists = $detailConvDists->filter(fn($cd) => $cd->convocatoria && $cd->convocatoria->status !== 'cancelled');
                            @endphp
                            @if($activeConvDists->count() > 0)
                                <div class="px-4 py-3 border-t bg-gray-50/50">
                                    <p class="text-xs font-semibold text-gray-600 mb-2">Convocatorias / Contratos</p>
                                    <div class="space-y-2">
                                        @foreach($activeConvDists as $cd)
                                            @php
                                                $conv = $cd->convocatoria;
                                                $contract = $conv->contract ?? null;
                                                $paidForConv = $detailPayLines
                                                    ->filter(fn($line) => $line->paymentOrder && in_array($line->paymentOrder->status, ['draft', 'approved', 'paid']) && $line->paymentOrder->contract && $line->paymentOrder->contract->convocatoria_id == $conv->id)
                                                    ->sum('total');
                                            @endphp
                                            <div class="flex items-start justify-between text-xs bg-white rounded-lg p-2 border {{ $contract && $contract->status === 'annulled' ? 'opacity-50' : '' }}">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="font-semibold text-indigo-600">Conv. #{{ $conv->formatted_number }}</span>
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium {{ $conv->status_color }}">{{ $conv->status_name }}</span>
                                                    </div>
                                                    <div class="text-[10px] text-gray-500 truncate mt-0.5">{{ Str::limit($conv->object, 80) }}</div>
                                                    @if($contract)
                                                        <div class="flex items-center gap-1.5 mt-1">
                                                            <span class="text-blue-700 font-medium">→ Contrato #{{ $contract->formatted_number }}</span>
                                                            <span class="text-[10px] text-gray-500">{{ $contract->supplier?->full_name ?? '' }}</span>
                                                        </div>
                                                        @if($paidForConv > 0)
                                                            <div class="text-[10px] text-emerald-600 font-medium">Pagado: ${{ number_format($paidForConv, 2, ',', '.') }}</div>
                                                        @endif
                                                    @endif
                                                </div>
                                                <span class="font-semibold text-amber-700 ml-2 whitespace-nowrap">${{ number_format($cd->amount, 2, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if($dist->description)
                                <div class="px-4 py-2 border-t text-sm text-gray-600">
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

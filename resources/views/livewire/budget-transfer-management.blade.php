<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Créditos y Contracréditos</h1>
                <p class="text-gray-500 mt-1">Traslados presupuestales entre códigos de gasto por fuente de financiación</p>
            </div>
            @can('budget_transfers.create')
            <button wire:click="openCreateModal" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo Traslado
            </button>
            @endcan
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="Número, documento, rubro...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                    <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        @foreach($this->availableYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Traslados Registrados</h2>
                    <p class="text-sm text-gray-500">Historial de créditos y contracréditos entre códigos de gasto</p>
                </div>
                <div class="text-xs text-gray-400 font-medium">
                    Total: {{ $this->transfers->total() }} traslados
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origen (Contracrédito)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Destino (Crédito)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fuente Financiación</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($this->transfers as $transfer)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">#{{ $transfer->formatted_number }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $transfer->document_date ? $transfer->document_date->format('d/m/Y') : $transfer->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 bg-red-100 text-red-600 rounded-full flex items-center justify-center flex-shrink-0" title="Contracrédito (sale dinero)">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                    </span>
                                    <div>
                                        @if($transfer->sourceExpenseDistribution)
                                        <div class="text-sm font-medium text-gray-900">{{ $transfer->sourceExpenseDistribution->expenseCode->code ?? '' }}</div>
                                        <div class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($transfer->sourceExpenseDistribution->expenseCode->name ?? '', 30) }}</div>
                                        @else
                                        <div class="text-sm font-medium text-gray-900">{{ $transfer->sourceBudget->budgetItem->code ?? '' }}</div>
                                        <div class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($transfer->sourceBudget->budgetItem->name ?? '', 30) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center flex-shrink-0" title="Crédito (entra dinero)">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                    </span>
                                    <div>
                                        @if($transfer->destinationExpenseDistribution)
                                        <div class="text-sm font-medium text-gray-900">{{ $transfer->destinationExpenseDistribution->expenseCode->code ?? '' }}</div>
                                        <div class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($transfer->destinationExpenseDistribution->expenseCode->name ?? '', 30) }}</div>
                                        @else
                                        <div class="text-sm font-medium text-gray-900">{{ $transfer->destinationBudget->budgetItem->code ?? '' }}</div>
                                        <div class="text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($transfer->destinationBudget->budgetItem->name ?? '', 30) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($transfer->sourceFundingSource)
                                <div class="text-xs text-gray-800 font-medium">{{ $transfer->sourceFundingSource->code ?? '' }} - {{ \Illuminate\Support\Str::limit($transfer->sourceFundingSource->name ?? '', 20) }}</div>
                                @if($transfer->destination_funding_source_id && $transfer->destination_funding_source_id !== $transfer->source_funding_source_id)
                                    <div class="text-[11px] text-blue-600 font-medium mt-0.5">Dest: {{ $transfer->destinationFundingSource->code ?? '' }} - {{ \Illuminate\Support\Str::limit($transfer->destinationFundingSource->name ?? '', 18) }}</div>
                                @endif
                                @else
                                <span class="text-xs text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                ${{ number_format($transfer->amount, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="showDetail({{ $transfer->id }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Ver detalle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    @can('budget_transfers.edit')
                                    <button wire:click="openEditModal({{ $transfer->id }})" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Editar traslado">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    @endcan
                                    @can('budget_transfers.delete')
                                    <button wire:click="confirmDelete({{ $transfer->id }})" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar traslado">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">No hay traslados registrados para el año seleccionado</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($this->transfers->hasPages())<div class="px-6 py-4 border-t border-gray-100">{{ $this->transfers->links() }}</div>@endif
        </div>
    </div>

    <!-- ======================================================
         MODAL CREAR TRASLADO
    ====================================================== -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-2xl sm:my-8 w-full max-w-2xl">
                <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                                <svg class="w-6 h-6 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                Nuevo Traslado Presupuestal
                            </h3>
                            <p class="text-blue-100 text-xs mt-1">Crédito y Contracrédito entre códigos de gasto</p>
                        </div>
                        <button type="button" wire:click="closeModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <form wire:submit="save" class="px-6 py-5 space-y-5 max-h-[78vh] overflow-y-auto">

                    <!-- Origen (Contracrédito) -->
                    <div class="p-4 bg-red-50/70 rounded-xl border border-red-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-red-800 flex items-center gap-2">
                                <span class="w-5 h-5 bg-red-200 text-red-700 rounded-full flex items-center justify-center text-xs">1</span>
                                Contracrédito (Origen - Sale Dinero)
                            </h4>
                            <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-medium">Disminuye saldo</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Fuente de Financiación Origen *</label>
                            <select wire:model.live="selected_funding_source_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                                <option value="">Seleccione una fuente de financiación...</option>
                                @foreach($availableFundingSources as $fs)
                                    <option value="{{ $fs['id'] }}">{{ $fs['name'] }}</option>
                                @endforeach
                            </select>
                            @error('selected_funding_source_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        @if($selected_funding_source_id)
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Código de Gasto Origen *</label>
                            <select wire:model.live="source_distribution_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                                <option value="">Seleccione un gasto con saldo disponible...</option>
                                @foreach($sourceDistributions as $dist)
                                    <option value="{{ $dist['id'] }}">{{ $dist['name'] }} — Disponible: ${{ number_format($dist['available_balance'], 2, ',', '.') }}</option>
                                @endforeach
                            </select>
                            @error('source_distribution_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        @if(empty($sourceDistributions))
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg">
                            <p class="text-xs text-amber-700">No hay códigos de gasto con saldo disponible para esta fuente de financiación.</p>
                        </div>
                        @endif

                        @if(count($selectedSourceInfo) > 0)
                        <div class="pt-3 border-t border-red-200/80 grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-gray-500 block">Rubro presupuestal:</span>
                                <span class="font-medium text-gray-800">{{ $selectedSourceInfo['rubro'] ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Saldo disponible actual:</span>
                                <span class="font-bold text-red-700 text-sm">${{ number_format($selectedSourceInfo['available_balance'], 2, ',', '.') }}</span>
                            </div>
                        </div>
                        @endif
                        @endif
                    </div>

                    <!-- Monto -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-1">Monto a Trasladar *</label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 font-bold sm:text-sm">$</span>
                            </div>
                            <input type="number" wire:model.live="amount" step="0.01" min="0.01" class="w-full pl-8 pr-4 py-2.5 rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-base font-bold text-gray-900" placeholder="0.00">
                        </div>
                        @error('amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Destino (Crédito) -->
                    <div class="p-4 bg-green-50/70 rounded-xl border border-green-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-green-800 flex items-center gap-2">
                                <span class="w-5 h-5 bg-green-200 text-green-700 rounded-full flex items-center justify-center text-xs">2</span>
                                Crédito (Destino - Entra Dinero)
                            </h4>
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Aumenta saldo</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Fuente de Financiación Destino *</label>
                            <select wire:model.live="destination_funding_source_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="">Seleccione fuente destino...</option>
                                @foreach($destinationFundingSources as $dfs)
                                    <option value="{{ $dfs['id'] }}">{{ $dfs['name'] }}</option>
                                @endforeach
                            </select>
                            @error('destination_funding_source_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Código de Gasto Destino *</label>
                            <select wire:model.live="destination_expense_code_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="">Seleccione código destino...</option>
                                @foreach($destinationExpenseCodes as $ec)
                                    <option value="{{ $ec['id'] }}">{{ $ec['name'] }}</option>
                                @endforeach
                            </select>
                            @error('destination_expense_code_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        @if(count($selectedDestinationInfo) > 0)
                        <div class="pt-3 border-t border-green-200/80 flex items-center justify-between text-xs">
                            <span class="text-gray-600">Estado en destino:</span>
                            @if($selectedDestinationInfo['is_new'] ?? false)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                    Nuevo rubro para esta fuente (Nace en $0)
                                </span>
                            @else
                                <span class="font-bold text-green-700 text-sm">
                                    Monto actual: ${{ number_format($selectedDestinationInfo['current_amount'], 2, ',', '.') }}
                                </span>
                            @endif
                        </div>
                        @endif
                    </div>

                    <!-- Vista previa en tiempo real -->
                    @if(count($selectedSourceInfo) > 0 && count($selectedDestinationInfo) > 0 && $amount && is_numeric($amount) && $amount > 0)
                    <div class="p-4 bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 rounded-xl border border-blue-200 shadow-sm">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-900 mb-2.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Vista previa del impacto en saldos
                        </h4>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="p-2.5 bg-white/80 rounded-lg border border-red-100">
                                <span class="text-red-600 font-semibold block mb-1">Origen (Contracrédito):</span>
                                <div class="text-gray-500">Asignado: ${{ number_format($selectedSourceInfo['amount'], 2, ',', '.') }}</div>
                                <div class="text-red-700 font-bold mt-1 text-sm">
                                    Quedará: ${{ number_format($selectedSourceInfo['amount'] - (float)$amount, 2, ',', '.') }}
                                </div>
                            </div>
                            <div class="p-2.5 bg-white/80 rounded-lg border border-green-100">
                                <span class="text-green-600 font-semibold block mb-1">Destino (Crédito):</span>
                                <div class="text-gray-500">Asignado: ${{ number_format($selectedDestinationInfo['current_amount'], 2, ',', '.') }}</div>
                                <div class="text-green-700 font-bold mt-1 text-sm">
                                    Quedará: ${{ number_format($selectedDestinationInfo['current_amount'] + (float)$amount, 2, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Justificación -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Justificación del Traslado *</label>
                        <textarea wire:model="reason" rows="2" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Motivo o necesidad del traslado presupuestal..."></textarea>
                        @error('reason') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Documento y Fecha -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nº Documento Soporte <span class="text-gray-400 text-xs">(Opcional)</span></label>
                            <input type="text" wire:model="document_number" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Ej: ACUERDO-003">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Realización *</label>
                            <input type="date" wire:model="document_date" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            @error('document_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium transition-colors">Cancelar</button>
                        <button type="submit" class="px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold shadow-md shadow-blue-500/20 transition-all">Registrar Traslado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- ======================================================
         MODAL EDITAR TRASLADO
    ====================================================== -->
    @if($showEditModal && $editingTransfer)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 backdrop-blur-sm transition-opacity" wire:click="closeEditModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-2xl sm:my-8 w-full max-w-2xl border border-gray-100">
                <div class="bg-gradient-to-r from-amber-600 via-orange-600 to-amber-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                                <svg class="w-6 h-6 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Editar Traslado #{{ $editingTransfer->formatted_number }}
                            </h3>
                            <p class="text-amber-100 text-xs mt-1">Ajuste valores, fuentes de origen y destino o datos de soporte</p>
                        </div>
                        <button type="button" wire:click="closeEditModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <form wire:submit="update" class="px-6 py-5 space-y-5 max-h-[78vh] overflow-y-auto">

                    <!-- Origen (Contracrédito) -->
                    <div class="p-4 bg-red-50/70 rounded-xl border border-red-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-red-800 flex items-center gap-2">
                                <span class="w-5 h-5 bg-red-200 text-red-700 rounded-full flex items-center justify-center text-xs">1</span>
                                Origen (Contracrédito - Sale Dinero)
                            </h4>
                            <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-medium">Disminuye saldo</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Fuente de Financiación Origen *</label>
                            <select wire:model.live="edit_source_funding_source_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                                <option value="">Seleccione fuente de origen...</option>
                                @foreach($editAvailableFundingSources as $fs)
                                    <option value="{{ $fs['id'] }}">{{ $fs['name'] }}</option>
                                @endforeach
                            </select>
                            @error('edit_source_funding_source_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Código de Gasto Origen *</label>
                            <select wire:model.live="edit_source_distribution_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                                <option value="">Seleccione gasto origen...</option>
                                @foreach($editSourceDistributions as $dist)
                                    <option value="{{ $dist['id'] }}">{{ $dist['name'] }} — Disponible: ${{ number_format($dist['available_balance'], 2, ',', '.') }} @if($dist['is_original']) (Origen Actual) @endif</option>
                                @endforeach
                            </select>
                            @error('edit_source_distribution_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        @if(count($editSelectedSourceInfo) > 0)
                        <div class="pt-3 border-t border-red-200/80 grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-gray-500 block">Rubro:</span>
                                <span class="font-medium text-gray-800">{{ $editSelectedSourceInfo['rubro'] ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Capacidad disponible:</span>
                                <span class="font-bold text-red-700 text-sm">${{ number_format($editSelectedSourceInfo['available_balance'], 2, ',', '.') }}</span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Monto a Trasladar -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-1">Monto del Traslado ($) *</label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 font-bold sm:text-sm">$</span>
                            </div>
                            <input type="number" wire:model.live="edit_amount" step="0.01" min="0.01" class="w-full pl-8 pr-4 py-2.5 rounded-xl border-gray-300 focus:border-amber-500 focus:ring-amber-500 text-base font-bold text-gray-900" placeholder="0.00">
                        </div>
                        @error('edit_amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-500 mt-1">Monto original registrado: <span class="font-semibold text-gray-700">${{ number_format($editingTransfer->amount, 2, ',', '.') }}</span></p>
                    </div>

                    <!-- Destino (Crédito) -->
                    <div class="p-4 bg-green-50/70 rounded-xl border border-green-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-green-800 flex items-center gap-2">
                                <span class="w-5 h-5 bg-green-200 text-green-700 rounded-full flex items-center justify-center text-xs">2</span>
                                Destino (Crédito - Entra Dinero)
                            </h4>
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Aumenta saldo</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Fuente de Financiación Destino *</label>
                            <select wire:model.live="edit_destination_funding_source_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="">Seleccione fuente destino...</option>
                                @foreach($editDestinationFundingSources as $dfs)
                                    <option value="{{ $dfs['id'] }}">{{ $dfs['name'] }}</option>
                                @endforeach
                            </select>
                            @error('edit_destination_funding_source_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Código de Gasto Destino *</label>
                            <select wire:model.live="edit_destination_expense_code_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="">Seleccione código destino...</option>
                                @foreach($editDestinationExpenseCodes as $ec)
                                    <option value="{{ $ec['id'] }}">{{ $ec['name'] }}</option>
                                @endforeach
                            </select>
                            @error('edit_destination_expense_code_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        @if(count($editSelectedDestinationInfo) > 0)
                        <div class="pt-3 border-t border-green-200/80 flex items-center justify-between text-xs">
                            <span class="text-gray-600">Estado en destino:</span>
                            @if($editSelectedDestinationInfo['is_new'] ?? false)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                    Nuevo rubro para esta fuente
                                </span>
                            @else
                                <span class="font-bold text-green-700 text-sm">
                                    Monto base: ${{ number_format($editSelectedDestinationInfo['base_amount'] ?? $editSelectedDestinationInfo['current_amount'], 2, ',', '.') }}
                                    @if($editSelectedDestinationInfo['is_original']) <span class="text-xs text-gray-500 font-normal">(Destino actual)</span> @endif
                                </span>
                            @endif
                        </div>
                        @endif
                    </div>

                    <!-- Vista previa en tiempo real -->
                    @if(count($editSelectedSourceInfo) > 0 && count($editSelectedDestinationInfo) > 0 && $edit_amount && is_numeric($edit_amount) && $edit_amount > 0)
                    @php
                        $origAmt = (float)$editingTransfer->amount;
                        $newAmt = (float)$edit_amount;
                        $sourceBase = (float)$editSelectedSourceInfo['amount'] + ($editSelectedSourceInfo['is_original'] ? $origAmt : 0);
                        $sourceProjected = $sourceBase - $newAmt;
                        $destBase = (float)($editSelectedDestinationInfo['base_amount'] ?? 0);
                        $destProjected = $destBase + $newAmt;
                    @endphp
                    <div class="p-4 bg-gradient-to-r from-amber-50 via-orange-50 to-amber-100/50 rounded-xl border border-amber-200 shadow-sm">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-amber-900 mb-2.5 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            Proyección de saldos con la edición
                        </h4>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="p-2.5 bg-white/90 rounded-lg border border-red-100">
                                <span class="text-red-600 font-semibold block mb-1">Gasto Origen:</span>
                                <div class="text-gray-500">Saldo base: ${{ number_format($sourceBase, 2, ',', '.') }}</div>
                                <div class="text-red-700 font-bold mt-1 text-sm">
                                    Nuevo saldo: ${{ number_format($sourceProjected, 2, ',', '.') }}
                                </div>
                            </div>
                            <div class="p-2.5 bg-white/90 rounded-lg border border-green-100">
                                <span class="text-green-600 font-semibold block mb-1">Gasto Destino:</span>
                                <div class="text-gray-500">Saldo base: ${{ number_format($destBase, 2, ',', '.') }}</div>
                                <div class="text-green-700 font-bold mt-1 text-sm">
                                    Nuevo saldo: ${{ number_format($destProjected, 2, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Justificación -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Justificación del Traslado *</label>
                        <textarea wire:model="edit_reason" rows="2" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm" placeholder="Razón del traslado..."></textarea>
                        @error('edit_reason') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Documento y Fecha -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nº Documento Soporte <span class="text-gray-400 text-xs">(Opcional)</span></label>
                            <input type="text" wire:model="edit_document_number" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Realización *</label>
                            <input type="date" wire:model="edit_document_date" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm">
                            @error('edit_document_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" wire:click="closeEditModal" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium transition-colors">Cancelar</button>
                        <button type="submit" class="px-5 py-2 text-sm bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-semibold shadow-md shadow-amber-500/20 transition-all">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- ======================================================
         MODAL ELIMINAR TRASLADO
    ====================================================== -->
    @if($showDeleteModal && $transferToDelete)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 backdrop-blur-sm transition-opacity" wire:click="closeDeleteModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-2xl sm:my-16 w-full max-w-lg border border-gray-100">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900">¿Eliminar Traslado #{{ $transferToDelete->formatted_number }}?</h3>
                            <p class="text-sm text-gray-500 mt-1">Esta acción revertirá los saldos modificados por este traslado presupuestal.</p>
                        </div>
                    </div>

                    <!-- Resumen del impacto de la reversión -->
                    <div class="mt-5 p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-3 text-sm">
                        <div class="flex justify-between items-center pb-2 border-b border-gray-200">
                            <span class="text-gray-500">Monto del traslado:</span>
                            <span class="font-bold text-gray-900 text-base">${{ number_format($transferToDelete->amount, 2, ',', '.') }}</span>
                        </div>

                        <div class="text-xs space-y-2">
                            <div class="flex items-start justify-between">
                                <span class="text-gray-600">Gasto Origen (Recuperará):</span>
                                <span class="font-semibold text-green-700 text-right">+${{ number_format($transferToDelete->amount, 2, ',', '.') }}</span>
                            </div>
                            <div class="text-gray-500 text-[11px]">
                                {{ $transferToDelete->sourceExpenseDistribution?->expenseCode?->code ?? '' }} - {{ $transferToDelete->sourceExpenseDistribution?->expenseCode?->name ?? '' }}
                            </div>

                            <div class="flex items-start justify-between pt-2 border-t border-gray-200">
                                <span class="text-gray-600">Gasto Destino (Se descontará):</span>
                                <span class="font-semibold text-red-700 text-right">-${{ number_format($transferToDelete->amount, 2, ',', '.') }}</span>
                            </div>
                            <div class="text-gray-500 text-[11px]">
                                {{ $transferToDelete->destinationExpenseDistribution?->expenseCode?->code ?? '' }} - {{ $transferToDelete->destinationExpenseDistribution?->expenseCode?->name ?? '' }}
                            </div>
                        </div>
                    </div>

                    <!-- Alerta si no se puede eliminar -->
                    @if($deleteWarning)
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-xl flex items-start gap-2.5">
                        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-xs text-red-700 leading-relaxed">{{ $deleteWarning }}</p>
                    </div>
                    @endif
                </div>

                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-100">
                    <button type="button" wire:click="closeDeleteModal" class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-xl font-medium transition-colors">
                        Cancelar
                    </button>
                    @if(!$deleteWarning)
                    <button type="button" wire:click="delete" class="px-5 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold shadow-md shadow-red-500/20 transition-all">
                        Sí, Eliminar y Revertir
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- ======================================================
         MODAL DETALLE DE TRASLADO
    ====================================================== -->
    @if($showDetailModal && $detailTransfer)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 backdrop-blur-sm transition-opacity" wire:click="closeDetailModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-2xl sm:my-8 w-full max-w-lg border border-gray-100">
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <span>Traslado #{{ $detailTransfer->formatted_number }}</span>
                        </h3>
                        <button type="button" wire:click="closeDetailModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-sm text-gray-500">Fecha de Registro</span>
                        <span class="text-sm font-medium">{{ $detailTransfer->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-sm text-gray-500">Monto</span>
                        <span class="text-lg font-bold text-blue-600">${{ number_format($detailTransfer->amount, 2, ',', '.') }}</span>
                    </div>

                    <!-- Fuente de financiación -->
                    @if($detailTransfer->sourceFundingSource)
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-sm text-gray-500">Fuente Origen</span>
                        <span class="text-sm font-medium">{{ $detailTransfer->sourceFundingSource->code ?? '' }} - {{ $detailTransfer->sourceFundingSource->name ?? '' }}</span>
                    </div>
                    @endif

                    @if($detailTransfer->destination_funding_source_id && $detailTransfer->destination_funding_source_id !== $detailTransfer->source_funding_source_id)
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-sm text-gray-500">Fuente Destino</span>
                        <span class="text-sm font-medium text-blue-600">{{ $detailTransfer->destinationFundingSource->code ?? '' }} - {{ $detailTransfer->destinationFundingSource->name ?? '' }}</span>
                    </div>
                    @endif

                    <!-- Origen -->
                    <div class="p-3 bg-red-50 rounded-xl border border-red-100">
                        <p class="text-xs font-semibold text-red-700 mb-1">Origen (Contracrédito)</p>
                        @if($detailTransfer->sourceExpenseDistribution)
                        <p class="text-sm font-medium text-gray-900">{{ $detailTransfer->sourceExpenseDistribution->expenseCode->code ?? '' }} - {{ $detailTransfer->sourceExpenseDistribution->expenseCode->name ?? '' }}</p>
                        <p class="text-xs text-gray-500">Rubro: {{ $detailTransfer->sourceBudget->budgetItem->code ?? '' }} - {{ $detailTransfer->sourceBudget->budgetItem->name ?? '' }}</p>
                        @else
                        <p class="text-sm font-medium text-gray-900">{{ $detailTransfer->sourceBudget->budgetItem->code ?? '' }} - {{ $detailTransfer->sourceBudget->budgetItem->name ?? '' }}</p>
                        @endif
                        <p class="text-xs text-gray-500">Fuente: {{ $detailTransfer->sourceFundingSource->name ?? 'N/A' }}</p>
                        <div class="flex gap-2 mt-2 text-xs">
                            <span class="text-gray-500">Antes: ${{ number_format($detailTransfer->source_previous_amount, 2, ',', '.') }}</span>
                            <span>→</span>
                            <span class="font-semibold text-red-600">Después: ${{ number_format($detailTransfer->source_new_amount, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Destino -->
                    <div class="p-3 bg-green-50 rounded-xl border border-green-100">
                        <p class="text-xs font-semibold text-green-700 mb-1">Destino (Crédito)</p>
                        @if($detailTransfer->destinationExpenseDistribution)
                        <p class="text-sm font-medium text-gray-900">{{ $detailTransfer->destinationExpenseDistribution->expenseCode->code ?? '' }} - {{ $detailTransfer->destinationExpenseDistribution->expenseCode->name ?? '' }}</p>
                        <p class="text-xs text-gray-500">Rubro: {{ $detailTransfer->destinationBudget->budgetItem->code ?? '' }} - {{ $detailTransfer->destinationBudget->budgetItem->name ?? '' }}</p>
                        @else
                        <p class="text-sm font-medium text-gray-900">{{ $detailTransfer->destinationBudget->budgetItem->code ?? '' }} - {{ $detailTransfer->destinationBudget->budgetItem->name ?? '' }}</p>
                        @endif
                        <p class="text-xs text-gray-500">Fuente: {{ $detailTransfer->destinationFundingSource->name ?? 'N/A' }}</p>
                        <div class="flex gap-2 mt-2 text-xs">
                            <span class="text-gray-500">Antes: ${{ number_format($detailTransfer->destination_previous_amount, 2, ',', '.') }}</span>
                            <span>→</span>
                            <span class="font-semibold text-green-600">Después: ${{ number_format($detailTransfer->destination_new_amount, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="py-2">
                        <p class="text-sm text-gray-500 mb-1">Justificación</p>
                        <p class="text-sm text-gray-900 bg-gray-50 p-2.5 rounded-lg border border-gray-100">{{ $detailTransfer->reason }}</p>
                    </div>

                    @if($detailTransfer->document_number)
                    <div class="flex justify-between py-2 border-t">
                        <span class="text-sm text-gray-500">Documento Soporte</span>
                        <span class="text-sm font-medium text-gray-900">{{ $detailTransfer->document_number }}</span>
                    </div>
                    @endif

                    <!-- Fecha de Realización -->
                    <div class="py-2 border-t">
                        @if($editingTransferId === $detailTransfer->id)
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500 flex-shrink-0">Fecha Realización</span>
                            <input type="date" wire:model="editingTransferDate" class="flex-1 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <button wire:click="saveTransferDate" class="px-2 py-1 bg-blue-600 text-white text-xs rounded-lg">Guardar</button>
                            <button wire:click="cancelEditTransferDate" class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-lg">✕</button>
                        </div>
                        @error('editingTransferDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        @else
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">Fecha Realización</span>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium">{{ $detailTransfer->document_date ? $detailTransfer->document_date->format('d/m/Y') : 'Sin fecha' }}</span>
                                @can('budget_transfers.edit')
                                <button wire:click="startEditTransferDate({{ $detailTransfer->id }}, '{{ $detailTransfer->document_date?->format('Y-m-d') }}')" class="text-blue-500 hover:text-blue-700" title="Editar fecha">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                @endcan
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="flex justify-between py-2 border-t">
                        <span class="text-sm text-gray-500">Registrado por</span>
                        <span class="text-sm font-medium">{{ $detailTransfer->creator->name ?? 'N/A' }}</span>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        @can('budget_transfers.edit')
                        <button type="button" wire:click="openEditModal({{ $detailTransfer->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Editar Traslado
                        </button>
                        @endcan
                        @can('budget_transfers.delete')
                        <button type="button" wire:click="confirmDelete({{ $detailTransfer->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Eliminar Traslado
                        </button>
                        @endcan
                    </div>
                    <button type="button" wire:click="closeDetailModal" class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-xl font-medium transition-colors">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

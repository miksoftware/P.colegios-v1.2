<div>
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Créditos y Contracréditos</h1>
                <p class="text-sm text-gray-500 mt-1">Traslados presupuestales entre rubros de gastos</p>
            </div>
            @can('budget_transfers.create')
                <button 
                    wire:click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-500 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/40 hover:scale-[1.02] transition-all"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuevo Traslado
                </button>
            @endcan
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input 
                        wire:model.live.debounce.300ms="search"
                        type="text" 
                        placeholder="Buscar por número, documento, rubro..."
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    >
                </div>
            </div>
            <div class="w-full sm:w-40">
                <select 
                    wire:model.live="filterYear"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                >
                    @foreach($this->availableYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <button 
                wire:click="clearFilters"
                class="px-4 py-2.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-all"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">#</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Origen (Contracrédito)</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Destino (Crédito)</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Monto</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Responsable</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->transfers as $transfer)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-lg">
                                    {{ $transfer->formatted_number }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $transfer->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-red-100 text-red-600 rounded-full">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                                        </svg>
                                    </span>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $transfer->sourceBudget->budgetItem->code }}</div>
                                        <div class="text-xs text-gray-500">{{ Str::limit($transfer->sourceBudget->budgetItem->name, 30) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-green-100 text-green-600 rounded-full">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                        </svg>
                                    </span>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $transfer->destinationBudget->budgetItem->code }}</div>
                                        <div class="text-xs text-gray-500">{{ Str::limit($transfer->destinationBudget->budgetItem->name, 30) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-bold text-gray-900">${{ number_format($transfer->amount, 2) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $transfer->creator->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button 
                                    wire:click="showDetail({{ $transfer->id }})"
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                    title="Ver detalle"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    <p class="text-gray-500 text-sm">No hay traslados registrados</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->transfers->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $this->transfers->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Crear Traslado -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 overflow-hidden transform transition-all">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-white">Nuevo Traslado Presupuestal</h3>
                            <button wire:click="closeModal" class="text-white/80 hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-6 space-y-6">
                        <!-- Rubro Origen (Contracrédito) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Rubro Origen (Contracrédito) 
                                <span class="text-red-500">*</span>
                            </label>
                            <p class="text-xs text-gray-500 mb-2">De donde saldrá el dinero</p>
                            <select 
                                wire:model.live="source_budget_id"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('source_budget_id') border-red-500 @enderror"
                            >
                                <option value="">Seleccione un rubro...</option>
                                @foreach($sourceBudgets as $budget)
                                    <option value="{{ $budget['id'] }}">
                                        {{ $budget['name'] }} (Saldo: ${{ number_format($budget['current_amount'], 2) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('source_budget_id')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror

                            @if($selectedSourceBudget)
                                <div class="mt-3 p-3 bg-red-50 border border-red-100 rounded-xl">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-red-700">Saldo disponible:</p>
                                            <p class="text-lg font-bold text-red-900">${{ number_format($selectedSourceBudget->current_amount, 2) }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Monto -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Monto a Trasladar
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">$</span>
                                <input 
                                    type="number" 
                                    wire:model="amount"
                                    step="0.01"
                                    min="0.01"
                                    @if($selectedSourceBudget) max="{{ $selectedSourceBudget->current_amount }}" @endif
                                    placeholder="0.00"
                                    class="w-full pl-8 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('amount') border-red-500 @enderror"
                                >
                            </div>
                            @error('amount')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Rubro Destino (Crédito) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Rubro Destino (Crédito)
                                <span class="text-red-500">*</span>
                            </label>
                            <p class="text-xs text-gray-500 mb-2">A donde irá el dinero</p>
                            <select 
                                wire:model="destination_budget_id"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('destination_budget_id') border-red-500 @enderror"
                                @if(!$source_budget_id) disabled @endif
                            >
                                <option value="">{{ $source_budget_id ? 'Seleccione un rubro destino...' : 'Primero seleccione el rubro origen' }}</option>
                                @foreach($destinationBudgets as $budget)
                                    <option value="{{ $budget['id'] }}">
                                        {{ $budget['name'] }} (Saldo actual: ${{ number_format($budget['current_amount'], 2) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('destination_budget_id')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Justificación -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Justificación
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                wire:model="reason"
                                rows="3"
                                placeholder="Describa la razón del traslado..."
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none @error('reason') border-red-500 @enderror"
                            ></textarea>
                            @error('reason')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Número de Documento (Opcional) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Número de Documento
                                <span class="text-gray-400 text-xs font-normal">(Opcional)</span>
                            </label>
                            <input 
                                type="text" 
                                wire:model="document_number"
                                placeholder="Ej: RES-001-2024"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('document_number') border-red-500 @enderror"
                            >
                            @error('document_number')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                        <button 
                            wire:click="closeModal"
                            class="px-5 py-2.5 text-gray-700 font-medium hover:bg-gray-100 rounded-xl transition-all"
                        >
                            Cancelar
                        </button>
                        <button 
                            wire:click="save"
                            wire:loading.attr="disabled"
                            class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-500 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/40 transition-all disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="save">Registrar Traslado</span>
                            <span wire:loading wire:target="save">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Detalle -->
    @if($showDetailModal && $detailTransfer)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" wire:click="closeDetailModal"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden transform transition-all">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-white">Detalle del Traslado #{{ $detailTransfer->formatted_number }}</h3>
                            <button wire:click="closeDetailModal" class="text-white/80 hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-6 space-y-4">
                        <!-- Fecha -->
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-500">Fecha</span>
                            <span class="text-sm font-medium text-gray-900">{{ $detailTransfer->created_at->format('d/m/Y H:i') }}</span>
                        </div>

                        <!-- Monto -->
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-500">Monto Trasladado</span>
                            <span class="text-lg font-bold text-blue-600">${{ number_format($detailTransfer->amount, 2) }}</span>
                        </div>

                        <!-- Origen -->
                        <div class="p-4 bg-red-50 rounded-xl">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                                </svg>
                                <span class="text-sm font-semibold text-red-700">Origen (Contracrédito)</span>
                            </div>
                            <p class="text-sm font-medium text-gray-900">{{ $detailTransfer->sourceBudget->budgetItem->code }} - {{ $detailTransfer->sourceBudget->budgetItem->name }}</p>
                            <div class="flex gap-4 mt-2 text-xs text-gray-600">
                                <span>Antes: ${{ number_format($detailTransfer->source_previous_amount, 2) }}</span>
                                <span>→</span>
                                <span class="font-semibold text-red-600">Después: ${{ number_format($detailTransfer->source_new_amount, 2) }}</span>
                            </div>
                        </div>

                        <!-- Destino -->
                        <div class="p-4 bg-green-50 rounded-xl">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                </svg>
                                <span class="text-sm font-semibold text-green-700">Destino (Crédito)</span>
                            </div>
                            <p class="text-sm font-medium text-gray-900">{{ $detailTransfer->destinationBudget->budgetItem->code }} - {{ $detailTransfer->destinationBudget->budgetItem->name }}</p>
                            <div class="flex gap-4 mt-2 text-xs text-gray-600">
                                <span>Antes: ${{ number_format($detailTransfer->destination_previous_amount, 2) }}</span>
                                <span>→</span>
                                <span class="font-semibold text-green-600">Después: ${{ number_format($detailTransfer->destination_new_amount, 2) }}</span>
                            </div>
                        </div>

                        <!-- Justificación -->
                        <div class="py-2">
                            <span class="text-sm text-gray-500 block mb-1">Justificación</span>
                            <p class="text-sm text-gray-900">{{ $detailTransfer->reason }}</p>
                        </div>

                        <!-- Documento -->
                        @if($detailTransfer->document_number)
                            <div class="flex justify-between items-center py-2 border-t border-gray-100">
                                <span class="text-sm text-gray-500">Documento</span>
                                <span class="text-sm font-medium text-gray-900">{{ $detailTransfer->document_number }}</span>
                            </div>
                        @endif

                        <!-- Responsable -->
                        <div class="flex justify-between items-center py-2 border-t border-gray-100">
                            <span class="text-sm text-gray-500">Registrado por</span>
                            <span class="text-sm font-medium text-gray-900">{{ $detailTransfer->creator->name ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                        <button 
                            wire:click="closeDetailModal"
                            class="px-5 py-2.5 text-gray-700 font-medium hover:bg-gray-100 rounded-xl transition-all"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

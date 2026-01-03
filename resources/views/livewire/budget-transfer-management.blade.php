<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Créditos y Contracréditos</h1>
                <p class="text-gray-500 mt-1">Traslados presupuestales entre fuentes de financiación</p>
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
                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border-gray-300" placeholder="Número, documento, rubro...">
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

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origen</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Destino</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($this->transfers as $transfer)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">{{ $transfer->formatted_number }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $transfer->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 bg-red-100 text-red-600 rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                </span>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $transfer->sourceFundingSource->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ $transfer->sourceFundingSource->budgetItem->code ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                </span>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $transfer->destinationFundingSource->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ $transfer->destinationFundingSource->budgetItem->code ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                            ${{ number_format($transfer->amount, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="showDetail({{ $transfer->id }})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Ver detalle">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">No hay traslados registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($this->transfers->hasPages())<div class="px-6 py-4 border-t">{{ $this->transfers->links() }}</div>@endif
        </div>
    </div>

    <!-- Modal Crear -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-2xl">
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white">Nuevo Traslado Presupuestal</h3>
                            <p class="text-blue-100 text-sm mt-1">Crédito y Contracrédito</p>
                        </div>
                        <button type="button" wire:click="closeModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <form wire:submit="save" class="px-6 py-5 space-y-5">
                    <!-- Origen -->
                    <div class="p-4 bg-red-50 rounded-xl border border-red-100">
                        <h4 class="text-sm font-semibold text-red-700 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                            Origen (Contracrédito)
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rubro *</label>
                                <select wire:model.live="source_budget_item_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Seleccione...</option>
                                    @foreach($budgetItems as $item)
                                        <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('source_budget_item_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fuente *</label>
                                <select wire:model.live="source_funding_source_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" @if(empty($sourceFundingSources)) disabled @endif>
                                    <option value="">{{ empty($sourceFundingSources) ? 'Seleccione rubro primero' : 'Seleccione...' }}</option>
                                    @foreach($sourceFundingSources as $source)
                                        <option value="{{ $source['id'] }}">{{ $source['name'] }} (${{ number_format($source['balance'], 0, ',', '.') }})</option>
                                    @endforeach
                                </select>
                                @error('source_funding_source_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @if($selectedSourceFundingSource)
                        <div class="mt-3 text-sm">
                            <span class="text-gray-600">Saldo disponible:</span>
                            <span class="font-bold text-red-700">${{ number_format($selectedSourceFundingSource['balance'], 0, ',', '.') }}</span>
                        </div>
                        @endif
                    </div>

                    <!-- Monto -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Monto a Trasladar *</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">$</span>
                            <input type="number" wire:model="amount" step="0.01" min="0.01" class="flex-1 rounded-r-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="0.00">
                        </div>
                        @error('amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Destino -->
                    <div class="p-4 bg-green-50 rounded-xl border border-green-100">
                        <h4 class="text-sm font-semibold text-green-700 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                            Destino (Crédito)
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Rubro *</label>
                                <select wire:model.live="destination_budget_item_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Seleccione...</option>
                                    @foreach($budgetItems as $item)
                                        <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                                    @endforeach
                                </select>
                                @error('destination_budget_item_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fuente *</label>
                                <select wire:model.live="destination_funding_source_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" @if(empty($destinationFundingSources)) disabled @endif>
                                    <option value="">{{ empty($destinationFundingSources) ? 'Seleccione rubro primero' : 'Seleccione...' }}</option>
                                    @foreach($destinationFundingSources as $source)
                                        <option value="{{ $source['id'] }}">{{ $source['name'] }} (${{ number_format($source['balance'], 0, ',', '.') }})</option>
                                    @endforeach
                                </select>
                                @error('destination_funding_source_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @if($selectedDestinationFundingSource)
                        <div class="mt-3 text-sm">
                            <span class="text-gray-600">Saldo actual:</span>
                            <span class="font-bold text-green-700">${{ number_format($selectedDestinationFundingSource['balance'], 0, ',', '.') }}</span>
                        </div>
                        @endif
                    </div>

                    <!-- Justificación -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Justificación *</label>
                        <textarea wire:model="reason" rows="2" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Razón del traslado..."></textarea>
                        @error('reason') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Documento -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número de Documento <span class="text-gray-400 text-xs">(Opcional)</span></label>
                        <input type="text" wire:model="document_number" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ej: RES-001-2024">
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium">Registrar Traslado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Detalle -->
    @if($showDetailModal && $detailTransfer)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeDetailModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-lg">
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white">Traslado #{{ $detailTransfer->formatted_number }}</h3>
                        <button type="button" wire:click="closeDetailModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-sm text-gray-500">Fecha</span>
                        <span class="text-sm font-medium">{{ $detailTransfer->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-sm text-gray-500">Monto</span>
                        <span class="text-lg font-bold text-blue-600">${{ number_format($detailTransfer->amount, 0, ',', '.') }}</span>
                    </div>

                    <!-- Origen -->
                    <div class="p-3 bg-red-50 rounded-xl">
                        <p class="text-xs font-semibold text-red-700 mb-1">Origen (Contracrédito)</p>
                        <p class="text-sm font-medium text-gray-900">{{ $detailTransfer->sourceFundingSource->name ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500">{{ $detailTransfer->sourceFundingSource->budgetItem->code ?? '' }} - {{ $detailTransfer->sourceFundingSource->budgetItem->name ?? '' }}</p>
                        <div class="flex gap-2 mt-2 text-xs">
                            <span class="text-gray-500">Antes: ${{ number_format($detailTransfer->source_previous_amount, 0, ',', '.') }}</span>
                            <span>→</span>
                            <span class="font-semibold text-red-600">Después: ${{ number_format($detailTransfer->source_new_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Destino -->
                    <div class="p-3 bg-green-50 rounded-xl">
                        <p class="text-xs font-semibold text-green-700 mb-1">Destino (Crédito)</p>
                        <p class="text-sm font-medium text-gray-900">{{ $detailTransfer->destinationFundingSource->name ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500">{{ $detailTransfer->destinationFundingSource->budgetItem->code ?? '' }} - {{ $detailTransfer->destinationFundingSource->budgetItem->name ?? '' }}</p>
                        <div class="flex gap-2 mt-2 text-xs">
                            <span class="text-gray-500">Antes: ${{ number_format($detailTransfer->destination_previous_amount, 0, ',', '.') }}</span>
                            <span>→</span>
                            <span class="font-semibold text-green-600">Después: ${{ number_format($detailTransfer->destination_new_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="py-2">
                        <p class="text-sm text-gray-500 mb-1">Justificación</p>
                        <p class="text-sm text-gray-900">{{ $detailTransfer->reason }}</p>
                    </div>

                    @if($detailTransfer->document_number)
                    <div class="flex justify-between py-2 border-t">
                        <span class="text-sm text-gray-500">Documento</span>
                        <span class="text-sm font-medium">{{ $detailTransfer->document_number }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between py-2 border-t">
                        <span class="text-sm text-gray-500">Registrado por</span>
                        <span class="text-sm font-medium">{{ $detailTransfer->creator->name ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button type="button" wire:click="closeDetailModal" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-xl font-medium">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

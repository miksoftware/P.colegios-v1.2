{{-- Distribución por Código de Gasto (solo flujo contrato) --}}
@if(count($expenseDistributions) > 0)
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        Distribución por Código de Gasto
    </h2>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4">
        <p class="text-xs text-amber-700">
            La convocatoria tiene <span class="font-bold">{{ count($expenseDistributions) }}</span> código(s) de gasto asociado(s).
        </p>
    </div>

    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">¿Cómo desea distribuir el pago?</label>
        <div class="flex items-center gap-6">
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="radio" wire:model.live="paymentMode" value="single" class="text-emerald-600 focus:ring-emerald-500">
                <span class="text-sm text-gray-700">Todo de un solo código de gasto</span>
            </label>
            @if(count($expenseDistributions) > 1)
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="radio" wire:model.live="paymentMode" value="split" class="text-emerald-600 focus:ring-emerald-500">
                <span class="text-sm text-gray-700">Dividir entre códigos de gasto</span>
            </label>
            @endif
        </div>
    </div>

    @if($paymentMode === 'single')
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Seleccione el código de gasto *</label>
            <select wire:model.live="selectedExpenseDistributionId" class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">-- Seleccione --</option>
                @foreach($expenseDistributions as $dist)
                    @php $canCover = (float)$dist['convocatoria_amount'] >= (float)$payTotal; @endphp
                    <option value="{{ $dist['id'] }}" {{ !$canCover ? 'disabled' : '' }}>
                        {{ $dist['expense_code_name'] }} (Asignado: ${{ number_format($dist['convocatoria_amount'], 2, ',', '.') }})
                        {{ !$canCover ? '— Insuficiente' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
    @else
        <div class="space-y-4">
            @foreach($expenseLines as $index => $line)
            <div class="border border-gray-200 rounded-xl p-4 bg-gray-50" wire:key="expense-line-{{ $index }}">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-semibold text-gray-800">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold mr-2">{{ $index + 1 }}</span>
                        {{ $line['expense_code_name'] }}
                    </h3>
                    <span class="text-xs text-gray-500">Asignado: ${{ number_format($line['max_amount'], 2, ',', '.') }}</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Subtotal</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-xs">$</span>
                            <input type="number" wire:model.live.debounce.500ms="expenseLines.{{ $index }}.subtotal" step="0.01" class="w-full rounded-xl border-gray-300 pl-7 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="0">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">IVA</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-xs">$</span>
                            <input type="number" wire:model.live.debounce.500ms="expenseLines.{{ $index }}.iva" step="0.01" class="w-full rounded-xl border-gray-300 pl-7 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="0">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Total Línea</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-xs">$</span>
                            <input type="number" value="{{ $line['total'] }}" class="w-full rounded-xl border-gray-300 pl-7 text-sm bg-gray-100 font-semibold" readonly>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Concepto de Retención</label>
                        <select wire:model.live="expenseLines.{{ $index }}.retention_concept" class="w-full rounded-xl border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">-- Sin retención --</option>
                            @foreach(\App\Models\PaymentOrder::RETENTION_CONCEPTS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">¿Declara renta?</label>
                        <div class="flex items-center gap-4 mt-1">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" wire:model.live="expenseLines.{{ $index }}.supplier_declares_rent" value="0" class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs text-gray-700">No</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" wire:model.live="expenseLines.{{ $index }}.supplier_declares_rent" value="1" class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs text-gray-700">Sí</span>
                            </label>
                        </div>
                    </div>
                </div>

                @if((float)($line['subtotal'] ?? 0) > 0)
                <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                    <div class="bg-white rounded-lg p-2 text-center border border-gray-200">
                        <p class="text-[10px] text-gray-500">Retefuente {{ number_format($line['retention_percentage'] ?? 0, 1) }}%</p>
                        <p class="text-sm font-bold text-red-600">${{ number_format($line['retefuente'] ?? 0, 2, ',', '.') }}</p>
                    </div>
                    <div class="bg-white rounded-lg p-2 text-center border border-gray-200">
                        <p class="text-[10px] text-gray-500">ReteIVA</p>
                        <p class="text-sm font-bold text-red-600">${{ number_format($line['reteiva'] ?? 0, 2, ',', '.') }}</p>
                    </div>
                    <div class="bg-white rounded-lg p-2 text-center border border-gray-200">
                        <p class="text-[10px] text-gray-500">Est. Produlto</p>
                        <p class="text-sm font-bold {{ ($line['estampilla_produlto_mayor'] ?? 0) > 0 ? 'text-orange-600' : 'text-gray-400' }}">${{ number_format($line['estampilla_produlto_mayor'] ?? 0, 2, ',', '.') }}</p>
                    </div>
                    <div class="bg-white rounded-lg p-2 text-center border border-gray-200">
                        <p class="text-[10px] text-gray-500">Total Desc.</p>
                        <p class="text-sm font-bold text-red-700">${{ number_format($line['total_retentions'] ?? 0, 2, ',', '.') }}</p>
                    </div>
                    <div class="bg-emerald-50 rounded-lg p-2 text-center border border-emerald-200">
                        <p class="text-[10px] text-emerald-600">Neto Línea</p>
                        <p class="text-sm font-bold text-emerald-700">${{ number_format($line['net_payment'] ?? 0, 2, ',', '.') }}</p>
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    @endif
</div>
@endif

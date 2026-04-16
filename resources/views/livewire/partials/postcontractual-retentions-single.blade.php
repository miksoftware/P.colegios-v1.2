{{-- Retenciones DIAN y Otros Impuestos (modo single) --}}
@if($paymentMode === 'single')
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        Impuestos - Retenciones DIAN
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Concepto de Retención</label>
            <select wire:model.live="retentionConcept" class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">-- Sin retención --</option>
                @foreach(\App\Models\PaymentOrder::RETENTION_CONCEPTS as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">¿El proveedor declara renta?</label>
            <div class="flex items-center gap-6 mt-2">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="radio" wire:model.live="supplierDeclaresRent" value="0" class="text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-gray-700">No declara</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="radio" wire:model.live="supplierDeclaresRent" value="1" class="text-emerald-600 focus:ring-emerald-500">
                    <span class="text-sm text-gray-700">Sí declara</span>
                </label>
            </div>
        </div>
    </div>

    @if($retentionConcept)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
        <p class="text-xs font-medium text-amber-700 uppercase mb-2">Porcentajes de Retención en la Fuente</p>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-xs">
            @foreach(\App\Models\PaymentOrder::RETENTION_RATES as $concept => $rates)
                @php $minBase = \App\Models\PaymentOrder::RETENTION_MIN_BASE[$concept] ?? 0; @endphp
                <div class="bg-white rounded-lg p-2 border {{ $retentionConcept === $concept ? 'border-amber-400 ring-1 ring-amber-300' : 'border-amber-100' }}">
                    <p class="font-medium text-gray-700">{{ \App\Models\PaymentOrder::RETENTION_CONCEPTS[$concept] }}</p>
                    <p class="text-gray-500">No declara: {{ $rates[0] }}%</p>
                    <p class="text-gray-500">Declara: {{ $rates[1] }}%</p>
                    <p class="text-gray-400 mt-1">Base mín: ${{ number_format($minBase, 2, ',', '.') }}</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @if(!empty($supplierData['tax_regime']))
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 mb-4">
        <p class="text-xs text-blue-700">
            <span class="font-medium">Régimen del proveedor:</span> {{ $supplierData['tax_regime_name'] ?? 'N/D' }}.
            @if($supplierData['tax_regime'] === 'simple')
                <span class="text-blue-800 font-medium">Régimen Simple → No aplica Retefuente. Sí aplica ReteIVA (15% del IVA).</span>
            @elseif(in_array($supplierData['tax_regime'], ['comun', 'gran_contribuyente']))
                <span class="text-blue-800 font-medium">Responsable de IVA → Se aplica ReteIVA (15% del IVA).</span>
            @else
                <span class="text-blue-600">No responsable de IVA → No se aplica ReteIVA.</span>
            @endif
        </p>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gray-50 rounded-xl p-3 text-center">
            <p class="text-xs text-gray-500">Retefuente %</p>
            <p class="text-lg font-bold text-gray-900">{{ number_format($retentionPercentage, 1) }}%</p>
        </div>
        <div class="bg-red-50 rounded-xl p-3 text-center">
            <p class="text-xs text-red-500">Retefuente</p>
            <p class="text-lg font-bold text-red-700">${{ number_format($retefuente, 2, ',', '.') }}</p>
        </div>
        <div class="bg-red-50 rounded-xl p-3 text-center">
            <p class="text-xs text-red-500">ReteIVA (15% del IVA)</p>
            <p class="text-lg font-bold text-red-700">${{ number_format($reteiva, 2, ',', '.') }}</p>
        </div>
        <div class="bg-red-100 rounded-xl p-3 text-center">
            <p class="text-xs text-red-600 font-medium">Total Retenciones DIAN</p>
            <p class="text-lg font-bold text-red-800">${{ number_format($totalRetentionsDian, 2, ',', '.') }}</p>
        </div>
    </div>
</div>

{{-- Otros Impuestos --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        Otros Impuestos
        <span class="text-xs font-normal text-gray-400">(según municipio del colegio)</span>
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="bg-gray-50 rounded-xl p-3 text-center">
            <p class="text-xs text-gray-500">Estampilla Produlto Mayor</p>
            <p class="text-lg font-bold {{ $estampillaProdultoMayor > 0 ? 'text-orange-700' : 'text-gray-400' }}">${{ number_format($estampillaProdultoMayor, 2, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400">2% del subtotal (solo Bucaramanga)</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3 text-center">
            <p class="text-xs text-gray-500">Estampilla Procultura</p>
            <p class="text-lg font-bold {{ $estampillaProcultura > 0 ? 'text-orange-700' : 'text-gray-400' }}">${{ number_format($estampillaProcultura, 2, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400">2% del subtotal (solo Bucaramanga, ≥ $35.018.010)</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3 text-center">
            <p class="text-xs text-gray-500">Retención ICA</p>
            <p class="text-lg font-bold text-gray-400">${{ number_format($retencionIca, 2, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400">Solo Piedecuesta y Villanueva</p>
        </div>
    </div>
    @if($otherTaxesTotal > 0)
    <div class="bg-orange-100 rounded-xl p-3 text-center">
        <p class="text-xs text-orange-600 font-medium">Total Otros Impuestos</p>
        <p class="text-lg font-bold text-orange-800">${{ number_format($otherTaxesTotal, 2, ',', '.') }}</p>
    </div>
    @endif
</div>
@endif

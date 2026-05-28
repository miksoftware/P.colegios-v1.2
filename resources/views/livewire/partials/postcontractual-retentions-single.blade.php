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
                @foreach(\App\Models\RetentionConfig::CONCEPTS as $key => $def)
                    @if($def['category'] === 'retefuente')
                        <option value="{{ $key }}">{{ $def['display_name'] }}</option>
                    @endif
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
        <p class="text-xs font-medium text-amber-700 uppercase mb-2">
            Porcentajes de Retención en la Fuente
            <span class="text-amber-600 normal-case font-normal">· vigencia {{ $filterYear }}</span>
        </p>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-xs">
            @php
                $retefuenteConfigs = $this->retentionConfigs->filter(fn($c) => $c->category === 'retefuente');
            @endphp
            @forelse($retefuenteConfigs as $concept => $cfg)
                <div class="bg-white rounded-lg p-2 border {{ $retentionConcept === $concept ? 'border-amber-400 ring-1 ring-amber-300' : 'border-amber-100' }} {{ $cfg->is_active ? '' : 'opacity-60' }}">
                    <p class="font-medium text-gray-700">
                        {{ $cfg->display_name }}
                        @unless($cfg->is_active)
                            <span class="text-[9px] text-red-500">(inactivo)</span>
                        @endunless
                    </p>
                    <p class="text-gray-500">No declara: {{ number_format((float) $cfg->rate_not_declares, 2, ',', '.') }}%</p>
                    <p class="text-gray-500">Declara: {{ number_format((float) $cfg->rate_declares, 2, ',', '.') }}%</p>
                    <p class="text-gray-400 mt-1">Base mín: ${{ number_format((float) $cfg->min_base, 0, ',', '.') }}</p>
                </div>
            @empty
                <div class="col-span-full text-center text-gray-500 py-2">
                    No hay configuraciones de retención para la vigencia {{ $filterYear }}.
                </div>
            @endforelse
        </div>
    </div>
    @endif

    @if(!empty($supplierData['tax_regime']))
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 mb-4">
        <p class="text-xs text-blue-700">
            <span class="font-medium">Régimen del proveedor:</span> {{ $supplierData['tax_regime_name'] ?? 'N/D' }}.
            @php $reteivaRate = (float) ($this->retentionConfigs['reteiva']->rate ?? 0); @endphp
            @if($supplierData['tax_regime'] === 'simple')
                <span class="text-blue-800 font-medium">Régimen Simple → No aplica Retefuente. Sí aplica ReteIVA ({{ number_format($reteivaRate, 2, ',', '.') }}% del IVA).</span>
            @elseif(in_array($supplierData['tax_regime'], ['comun', 'gran_contribuyente']))
                <span class="text-blue-800 font-medium">Responsable de IVA → Se aplica ReteIVA ({{ number_format($reteivaRate, 2, ',', '.') }}% del IVA).</span>
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
            @php $reteivaRate = (float) ($this->retentionConfigs['reteiva']->rate ?? 0); @endphp
            <p class="text-xs text-red-500">ReteIVA ({{ number_format($reteivaRate, 2, ',', '.') }}% del IVA)</p>
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
        <span class="text-xs font-normal text-gray-400">(según configuración del colegio · vigencia {{ $filterYear }})</span>
    </h2>
    @php
        $cfgEstProdulto   = $this->retentionConfigs['estampilla_produlto_mayor'] ?? null;
        $cfgEstProcultura = $this->retentionConfigs['estampilla_procultura'] ?? null;
        $cfgEstProdeporte = $this->retentionConfigs['estampilla_prodeporte'] ?? null;
        $cfgIca           = $this->retentionConfigs['retencion_ica'] ?? null;
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        @if($paymentType === 'accounts_payable')
        <div class="bg-gray-50 rounded-xl p-3 {{ ($cfgEstProdulto && !$cfgEstProdulto->is_active) ? 'opacity-60' : '' }}">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-gray-500">{{ $cfgEstProdulto->display_name ?? 'Estampilla Produlto Mayor' }}</p>
                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                    <input type="checkbox" wire:model.live="applyEstampillaProdulto"
                        @if(!$cfgEstProdulto || !$cfgEstProdulto->is_active) disabled @endif
                        class="rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                    <span class="text-xs font-medium text-gray-600">Aplicar</span>
                </label>
            </div>
            <p class="text-lg font-bold text-center {{ $estampillaProdultoMayor > 0 ? 'text-orange-700' : 'text-gray-400' }}">${{ number_format($estampillaProdultoMayor, 2, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400 text-center">
                @if($cfgEstProdulto && $cfgEstProdulto->is_active)
                    {{ number_format((float) $cfgEstProdulto->rate, 2, ',', '.') }}% del subtotal (base ≥ ${{ number_format((float) $cfgEstProdulto->min_base, 0, ',', '.') }})
                @else
                    No configurado / inactivo
                @endif
            </p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3 {{ ($cfgEstProcultura && !$cfgEstProcultura->is_active) ? 'opacity-60' : '' }}">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-gray-500">{{ $cfgEstProcultura->display_name ?? 'Estampilla Procultura' }}</p>
                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                    <input type="checkbox" wire:model.live="applyEstampillaProcultura"
                        @if(!$cfgEstProcultura || !$cfgEstProcultura->is_active) disabled @endif
                        class="rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                    <span class="text-xs font-medium text-gray-600">Aplicar</span>
                </label>
            </div>
            <p class="text-lg font-bold text-center {{ $estampillaProcultura > 0 ? 'text-orange-700' : 'text-gray-400' }}">${{ number_format($estampillaProcultura, 2, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400 text-center">
                @if($cfgEstProcultura && $cfgEstProcultura->is_active)
                    {{ number_format((float) $cfgEstProcultura->rate, 2, ',', '.') }}% del subtotal (base ≥ ${{ number_format((float) $cfgEstProcultura->min_base, 0, ',', '.') }})
                @else
                    No configurado / inactivo
                @endif
            </p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3 {{ ($cfgEstProdeporte && !$cfgEstProdeporte->is_active) ? 'opacity-60' : '' }}">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-gray-500">{{ $cfgEstProdeporte->display_name ?? 'Estampilla Prodeporte' }}</p>
                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                    <input type="checkbox" wire:model.live="applyEstampillaProdeporte"
                        @if(!$cfgEstProdeporte || !$cfgEstProdeporte->is_active) disabled @endif
                        class="rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                    <span class="text-xs font-medium text-gray-600">Aplicar</span>
                </label>
            </div>
            <p class="text-lg font-bold text-center {{ $estampillaProdeporte > 0 ? 'text-orange-700' : 'text-gray-400' }}">${{ number_format($estampillaProdeporte, 2, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400 text-center">
                @if($cfgEstProdeporte && $cfgEstProdeporte->is_active)
                    {{ number_format((float) $cfgEstProdeporte->rate, 2, ',', '.') }}% del subtotal (base ≥ ${{ number_format((float) $cfgEstProdeporte->min_base, 0, ',', '.') }})
                @else
                    No configurado / inactivo
                @endif
            </p>
        </div>
        @else
        <div class="bg-gray-50 rounded-xl p-3 text-center {{ ($cfgEstProdulto && !$cfgEstProdulto->is_active) ? 'opacity-60' : '' }}">
            <p class="text-xs text-gray-500">{{ $cfgEstProdulto->display_name ?? 'Estampilla Produlto Mayor' }}</p>
            <p class="text-lg font-bold {{ $estampillaProdultoMayor > 0 ? 'text-orange-700' : 'text-gray-400' }}">${{ number_format($estampillaProdultoMayor, 2, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400">
                @if($cfgEstProdulto && $cfgEstProdulto->is_active)
                    {{ number_format((float) $cfgEstProdulto->rate, 2, ',', '.') }}% del subtotal (base ≥ ${{ number_format((float) $cfgEstProdulto->min_base, 0, ',', '.') }})
                @else
                    No aplica en este colegio
                @endif
            </p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3 text-center {{ ($cfgEstProcultura && !$cfgEstProcultura->is_active) ? 'opacity-60' : '' }}">
            <p class="text-xs text-gray-500">{{ $cfgEstProcultura->display_name ?? 'Estampilla Procultura' }}</p>
            <p class="text-lg font-bold {{ $estampillaProcultura > 0 ? 'text-orange-700' : 'text-gray-400' }}">${{ number_format($estampillaProcultura, 2, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400">
                @if($cfgEstProcultura && $cfgEstProcultura->is_active)
                    {{ number_format((float) $cfgEstProcultura->rate, 2, ',', '.') }}% del subtotal (base ≥ ${{ number_format((float) $cfgEstProcultura->min_base, 0, ',', '.') }})
                @else
                    No aplica en este colegio
                @endif
            </p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3 text-center {{ ($cfgEstProdeporte && !$cfgEstProdeporte->is_active) ? 'opacity-60' : '' }}">
            <p class="text-xs text-gray-500">{{ $cfgEstProdeporte->display_name ?? 'Estampilla Prodeporte' }}</p>
            <p class="text-lg font-bold {{ $estampillaProdeporte > 0 ? 'text-orange-700' : 'text-gray-400' }}">${{ number_format($estampillaProdeporte, 2, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400">
                @if($cfgEstProdeporte && $cfgEstProdeporte->is_active)
                    {{ number_format((float) $cfgEstProdeporte->rate, 2, ',', '.') }}% del subtotal (base ≥ ${{ number_format((float) $cfgEstProdeporte->min_base, 0, ',', '.') }})
                @else
                    No aplica en este colegio
                @endif
            </p>
        </div>
        @endif
        <div class="bg-gray-50 rounded-xl p-3 text-center {{ ($cfgIca && !$cfgIca->is_active) ? 'opacity-60' : '' }}">
            <p class="text-xs text-gray-500">{{ $cfgIca->display_name ?? 'Retención ICA' }}</p>
            <p class="text-lg font-bold {{ $retencionIca > 0 ? 'text-orange-700' : 'text-gray-400' }}">${{ number_format($retencionIca, 2, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400">
                @if($cfgIca && $cfgIca->is_active && (float) $cfgIca->rate > 0)
                    {{ number_format((float) $cfgIca->rate, 2, ',', '.') }}% del subtotal (base ≥ ${{ number_format((float) $cfgIca->min_base, 0, ',', '.') }})
                @else
                    No aplica en este colegio
                @endif
            </p>
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

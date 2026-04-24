<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @if($currentView === 'list')
            {{-- ==================== VISTA LISTADO ==================== --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Etapa Postcontractual</h1>
                    <p class="text-gray-500 mt-1">Gestión de órdenes de pago, retenciones y egresos</p>
                </div>
                @can('postcontractual.create')
                    <button wire:click="openCreateView" class="px-4 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors flex items-center gap-2 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nueva Orden de Pago
                    </button>
                @endcan
            </div>

            {{-- Resumen --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-gray-500 uppercase">Total</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $summary['total'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-gray-500 uppercase">Borrador</p>
                    <p class="text-2xl font-bold text-gray-500">{{ $summary['draft'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-blue-500 uppercase">Aprobadas</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $summary['approved'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-green-500 uppercase">Pagadas</p>
                    <p class="text-2xl font-bold text-green-600">{{ $summary['paid'] }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
                    <p class="text-xs text-emerald-500 uppercase">Total Pagado</p>
                    <p class="text-lg font-bold text-emerald-600">${{ number_format($summary['total_value'], 2, ',', '.') }}</p>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                        <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border-gray-300" placeholder="N° pago, factura, proveedor...">
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select wire:model.live="filterStatus" class="w-full rounded-xl border-gray-300">
                            <option value="">Todos</option>
                            @foreach(\App\Models\PaymentOrder::STATUSES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button wire:click="$set('search', '')" class="w-full px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tabla --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Pago</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contrato / Concepto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Retenciones</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Neto</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($paymentOrders as $po)
                                @php $resolvedSupplier = $po->resolved_supplier; @endphp
                                <tr class="hover:bg-gray-50" wire:key="po-{{ $po->id }}">
                                    <td class="px-6 py-4">
                                        <span class="font-mono font-bold text-emerald-600">{{ $po->formatted_number }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($po->payment_type === 'direct')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">Directo</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Contrato</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($po->payment_type === 'direct')
                                            <p class="text-sm font-medium text-gray-900">Pago Directo</p>
                                            <p class="text-xs text-gray-500 truncate max-w-[200px]">{{ Str::limit($po->description, 40) }}</p>
                                        @else
                                            <p class="text-sm font-medium text-gray-900">N° {{ $po->contract?->formatted_number }}</p>
                                            <p class="text-xs text-gray-500 truncate max-w-[200px]">{{ Str::limit($po->contract?->object, 40) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-gray-900">{{ $resolvedSupplier?->full_name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500">{{ $resolvedSupplier?->full_document ?? '' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold">${{ number_format($po->total, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right text-red-600">${{ number_format($po->total_retentions, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right font-bold text-emerald-700">${{ number_format($po->net_payment, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $po->status_color }}">
                                            {{ $po->status_name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-xs text-gray-500">
                                        {{ $po->payment_date?->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button wire:click="viewDetail({{ $po->id }})" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg" title="Ver detalle">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        <p class="mt-2">No hay órdenes de pago para este período</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($paymentOrders->hasPages())
                    <div class="px-6 py-4 border-t">{{ $paymentOrders->links() }}</div>
                @endif
            </div>

        @elseif($currentView === 'create')
            {{-- ==================== VISTA CREAR ==================== --}}
            <div class="mb-6">
                <button wire:click="backToList" class="inline-flex items-center gap-2 text-sm text-emerald-600 hover:text-emerald-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Volver al listado
                </button>
            </div>

            <div class="flex items-center gap-3 mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Nueva Orden de Pago</h1>
                <span class="text-sm text-gray-500">Etapa Postcontractual</span>
            </div>

            <form wire:submit.prevent="savePaymentOrder" class="space-y-6">

                {{-- ─── TIPO DE PAGO ────────────────────────────────── --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        Tipo de Orden de Pago
                    </h2>
                    <div class="flex items-center gap-6">
                        <label class="inline-flex items-center gap-2 cursor-pointer p-3 rounded-xl border-2 transition-colors {{ $paymentType === 'contract' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <input type="radio" wire:model.live="paymentType" value="contract" class="text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900">Con Contrato</span>
                                <p class="text-xs text-gray-500">Pago asociado a un contrato existente</p>
                            </div>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer p-3 rounded-xl border-2 transition-colors {{ $paymentType === 'direct' ? 'border-purple-500 bg-purple-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <input type="radio" wire:model.live="paymentType" value="direct" class="text-purple-600 focus:ring-purple-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900">Pago Directo</span>
                                <p class="text-xs text-gray-500">Servicios públicos, retenciones DIAN, etc.</p>
                            </div>
                        </label>
                    </div>
                </div>

                @if($paymentType === 'contract')
                {{-- ═══════════════════════════════════════════════════ --}}
                {{-- FLUJO CON CONTRATO (existente) --}}
                {{-- ═══════════════════════════════════════════════════ --}}

                {{-- Seleccionar Contrato --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Seleccione el Contrato
                    </h2>
                    <div class="max-w-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contrato *</label>
                        <select wire:model.live="selectedContractId" wire:change="onContractSelected" class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">-- Seleccione un contrato --</option>
                            @foreach($availableContracts as $ac)
                                <option value="{{ $ac['id'] }}">N° {{ $ac['number'] }} - {{ $ac['status'] }} - {{ Str::limit($ac['object'], 40) }} ({{ $ac['supplier'] }})</option>
                            @endforeach
                        </select>
                        @if(empty($availableContracts))
                            <p class="text-xs text-amber-600 mt-1">No hay contratos disponibles para este año.</p>
                        @endif
                        @error('selectedContractId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if(!empty($contractData))
                {{-- Objeto del Contrato --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Contrato N° {{ $contractData['number'] }}
                    </h2>
                    <div class="bg-emerald-50 rounded-xl p-4">
                        <label class="block text-xs font-medium text-emerald-700 uppercase mb-1">Objeto a Contratar</label>
                        <p class="text-sm text-gray-800">{{ $contractData['object'] }}</p>
                    </div>
                </div>

                @include('livewire.partials.postcontractual-supplier-info')
                @include('livewire.partials.postcontractual-contract-values')
                @include('livewire.partials.postcontractual-invoice-payment', ['showFullPaymentToggle' => true])
                @include('livewire.partials.postcontractual-expense-distribution')
                @include('livewire.partials.postcontractual-retentions-single')
                @include('livewire.partials.postcontractual-summary')
                @include('livewire.partials.postcontractual-observations')

                {{-- Botones --}}
                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="backToList" class="px-6 py-2.5 text-gray-700 hover:bg-gray-100 rounded-xl transition-colors">Cancelar</button>
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors flex items-center gap-2" wire:loading.attr="disabled">
                        <svg wire:loading wire:target="savePaymentOrder" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Crear Orden de Pago
                    </button>
                </div>
                @endif {{-- end if contractData --}}

                @else
                {{-- ═══════════════════════════════════════════════════ --}}
                {{-- FLUJO PAGO DIRECTO (sin contrato) --}}
                {{-- ═══════════════════════════════════════════════════ --}}

                {{-- Seleccionar Proveedor --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Seleccione el Proveedor / Beneficiario
                    </h2>
                    <div class="max-w-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor *</label>
                        <select wire:model.live="selectedSupplierId" wire:change="onSupplierSelected" class="w-full rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                            <option value="">-- Seleccione un proveedor --</option>
                            @foreach($availableSuppliers as $as)
                                <option value="{{ $as['id'] }}">{{ $as['name'] }} ({{ $as['document'] }})</option>
                            @endforeach
                        </select>
                        @if(empty($availableSuppliers))
                            <p class="text-xs text-amber-600 mt-1">No hay proveedores registrados para este colegio.</p>
                        @endif
                        @error('selectedSupplierId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if(!empty($supplierData))
                @include('livewire.partials.postcontractual-supplier-info')

                {{-- Descripción del Pago --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                        Concepto del Pago
                    </h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción del pago *</label>
                        <textarea wire:model="directDescription" rows="3" class="w-full rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500" placeholder="Ej: Pago de servicios públicos (energía eléctrica) mes de marzo 2026, Pago retenciones DIAN periodo enero-marzo 2026..."></textarea>
                        @error('directDescription') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Soporte Presupuestal (CDP + RP se crean automáticamente) --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Soporte Presupuestal
                    </h2>

                    {{-- Checkbox para omitir CDP/RP --}}
                    <div class="mb-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="skipCdpRp" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700">Este pago <span class="font-medium">no requiere CDP ni RP</span> (retenciones, gastos financieros, etc.)</span>
                        </label>
                    </div>

                    @if(!$skipCdpRp)
                    <div class="bg-purple-50 border border-purple-200 rounded-xl p-3 mb-4">
                        <p class="text-xs text-purple-700">Al guardar se creará automáticamente un CDP y un RP con los datos presupuestales que ingrese aquí.</p>
                    </div>

                    {{-- Rubro Presupuestal --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rubro Presupuestal *</label>
                        <select wire:model.live="directBudgetItemId" class="w-full rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                            <option value="">-- Seleccione un rubro --</option>
                            @foreach($directBudgetItems as $bi)
                                <option value="{{ $bi['id'] }}">{{ $bi['name'] }}</option>
                            @endforeach
                        </select>
                        @if(empty($directBudgetItems))
                            <p class="text-xs text-amber-600 mt-1">No hay rubros con presupuesto de gasto activo para este año.</p>
                        @endif
                        @error('directBudgetItemId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Fuentes de Financiación --}}
                    @if($directBudgetItemId && count($directFundingSources) > 0)
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fuentes de Financiación</label>

                        {{-- Botón para agregar fuente --}}
                        @if(count($directFundingSources) > count($directSelectedSources))
                        <div class="flex items-center gap-3 mb-3">
                            <select id="addSourceSelect" class="flex-1 rounded-xl border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500">
                                <option value="">-- Agregar fuente --</option>
                                @foreach($directFundingSources as $fs)
                                    @php $alreadyAdded = collect($directSelectedSources)->contains('id', $fs['id']); @endphp
                                    @if(!$alreadyAdded)
                                        <option value="{{ $fs['id'] }}">{{ $fs['name'] }} (Disponible: ${{ number_format($fs['available'], 0, ',', '.') }})</option>
                                    @endif
                                @endforeach
                            </select>
                            <button type="button"
                                onclick="let sel = document.getElementById('addSourceSelect'); if(sel.value) { @this.call('addDirectFundingSource', parseInt(sel.value)); sel.value=''; }"
                                class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 text-sm transition-colors">
                                Agregar
                            </button>
                        </div>
                        @endif

                        {{-- Fuentes seleccionadas --}}
                        @if(count($directSelectedSources) > 0)
                        <div class="space-y-3">
                            @foreach($directSelectedSources as $index => $src)
                            <div class="border border-purple-200 rounded-xl p-4 bg-purple-50" wire:key="direct-src-{{ $index }}">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-semibold text-gray-800">{{ $src['name'] }}</p>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs text-gray-500">Disponible: ${{ number_format($src['available'], 0, ',', '.') }}</span>
                                        @if(count($directSelectedSources) > 1)
                                        <button type="button" wire:click="removeDirectFundingSource({{ $index }})" class="text-red-500 hover:text-red-700" title="Quitar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                                <div class="max-w-xs">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Monto a comprometer *</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-xs">$</span>
                                        <input type="number" wire:model.live.debounce.300ms="directSelectedSources.{{ $index }}.amount"
                                            step="0.01" max="{{ $src['available'] }}"
                                            class="w-full rounded-xl border-gray-300 pl-7 text-sm focus:border-purple-500 focus:ring-purple-500" placeholder="0">
                                    </div>
                                    @if((float)($src['amount'] ?? 0) > (float)$src['available'])
                                        <p class="mt-1 text-xs text-red-600">Excede el saldo disponible.</p>
                                    @endif
                                </div>

                                {{-- Banco y Cuenta Bancaria --}}
                                <div class="grid grid-cols-2 gap-3 mt-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Banco *</label>
                                        <select wire:model.live="directSelectedSources.{{ $index }}.bank_id"
                                            class="w-full rounded-xl border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500">
                                            <option value="">-- Banco --</option>
                                            @foreach($directBanks as $bank)
                                                <option value="{{ $bank['id'] }}">{{ $bank['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Cuenta Bancaria *</label>
                                        <select wire:model="directSelectedSources.{{ $index }}.bank_account_id"
                                            class="w-full rounded-xl border-gray-300 text-sm focus:border-purple-500 focus:ring-purple-500">
                                            <option value="">-- Cuenta --</option>
                                            @if(!empty($src['bank_id']))
                                                @php $selectedBank = collect($directBanks)->firstWhere('id', (int)$src['bank_id']); @endphp
                                                @if($selectedBank)
                                                    @foreach($selectedBank['accounts'] as $acct)
                                                        <option value="{{ $acct['id'] }}">{{ $acct['label'] }}</option>
                                                    @endforeach
                                                @endif
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- Total comprometido --}}
                        @php $totalCommitted = collect($directSelectedSources)->sum(fn($s) => (float)($s['amount'] ?? 0)); @endphp
                        <div class="mt-3 bg-purple-100 rounded-xl p-3 text-center">
                            <p class="text-xs text-purple-600 font-medium">Total a comprometer en CDP/RP</p>
                            <p class="text-lg font-bold text-purple-800">${{ number_format($totalCommitted, 2, ',', '.') }}</p>
                        </div>
                        @endif
                    </div>
                    @elseif($directBudgetItemId && count($directFundingSources) === 0)
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
                            <p class="text-xs text-amber-700">No hay fuentes de financiación con saldo disponible para este rubro.</p>
                        </div>
                    @endif
                    @endif {{-- end @if(!$skipCdpRp) --}}

                    @if($skipCdpRp)
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4">
                        <p class="text-xs text-amber-700">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            No se creará CDP ni RP para este pago. Solo se registrará la orden de pago directa.
                        </p>
                    </div>

                    {{-- Banco y Cuenta de Egreso --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Banco de Egreso</label>
                            <select wire:model.live="directEgressBankId" class="w-full rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                                <option value="">-- Seleccione banco --</option>
                                @foreach($directEgressBanks as $bank)
                                    <option value="{{ $bank['id'] }}">{{ $bank['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Bancaria</label>
                            <select wire:model="directEgressBankAccountId" class="w-full rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                                <option value="">-- Seleccione cuenta --</option>
                                @foreach($directEgressBankAccounts as $ba)
                                    <option value="{{ $ba['id'] }}">{{ $ba['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif
                </div>

                @include('livewire.partials.postcontractual-invoice-payment', ['showFullPaymentToggle' => false])
                @include('livewire.partials.postcontractual-retentions-single')
                @include('livewire.partials.postcontractual-summary')
                @include('livewire.partials.postcontractual-observations')

                {{-- Botones --}}
                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="backToList" class="px-6 py-2.5 text-gray-700 hover:bg-gray-100 rounded-xl transition-colors">Cancelar</button>
                    <button type="submit" class="px-6 py-2.5 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors flex items-center gap-2" wire:loading.attr="disabled">
                        <svg wire:loading wire:target="savePaymentOrder" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Crear Orden de Pago Directo
                    </button>
                </div>
                @endif {{-- end if supplierData --}}

                @endif {{-- end paymentType --}}
            </form>

        @elseif($currentView === 'detail')
            {{-- ==================== VISTA DETALLE ==================== --}}
            <div class="mb-6">
                <button wire:click="backToList" class="inline-flex items-center gap-2 text-sm text-emerald-600 hover:text-emerald-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Volver al listado
                </button>
            </div>

            @if($paymentOrder)
            <div class="space-y-6">
                {{-- Header --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <h1 class="text-2xl font-bold text-gray-900">Orden de Pago N° {{ $paymentOrder->formatted_number }}</h1>
                                @if($paymentOrder->payment_type === 'direct')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">Pago Directo</span>
                                @endif
                            </div>
                            @if($paymentOrder->payment_type === 'direct')
                                <p class="text-gray-500 mt-1">{{ Str::limit($paymentOrder->description, 80) }}</p>
                            @else
                                <p class="text-gray-500 mt-1">Contrato N° {{ $paymentOrder->contract?->formatted_number }} - {{ Str::limit($paymentOrder->contract?->object, 60) }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium {{ $paymentOrder->status_color }}">
                                {{ $paymentOrder->status_name }}
                            </span>
                            {{-- Botón Imprimir --}}
                            <button wire:click="openPrintModal" class="px-4 py-2 bg-gray-600 text-white rounded-xl hover:bg-gray-700 text-sm transition-colors inline-flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Imprimir
                            </button>
                            @if(in_array($paymentOrder->status, ['draft', 'approved']))
                                @can('postcontractual.edit')
                                    <button wire:click="openStatusModal" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 text-sm transition-colors">
                                        Cambiar Estado
                                    </button>
                                @endcan
                            @endif
                            @if($paymentOrder->status === 'draft')
                                @can('postcontractual.delete')
                                    <button wire:click="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 text-sm transition-colors">
                                        Eliminar
                                    </button>
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Datos del Proveedor --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Datos del Proveedor</h2>
                    @php $detailSupplier = $paymentOrder->resolved_supplier; @endphp
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500">Nombre</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $detailSupplier?->full_name ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Documento</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $detailSupplier?->full_document ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Dirección</p>
                            <p class="text-sm text-gray-900">{{ $detailSupplier?->address ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Municipio</p>
                            <p class="text-sm text-gray-900">{{ $detailSupplier?->municipality?->name ?? 'No registrado' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Teléfono</p>
                            <p class="text-sm text-gray-900">{{ $detailSupplier?->phone ?? $detailSupplier?->mobile ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Régimen Tributario</p>
                            <p class="text-sm text-gray-900">{{ $detailSupplier?->tax_regime_name ?? 'N/D' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Descripción del pago directo --}}
                @if($paymentOrder->payment_type === 'direct' && $paymentOrder->description)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">Concepto del Pago</h2>
                    <p class="text-sm text-gray-700">{{ $paymentOrder->description }}</p>
                </div>
                @endif

                {{-- CDP y RP para pagos directos --}}
                @if($paymentOrder->payment_type === 'direct' && ($paymentOrder->cdp || $paymentOrder->contractRp || $paymentOrder->budgetItem))
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Información Presupuestal</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @if($paymentOrder->budgetItem)
                        <div>
                            <p class="text-xs text-gray-500">Rubro Presupuestal</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $paymentOrder->budgetItem->code }} - {{ $paymentOrder->budgetItem->name }}</p>
                        </div>
                        @endif
                        @if($paymentOrder->cdp)
                        <div>
                            <p class="text-xs text-gray-500">CDP</p>
                            <p class="text-sm font-semibold text-gray-900">N° {{ $paymentOrder->cdp->formatted_number }} - ${{ number_format($paymentOrder->cdp->total_amount, 2, ',', '.') }}</p>
                        </div>
                        @endif
                        @if($paymentOrder->contractRp)
                        <div>
                            <p class="text-xs text-gray-500">Registro Presupuestal</p>
                            <p class="text-sm font-semibold text-gray-900">RP N° {{ $paymentOrder->contractRp->formatted_number }} - ${{ number_format($paymentOrder->contractRp->total_amount, 2, ',', '.') }}</p>
                        </div>
                        @endif
                    </div>

                    {{-- Fuentes de financiación --}}
                    @php
                        $detailSources = [];
                        if ($paymentOrder->contractRp) {
                            foreach ($paymentOrder->contractRp->fundingSources as $rpFs) {
                                $detailSources[] = ['name' => $rpFs->fundingSource?->name ?? 'N/A', 'amount' => (float) $rpFs->amount];
                            }
                        } elseif ($paymentOrder->cdp) {
                            foreach ($paymentOrder->cdp->fundingSources as $cdpFs) {
                                $detailSources[] = ['name' => $cdpFs->fundingSource?->name ?? 'N/A', 'amount' => (float) $cdpFs->amount];
                            }
                        }
                    @endphp
                    @if(count($detailSources) > 0)
                    <div class="mt-4 bg-blue-50 rounded-xl p-4">
                        <p class="text-xs font-medium text-blue-700 uppercase mb-2">Fuentes de Financiación</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @foreach($detailSources as $ds)
                                <div class="bg-white rounded-lg p-3 border border-blue-100">
                                    <p class="text-xs text-gray-500">{{ $ds['name'] }}</p>
                                    <p class="text-sm font-bold text-blue-700">${{ number_format($ds['amount'], 2, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Valores del Pago --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Detalle del Pago</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500">Subtotal</p>
                            <p class="text-lg font-bold text-gray-900">${{ number_format($paymentOrder->subtotal, 2, ',', '.') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500">IVA</p>
                            <p class="text-lg font-bold text-gray-900">${{ number_format($paymentOrder->iva, 2, ',', '.') }}</p>
                        </div>
                        <div class="bg-emerald-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-emerald-600">Total</p>
                            <p class="text-lg font-bold text-emerald-700">${{ number_format($paymentOrder->total, 2, ',', '.') }}</p>
                        </div>
                        <div class="bg-blue-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-blue-600">Tipo de Pago</p>
                            <p class="text-sm font-bold text-blue-700">{{ $paymentOrder->is_full_payment ? 'Completo' : 'Parcial' }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500">Fecha de Pago</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $paymentOrder->payment_date?->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Fecha de Factura</p>
                            <p class="text-sm text-gray-900">{{ $paymentOrder->invoice_date?->format('d/m/Y') ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">N° Factura</p>
                            <p class="text-sm text-gray-900">{{ $paymentOrder->invoice_number ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Distribución por Código de Gasto (si tiene líneas) --}}
                @if($paymentOrder->expenseLines && $paymentOrder->expenseLines->count() > 0)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Distribución por Código de Gasto
                    </h2>
                    <div class="space-y-3">
                        @foreach($paymentOrder->expenseLines as $el)
                        <div class="border border-gray-200 rounded-xl p-4 bg-gray-50">
                            <p class="text-sm font-semibold text-gray-800 mb-2">{{ $el->expenseCode?->code }} - {{ $el->expenseCode?->name }}</p>
                            <div class="grid grid-cols-2 md:grid-cols-6 gap-2 text-center">
                                <div class="bg-white rounded-lg p-2 border border-gray-200">
                                    <p class="text-[10px] text-gray-500">Subtotal</p>
                                    <p class="text-sm font-bold text-gray-900">${{ number_format($el->subtotal, 2, ',', '.') }}</p>
                                </div>
                                <div class="bg-white rounded-lg p-2 border border-gray-200">
                                    <p class="text-[10px] text-gray-500">IVA</p>
                                    <p class="text-sm font-bold text-gray-900">${{ number_format($el->iva, 2, ',', '.') }}</p>
                                </div>
                                <div class="bg-white rounded-lg p-2 border border-gray-200">
                                    <p class="text-[10px] text-gray-500">Retefuente</p>
                                    <p class="text-sm font-bold text-red-600">${{ number_format($el->retefuente, 2, ',', '.') }}</p>
                                </div>
                                <div class="bg-white rounded-lg p-2 border border-gray-200">
                                    <p class="text-[10px] text-gray-500">ReteIVA</p>
                                    <p class="text-sm font-bold text-red-600">${{ number_format($el->reteiva, 2, ',', '.') }}</p>
                                </div>
                                <div class="bg-white rounded-lg p-2 border border-gray-200">
                                    <p class="text-[10px] text-gray-500">Total Desc.</p>
                                    <p class="text-sm font-bold text-red-700">${{ number_format($el->total_retentions, 2, ',', '.') }}</p>
                                </div>
                                <div class="bg-emerald-50 rounded-lg p-2 border border-emerald-200">
                                    <p class="text-[10px] text-emerald-600">Neto</p>
                                    <p class="text-sm font-bold text-emerald-700">${{ number_format($el->net_payment, 2, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Retenciones DIAN --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Retenciones y Descuentos</h2>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500">Concepto</p>
                            <p class="text-sm font-bold text-gray-900">{{ $paymentOrder->retention_concept_name }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500">% Retención</p>
                            <p class="text-lg font-bold text-gray-900">{{ number_format($paymentOrder->retention_percentage, 1) }}%</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-red-500">Retefuente</p>
                            <p class="text-lg font-bold text-red-700">${{ number_format($paymentOrder->retefuente, 2, ',', '.') }}</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-red-500">ReteIVA</p>
                            <p class="text-lg font-bold text-red-700">${{ number_format($paymentOrder->reteiva, 2, ',', '.') }}</p>
                        </div>
                        <div class="bg-red-100 rounded-xl p-3 text-center">
                            <p class="text-xs text-red-600 font-medium">Total Ret. DIAN</p>
                            <p class="text-lg font-bold text-red-800">${{ number_format((float)$paymentOrder->retefuente + (float)$paymentOrder->reteiva, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    @if((float)$paymentOrder->estampilla_produlto_mayor > 0 || (float)$paymentOrder->estampilla_procultura > 0 || (float)$paymentOrder->retencion_ica > 0)
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 mt-4">Otros Impuestos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        @if((float)$paymentOrder->estampilla_produlto_mayor > 0)
                        <div class="bg-orange-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-orange-500">Estampilla Produlto Mayor</p>
                            <p class="text-lg font-bold text-orange-700">${{ number_format($paymentOrder->estampilla_produlto_mayor, 2, ',', '.') }}</p>
                        </div>
                        @endif
                        @if((float)$paymentOrder->estampilla_procultura > 0)
                        <div class="bg-orange-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-orange-500">Estampilla Procultura</p>
                            <p class="text-lg font-bold text-orange-700">${{ number_format($paymentOrder->estampilla_procultura, 2, ',', '.') }}</p>
                        </div>
                        @endif
                        @if((float)$paymentOrder->retencion_ica > 0)
                        <div class="bg-orange-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-orange-500">Retención ICA</p>
                            <p class="text-lg font-bold text-orange-700">${{ number_format($paymentOrder->retencion_ica, 2, ',', '.') }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Neto --}}
                    <div class="bg-emerald-100 rounded-xl p-4 text-center">
                        <div class="flex justify-center items-center gap-6 mb-2">
                            <div>
                                <p class="text-xs text-gray-500">Total Factura</p>
                                <p class="text-sm font-bold text-gray-700">${{ number_format($paymentOrder->total, 2, ',', '.') }}</p>
                            </div>
                            <span class="text-gray-400">−</span>
                            <div>
                                <p class="text-xs text-red-500">Total Descuentos</p>
                                <p class="text-sm font-bold text-red-700">${{ number_format($paymentOrder->total_retentions, 2, ',', '.') }}</p>
                            </div>
                            <span class="text-gray-400">=</span>
                        </div>
                        <p class="text-sm text-emerald-700 font-medium">NETO PAGADO AL PROVEEDOR</p>
                        <p class="text-3xl font-bold text-emerald-800">${{ number_format($paymentOrder->net_payment, 2, ',', '.') }}</p>
                    </div>
                </div>

                {{-- Cuenta bancaria --}}
                @if($paymentOrder->supplier_bank_name || $paymentOrder->supplier_account_number)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Cuenta Bancaria del Pago</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500">Banco</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $paymentOrder->supplier_bank_name ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Tipo de Cuenta</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $paymentOrder->supplier_account_type ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Número de Cuenta</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $paymentOrder->supplier_account_number ?? 'N/D' }}</p>
                        </div>
                    </div>
                </div>
                @endif

                @if($paymentOrder->observations)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">Observaciones</h2>
                    <p class="text-sm text-gray-700">{{ $paymentOrder->observations }}</p>
                </div>
                @endif

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-xs text-gray-400">
                        Creado por {{ $paymentOrder->creator?->name ?? 'N/A' }} el {{ $paymentOrder->created_at?->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>
            @endif
        @endif
    </div>

    {{-- ==================== MODALES ==================== --}}

    @if($showStatusModal && $paymentOrder)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="$set('showStatusModal', false)"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-md">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-bold text-gray-900">Cambiar Estado</h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">Estado actual: <span class="font-semibold">{{ $paymentOrder->status_name }}</span></p>
                    <select wire:model.live="newStatus" class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">-- Seleccione --</option>
                        @foreach($this->getAllowedStatuses($paymentOrder->status) as $status)
                            <option value="{{ $status }}">{{ \App\Models\PaymentOrder::STATUSES[$status] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showStatusModal', false)" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl">Cancelar</button>
                    <button type="button" wire:click="changeStatus" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700" {{ !$newStatus ? 'disabled' : '' }}>Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($showDeleteModal && $paymentOrder)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="$set('showDeleteModal', false)"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-md">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-bold text-red-600">Eliminar Orden de Pago</h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600">¿Está seguro de eliminar la orden de pago N° <span class="font-bold">{{ $paymentOrder->formatted_number }}</span>?</p>
                    <p class="text-xs text-red-500 mt-2">Esta acción no se puede deshacer.</p>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                    <button type="button" wire:click="$set('showDeleteModal', false)" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl">Cancelar</button>
                    <button type="button" wire:click="deletePaymentOrder" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Imprimir Documentos --}}
    @if($showPrintModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closePrintModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-md">
                <div class="px-6 py-4 border-b border-gray-200" style="background: linear-gradient(to right, #374151, #1f2937);">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Imprimir Documentos
                    </h3>
                    <p class="text-sm text-gray-300 mt-1">Seleccione los documentos que desea generar en PDF</p>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/50 cursor-pointer transition-colors">
                            <input type="checkbox" wire:model="printDocuments.comprobante_egreso" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="font-medium text-gray-900">Comprobante de Egreso</span>
                                <p class="text-xs text-gray-500 mt-0.5">Comprobante con imputación contable, retenciones, imputación presupuestal y datos bancarios.</p>
                            </div>
                        </label>

                        {{-- Orden de Pago --}}
                        <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/50 cursor-pointer transition-colors">
                            <input type="checkbox" wire:model="printDocuments.orden_pago" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="font-medium text-gray-900">Orden de Pago</span>
                                <p class="text-xs text-gray-500 mt-0.5">Resolución de pago con considerandos, rubro presupuestal, beneficiario y monto.</p>
                            </div>
                        </label>

                        {{-- Constancia de Recibido a Satisfacción --}}
                        <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/50 cursor-pointer transition-colors">
                            <input type="checkbox" wire:model="printDocuments.constancia_recibido" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="font-medium text-gray-900">Constancia de Recibido a Satisfacción</span>
                                <p class="text-xs text-gray-500 mt-0.5">Constancia del rector certificando recepción a satisfacción de bienes y/o servicios.</p>
                            </div>
                        </label>

                        {{-- Certificado de Retenciones --}}
                        <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/50 cursor-pointer transition-colors">
                            <input type="checkbox" wire:model="printDocuments.certificado_retenciones" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="font-medium text-gray-900">Certificado de Retenciones</span>
                                <p class="text-xs text-gray-500 mt-0.5">Resumen de retenciones de renta y de IVA practicadas en el pago.</p>
                            </div>
                        </label>

                        {{-- Documento Soporte --}}
                        <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50/50 cursor-pointer transition-colors">
                            <input type="checkbox" wire:model="printDocuments.documento_soporte" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="font-medium text-gray-900">Documento Soporte (No obligados a facturar)</span>
                                <p class="text-xs text-gray-500 mt-0.5">Para proveedores que no facturan electrónicamente. Incluye resolución DIAN, datos del proveedor, objeto y valor.</p>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                    <button type="button" wire:click="closePrintModal" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl transition-colors">Cancelar</button>
                    <button type="button" wire:click="printSelectedDocuments" class="px-4 py-2 bg-gray-700 text-white rounded-xl hover:bg-gray-800 transition-colors inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Generar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('openPdfWindow', (data) => {
            const url = Array.isArray(data) ? data[0].url : data.url;
            window.open(url, '_blank');
        });
    });
</script>
@endpush

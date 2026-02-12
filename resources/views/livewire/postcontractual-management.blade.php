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
                    <p class="text-lg font-bold text-emerald-600">${{ number_format($summary['total_value'], 0, ',', '.') }}</p>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                        <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border-gray-300" placeholder="N° pago, factura, contrato...">
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contrato</th>
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
                                <tr class="hover:bg-gray-50" wire:key="po-{{ $po->id }}">
                                    <td class="px-6 py-4">
                                        <span class="font-mono font-bold text-emerald-600">{{ $po->formatted_number }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-gray-900">N° {{ $po->contract?->formatted_number }}</p>
                                        <p class="text-xs text-gray-500 truncate max-w-[200px]">{{ Str::limit($po->contract?->object, 40) }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-gray-900">{{ $po->contract?->supplier?->full_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $po->contract?->supplier?->full_document }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold">${{ number_format($po->total, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right text-red-600">${{ number_format($po->total_retentions, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right font-bold text-emerald-700">${{ number_format($po->net_payment, 0, ',', '.') }}</td>
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
                                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">
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

                {{-- ─── SECCIÓN 1: Seleccionar Contrato ────────────── --}}
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
                                <option value="{{ $ac['id'] }}">N° {{ $ac['number'] }} - {{ Str::limit($ac['object'], 50) }} ({{ $ac['supplier'] }})</option>
                            @endforeach
                        </select>
                        @if(empty($availableContracts))
                            <p class="text-xs text-amber-600 mt-1">No hay contratos activos o en ejecución para este año.</p>
                        @endif
                        @error('selectedContractId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if(!empty($contractData))
                {{-- ─── SECCIÓN 2: Objeto del Contrato (informativo) ── --}}
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

                {{-- ─── SECCIÓN 3: Datos del Proveedor ─────────────── --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Datos del Proveedor
                        <span class="text-xs font-normal text-emerald-500 bg-emerald-50 px-2 py-0.5 rounded-full">Automático</span>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Nombre del Proveedor</label>
                            <p class="text-sm font-semibold text-gray-900 bg-gray-50 rounded-xl px-4 py-2.5">{{ $supplierData['name'] ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Número de Documento</label>
                            <p class="text-sm font-semibold text-gray-900 bg-gray-50 rounded-xl px-4 py-2.5">{{ $supplierData['document'] ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Dirección</label>
                            <p class="text-sm text-gray-900 bg-gray-50 rounded-xl px-4 py-2.5">{{ $supplierData['address'] ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Municipio</label>
                            <p class="text-sm text-gray-900 bg-gray-50 rounded-xl px-4 py-2.5">{{ $supplierData['municipality'] ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Teléfono</label>
                            <p class="text-sm text-gray-900 bg-gray-50 rounded-xl px-4 py-2.5">{{ $supplierData['phone'] ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Régimen Tributario</label>
                            <p class="text-sm text-gray-900 bg-gray-50 rounded-xl px-4 py-2.5">{{ $supplierData['tax_regime'] ?? 'N/D' }}</p>
                        </div>
                    </div>
                </div>

                {{-- ─── SECCIÓN 4: Valor del Contrato + Fuentes ────── --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Valor del Contrato
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500">Subtotal</p>
                            <p class="text-lg font-bold text-gray-900">${{ number_format($contractData['subtotal'], 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500">IVA</p>
                            <p class="text-lg font-bold text-gray-900">${{ number_format($contractData['iva'], 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-emerald-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-emerald-600">Total Contrato</p>
                            <p class="text-lg font-bold text-emerald-700">${{ number_format($contractData['total'], 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-amber-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-amber-600">Saldo Pendiente</p>
                            <p class="text-lg font-bold text-amber-700">${{ number_format($contractData['remaining'], 0, ',', '.') }}</p>
                        </div>
                    </div>

                    @if(count($fundingSourcesData) > 0)
                    <div class="bg-blue-50 rounded-xl p-4">
                        <p class="text-xs font-medium text-blue-700 uppercase mb-2">Distribución por Fuente de Financiación</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @foreach($fundingSourcesData as $fs)
                                <div class="bg-white rounded-lg p-3 border border-blue-100">
                                    <p class="text-xs text-gray-500">{{ $fs['name'] }}</p>
                                    <p class="text-sm font-bold text-blue-700">${{ number_format($fs['amount'], 0, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- ─── SECCIÓN 5: Datos de Factura y Pago ─────────── --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        Datos de Factura y Pago
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Pago *</label>
                            <input type="date" wire:model="paymentDate" class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                            @error('paymentDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de la Factura</label>
                            <input type="date" wire:model="invoiceDate" class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número de Factura</label>
                            <input type="text" wire:model="invoiceNumber" class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Ej: FAC-001">
                        </div>
                    </div>

                    {{-- ¿Pago completo? --}}
                    <div class="border-t pt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">¿Va a realizar el pago completo?</label>
                        <div class="flex items-center gap-6">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model.live="isFullPayment" value="1" class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-sm text-gray-700">Sí - Pago completo</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model.live="isFullPayment" value="0" class="text-emerald-600 focus:ring-emerald-500">
                                <span class="text-sm text-gray-700">No - Pago parcial</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            @if($isFullPayment)
                                Los valores se toman del saldo pendiente del contrato.
                            @else
                                Ingrese los valores de subtotal, IVA y total de la factura parcial.
                            @endif
                        </p>
                    </div>

                    {{-- Valores del pago --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subtotal *</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                                <input type="number" wire:model.live.debounce.300ms="paySubtotal" step="0.01" class="w-full rounded-xl border-gray-300 pl-7 focus:border-emerald-500 focus:ring-emerald-500 {{ $isFullPayment ? 'bg-gray-50' : '' }}" {{ $isFullPayment ? 'readonly' : '' }}>
                            </div>
                            @error('paySubtotal') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">IVA</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                                <input type="number" wire:model.live.debounce.300ms="payIva" step="0.01" class="w-full rounded-xl border-gray-300 pl-7 focus:border-emerald-500 focus:ring-emerald-500 {{ $isFullPayment ? 'bg-gray-50' : '' }}" {{ $isFullPayment ? 'readonly' : '' }}>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Total</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                                <input type="number" value="{{ $payTotal }}" step="0.01" class="w-full rounded-xl border-gray-300 pl-7 bg-gray-50 font-bold" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── SECCIÓN 6: Impuestos / Retenciones DIAN ────── --}}
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

                    {{-- Tabla de referencia de porcentajes --}}
                    @if($retentionConcept)
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                        <p class="text-xs font-medium text-amber-700 uppercase mb-2">Porcentajes de Retención en la Fuente</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                            @foreach(\App\Models\PaymentOrder::RETENTION_RATES as $concept => $rates)
                                <div class="bg-white rounded-lg p-2 border {{ $retentionConcept === $concept ? 'border-amber-400 ring-1 ring-amber-300' : 'border-amber-100' }}">
                                    <p class="font-medium text-gray-700">{{ \App\Models\PaymentOrder::RETENTION_CONCEPTS[$concept] }}</p>
                                    <p class="text-gray-500">No declara: {{ $rates[0] }}%</p>
                                    <p class="text-gray-500">Declara: {{ $rates[1] }}%</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Resultados de retención --}}
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500">Porcentaje Aplicado</p>
                            <p class="text-lg font-bold text-gray-900">{{ number_format($retentionPercentage, 1) }}%</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-red-500">Retefuente</p>
                            <p class="text-lg font-bold text-red-700">${{ number_format($retefuente, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-red-500">ReteIVA (15%)</p>
                            <p class="text-lg font-bold text-red-700">${{ number_format($reteiva, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-red-100 rounded-xl p-3 text-center">
                            <p class="text-xs text-red-600 font-medium">Total Retenciones DIAN</p>
                            <p class="text-lg font-bold text-red-800">${{ number_format($totalRetentions, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    {{-- Neto a pagar --}}
                    <div class="mt-4 bg-emerald-100 rounded-xl p-4 text-center">
                        <p class="text-sm text-emerald-700 font-medium">NETO A PAGAR AL PROVEEDOR</p>
                        <p class="text-3xl font-bold text-emerald-800">${{ number_format($netPayment, 0, ',', '.') }}</p>
                    </div>
                </div>

                {{-- ─── SECCIÓN 7: Observaciones ───────────────────── --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Observaciones</h2>
                    <textarea wire:model="observations" rows="3" class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Observaciones adicionales (opcional)..."></textarea>
                </div>

                {{-- Botones --}}
                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="backToList" class="px-6 py-2.5 text-gray-700 hover:bg-gray-100 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors flex items-center gap-2" wire:loading.attr="disabled">
                        <svg wire:loading wire:target="savePaymentOrder" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Crear Orden de Pago
                    </button>
                </div>

                @endif {{-- end if contractData --}}
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
                            <h1 class="text-2xl font-bold text-gray-900">Orden de Pago N° {{ $paymentOrder->formatted_number }}</h1>
                            <p class="text-gray-500 mt-1">Contrato N° {{ $paymentOrder->contract?->formatted_number }} - {{ Str::limit($paymentOrder->contract?->object, 60) }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium {{ $paymentOrder->status_color }}">
                                {{ $paymentOrder->status_name }}
                            </span>
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
                    @php $supplier = $paymentOrder->contract?->supplier; @endphp
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-500">Nombre</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $supplier?->full_name ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Documento</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $supplier?->full_document ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Dirección</p>
                            <p class="text-sm text-gray-900">{{ $supplier?->address ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Municipio</p>
                            <p class="text-sm text-gray-900">{{ $supplier?->city ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Teléfono</p>
                            <p class="text-sm text-gray-900">{{ $supplier?->phone ?? $supplier?->mobile ?? 'N/D' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Régimen Tributario</p>
                            <p class="text-sm text-gray-900">{{ $supplier?->tax_regime_name ?? 'N/D' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Valores --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Detalle del Pago</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500">Subtotal</p>
                            <p class="text-lg font-bold text-gray-900">${{ number_format($paymentOrder->subtotal, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500">IVA</p>
                            <p class="text-lg font-bold text-gray-900">${{ number_format($paymentOrder->iva, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-emerald-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-emerald-600">Total</p>
                            <p class="text-lg font-bold text-emerald-700">${{ number_format($paymentOrder->total, 0, ',', '.') }}</p>
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

                {{-- Retenciones --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Retenciones DIAN</h2>
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
                            <p class="text-lg font-bold text-red-700">${{ number_format($paymentOrder->retefuente, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-red-500">ReteIVA</p>
                            <p class="text-lg font-bold text-red-700">${{ number_format($paymentOrder->reteiva, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-red-100 rounded-xl p-3 text-center">
                            <p class="text-xs text-red-600 font-medium">Total Retenciones</p>
                            <p class="text-lg font-bold text-red-800">${{ number_format($paymentOrder->total_retentions, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="bg-emerald-100 rounded-xl p-4 text-center">
                        <p class="text-sm text-emerald-700 font-medium">NETO PAGADO AL PROVEEDOR</p>
                        <p class="text-3xl font-bold text-emerald-800">${{ number_format($paymentOrder->net_payment, 0, ',', '.') }}</p>
                    </div>
                </div>

                {{-- Observaciones --}}
                @if($paymentOrder->observations)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">Observaciones</h2>
                    <p class="text-sm text-gray-700">{{ $paymentOrder->observations }}</p>
                </div>
                @endif

                {{-- Info de creación --}}
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

    {{-- Modal Cambio de Estado --}}
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
                    <select wire:model="newStatus" class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
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

    {{-- Modal Eliminar --}}
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
</div>

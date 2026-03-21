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
                {{-- ─── SECCIÓN 2: Objeto del Contrato ── --}}
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
                            <p class="text-sm text-gray-900 bg-gray-50 rounded-xl px-4 py-2.5">{{ $supplierData['tax_regime_name'] ?? 'N/D' }}</p>
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

                    @if($contractData['total_paid'] > 0)
                    <div class="mt-4 bg-orange-50 border border-orange-200 rounded-xl p-3">
                        <p class="text-xs text-orange-700">
                            Ya se han registrado pagos por <span class="font-bold">${{ number_format($contractData['total_paid'], 0, ',', '.') }}</span>.
                            Saldo pendiente: <span class="font-bold">${{ number_format($contractData['remaining'], 0, ',', '.') }}</span>
                        </p>
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
                        <div x-data="{
                            init() {
                                flatpickr(this.$refs.paymentDateInput, {
                                    dateFormat: 'Y-m-d',
                                    defaultDate: $wire.paymentDate || null,
                                    disable: [function(date) { return date.getDay() === 0 || date.getDay() === 6; }],
                                    onChange: (selectedDates, dateStr) => { $wire.set('paymentDate', dateStr); }
                                });
                            }
                        }">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Pago *</label>
                            <input type="text" x-ref="paymentDateInput" value="{{ $paymentDate }}" class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 bg-white" placeholder="Seleccionar fecha..." readonly>
                            @error('paymentDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div x-data="{
                            init() {
                                flatpickr(this.$refs.invoiceDateInput, {
                                    dateFormat: 'Y-m-d',
                                    defaultDate: $wire.invoiceDate || null,
                                    disable: [function(date) { return date.getDay() === 0 || date.getDay() === 6; }],
                                    onChange: (selectedDates, dateStr) => { $wire.set('invoiceDate', dateStr); }
                                });
                            }
                        }">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de la Factura *</label>
                            <input type="text" x-ref="invoiceDateInput" value="{{ $invoiceDate }}" class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 bg-white" placeholder="Seleccionar fecha..." readonly>
                            @error('invoiceDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número de Factura *</label>
                            <input type="text" wire:model="invoiceNumber" class="w-full rounded-xl border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @error('invoiceNumber') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                            @if($paymentMode === 'split')
                                Los valores se calculan automáticamente desde la distribución por código de gasto.
                            @elseif($isFullPayment)
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
                                <input type="number" wire:model.live.debounce.300ms="paySubtotal" step="0.01" class="w-full rounded-xl border-gray-300 pl-7 focus:border-emerald-500 focus:ring-emerald-500 {{ ($isFullPayment || $paymentMode === 'split') ? 'bg-gray-50' : '' }}" {{ ($isFullPayment || $paymentMode === 'split') ? 'readonly' : '' }}>
                            </div>
                            @error('paySubtotal') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">IVA</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                                <input type="number" wire:model.live.debounce.300ms="payIva" step="0.01" class="w-full rounded-xl border-gray-300 pl-7 focus:border-emerald-500 focus:ring-emerald-500 {{ ($isFullPayment || $paymentMode === 'split') ? 'bg-gray-50' : '' }}" {{ ($isFullPayment || $paymentMode === 'split') ? 'readonly' : '' }}>
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

                {{-- ─── SECCIÓN 5.5: Distribución por Código de Gasto ── --}}
                @if(count($expenseDistributions) > 0)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Distribución por Código de Gasto
                    </h2>

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4">
                        <p class="text-xs text-amber-700">
                            La convocatoria tiene <span class="font-bold">{{ count($expenseDistributions) }}</span> código(s) de gasto asociado(s).
                            Seleccione cómo desea distribuir el pago.
                        </p>
                    </div>

                    {{-- Selector de modo --}}
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
                        {{-- Modo: Un solo código --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Seleccione el código de gasto *</label>
                            <select wire:model.live="selectedExpenseDistributionId" class="w-full rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">-- Seleccione --</option>
                                @foreach($expenseDistributions as $dist)
                                    @php $canCover = (float)$dist['convocatoria_amount'] >= (float)$payTotal; @endphp
                                    <option value="{{ $dist['id'] }}" {{ !$canCover ? 'disabled' : '' }}>
                                        {{ $dist['expense_code_name'] }} (Asignado: ${{ number_format($dist['convocatoria_amount'], 0, ',', '.') }})
                                        {{ !$canCover ? '— Insuficiente' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if($selectedExpenseDistributionId)
                                @php
                                    $selDist = collect($expenseDistributions)->firstWhere('id', $selectedExpenseDistributionId);
                                    $selMax = $selDist ? (float)$selDist['convocatoria_amount'] : 0;
                                @endphp
                                @if((float)$payTotal > $selMax && $selMax > 0)
                                    <p class="mt-1 text-sm text-red-600">El total del pago (${{ number_format($payTotal, 0, ',', '.') }}) excede lo asignado (${{ number_format($selMax, 0, ',', '.') }}).</p>
                                @endif
                            @endif
                        </div>
                    @else
                        {{-- Modo: Dividir entre códigos --}}
                        <div class="space-y-4">
                            @foreach($expenseLines as $index => $line)
                            <div class="border border-gray-200 rounded-xl p-4 bg-gray-50" wire:key="expense-line-{{ $index }}">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-sm font-semibold text-gray-800">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold mr-2">{{ $index + 1 }}</span>
                                        {{ $line['expense_code_name'] }}
                                    </h3>
                                    <span class="text-xs text-gray-500">Asignado en convocatoria: ${{ number_format($line['max_amount'], 0, ',', '.') }}</span>
                                </div>
                                @if(!empty($line['funding_source_name']) || !empty($line['bank_name']))
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 ml-8 mb-3 text-xs text-gray-500">
                                        @if(!empty($line['funding_source_name']))
                                            <span>Fuente: <span class="font-medium text-gray-700">{{ $line['funding_source_name'] }}</span></span>
                                        @endif
                                        @if(!empty($line['bank_name']))
                                            <span>Banco: <span class="font-medium text-gray-700">{{ $line['bank_name'] }}</span></span>
                                        @endif
                                        @if(!empty($line['bank_account']))
                                            <span>Cuenta: <span class="font-medium text-gray-700">{{ $line['bank_account'] }}</span></span>
                                        @endif
                                    </div>
                                @endif

                                {{-- Valores del pago por línea --}}
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Subtotal</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-xs">$</span>
                                            <input type="number" wire:model.live.debounce.500ms="expenseLines.{{ $index }}.subtotal"
                                                step="0.01" class="w-full rounded-xl border-gray-300 pl-7 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="0">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">IVA</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-xs">$</span>
                                            <input type="number" wire:model.live.debounce.500ms="expenseLines.{{ $index }}.iva"
                                                step="0.01" class="w-full rounded-xl border-gray-300 pl-7 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="0">
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

                                {{-- Retenciones por línea --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Concepto de Retención</label>
                                        <select wire:model.live="expenseLines.{{ $index }}.retention_concept"
                                            class="w-full rounded-xl border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
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
                                                <input type="radio" wire:model.live="expenseLines.{{ $index }}.supplier_declares_rent" value="0"
                                                    class="text-emerald-600 focus:ring-emerald-500">
                                                <span class="text-xs text-gray-700">No</span>
                                            </label>
                                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" wire:model.live="expenseLines.{{ $index }}.supplier_declares_rent" value="1"
                                                    class="text-emerald-600 focus:ring-emerald-500">
                                                <span class="text-xs text-gray-700">Sí</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                {{-- Resultados de retención de la línea --}}
                                @if(!empty($line['exceeded']))
                                <div class="bg-red-50 border border-red-300 rounded-xl p-2 mb-2">
                                    <p class="text-xs text-red-700 font-medium">⚠ El monto (${{ number_format($line['total'] ?? 0, 0, ',', '.') }}) excede lo asignado en la convocatoria (${{ number_format($line['max_amount'] ?? 0, 0, ',', '.') }}).</p>
                                </div>
                                @endif
                                @if((float)($line['subtotal'] ?? 0) > 0)
                                <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                                    <div class="bg-white rounded-lg p-2 text-center border border-gray-200">
                                        <p class="text-[10px] text-gray-500">Retefuente {{ number_format($line['retention_percentage'] ?? 0, 1) }}%</p>
                                        <p class="text-sm font-bold text-red-600">${{ number_format($line['retefuente'] ?? 0, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="bg-white rounded-lg p-2 text-center border border-gray-200">
                                        <p class="text-[10px] text-gray-500">ReteIVA</p>
                                        <p class="text-sm font-bold text-red-600">${{ number_format($line['reteiva'] ?? 0, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="bg-white rounded-lg p-2 text-center border border-gray-200">
                                        <p class="text-[10px] text-gray-500">Est. Produlto</p>
                                        <p class="text-sm font-bold {{ ($line['estampilla_produlto_mayor'] ?? 0) > 0 ? 'text-orange-600' : 'text-gray-400' }}">${{ number_format($line['estampilla_produlto_mayor'] ?? 0, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="bg-white rounded-lg p-2 text-center border border-gray-200">
                                        <p class="text-[10px] text-gray-500">Total Desc.</p>
                                        <p class="text-sm font-bold text-red-700">${{ number_format($line['total_retentions'] ?? 0, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="bg-emerald-50 rounded-lg p-2 text-center border border-emerald-200">
                                        <p class="text-[10px] text-emerald-600">Neto Línea</p>
                                        <p class="text-sm font-bold text-emerald-700">${{ number_format($line['net_payment'] ?? 0, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                @endif

                {{-- ─── SECCIÓN 6: Retenciones DIAN ────────────────── --}}
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

                    {{-- Tabla de referencia de porcentajes --}}
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
                                    <p class="text-gray-400 mt-1">Base mín: ${{ number_format($minBase, 0, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Info de régimen y ReteIVA --}}
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

                    {{-- Resultados de retención DIAN --}}
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500">Retefuente %</p>
                            <p class="text-lg font-bold text-gray-900">{{ number_format($retentionPercentage, 1) }}%</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-red-500">Retefuente</p>
                            <p class="text-lg font-bold text-red-700">${{ number_format($retefuente, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-red-400">Se calcula sobre el subtotal</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-red-500">ReteIVA (15% del IVA)</p>
                            <p class="text-lg font-bold text-red-700">${{ number_format($reteiva, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-red-400">Se calcula sobre el IVA</p>
                        </div>
                        <div class="bg-red-100 rounded-xl p-3 text-center">
                            <p class="text-xs text-red-600 font-medium">Total Retenciones DIAN</p>
                            <p class="text-lg font-bold text-red-800">${{ number_format($totalRetentionsDian, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- ─── SECCIÓN 7: Otros Impuestos ─────────────────── --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Otros Impuestos
                        <span class="text-xs font-normal text-gray-400">(según municipio del colegio)</span>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500">Estampilla Produlto Mayor</p>
                            <p class="text-lg font-bold {{ $estampillaProdultoMayor > 0 ? 'text-orange-700' : 'text-gray-400' }}">${{ number_format($estampillaProdultoMayor, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-gray-400">2% del subtotal (solo Bucaramanga, subtotal ≥ $1)</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500">Estampilla Procultura</p>
                            <p class="text-lg font-bold {{ $estampillaProcultura > 0 ? 'text-orange-700' : 'text-gray-400' }}">${{ number_format($estampillaProcultura, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-gray-400">2% del subtotal (solo Bucaramanga, subtotal ≥ $35.018.010)</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500">Retención ICA</p>
                            <p class="text-lg font-bold text-gray-400">${{ number_format($retencionIca, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-gray-400">Solo Piedecuesta y Villanueva</p>
                        </div>
                    </div>

                    @if($otherTaxesTotal > 0)
                    <div class="bg-orange-100 rounded-xl p-3 text-center">
                        <p class="text-xs text-orange-600 font-medium">Total Otros Impuestos</p>
                        <p class="text-lg font-bold text-orange-800">${{ number_format($otherTaxesTotal, 0, ',', '.') }}</p>
                    </div>
                    @endif
                </div>
                @endif {{-- end paymentMode === single --}}

                {{-- ─── SECCIÓN 8: Resumen Final ───────────────────── --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Resumen de Descuentos y Neto a Pagar</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="bg-gray-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-gray-500">Total Factura</p>
                            <p class="text-lg font-bold text-gray-900">${{ number_format($payTotal, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-red-500">Total Descuentos</p>
                            <p class="text-lg font-bold text-red-700">- ${{ number_format($totalRetentions, 0, ',', '.') }}</p>
                            <p class="text-[10px] text-red-400">DIAN: ${{ number_format($totalRetentionsDian, 0, ',', '.') }} + Otros: ${{ number_format($otherTaxesTotal, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-emerald-100 rounded-xl p-4 text-center">
                            <p class="text-sm text-emerald-700 font-medium">VALOR NETO A PAGAR</p>
                            <p class="text-3xl font-bold text-emerald-800">${{ number_format($netPayment, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    {{-- Cuenta bancaria del proveedor --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mt-4">
                        <p class="text-xs font-medium text-blue-700 uppercase mb-2">Cuenta Bancaria del Proveedor (donde se realiza el pago)</p>

                        @if(count($supplierBankAccounts) > 0)
                            <div class="mb-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Seleccionar Cuenta Bancaria *</label>
                                <select wire:model="selectedBankAccountId"
                                    class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">— Seleccionar cuenta —</option>
                                    @foreach($supplierBankAccounts as $ba)
                                        <option value="{{ $ba['id'] }}">{{ $ba['bank_name'] }} - {{ ucfirst($ba['account_type']) }} - {{ $ba['account_number'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-3">
                                <p class="text-xs text-amber-700">El proveedor no tiene cuentas bancarias registradas.</p>
                            </div>
                        @endif

                        {{-- Botón para agregar nueva cuenta --}}
                        @if(!$showNewBankAccountForm)
                            <button type="button" wire:click="toggleNewBankAccountForm"
                                class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Agregar nueva cuenta bancaria
                            </button>
                        @else
                            <div class="bg-white border border-blue-200 rounded-lg p-3 mt-2">
                                <p class="text-xs font-medium text-gray-700 mb-2">Nueva Cuenta Bancaria</p>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Banco *</label>
                                        <input type="text" wire:model="newBankName"
                                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Bancolombia">
                                        @error('newBankName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Tipo de Cuenta *</label>
                                        <select wire:model="newAccountType"
                                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                            @foreach(\App\Models\SupplierBankAccount::ACCOUNT_TYPES as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">N° Cuenta *</label>
                                        <input type="text" wire:model="newAccountNumber"
                                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-mono" placeholder="123456789">
                                        @error('newAccountNumber') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="flex gap-2 mt-3">
                                    <button type="button" wire:click="saveNewBankAccount"
                                        class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">
                                        Guardar Cuenta
                                    </button>
                                    <button type="button" wire:click="toggleNewBankAccountForm"
                                        class="px-3 py-1.5 bg-gray-200 text-gray-700 text-xs rounded-lg hover:bg-gray-300">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ─── SECCIÓN 9: Códigos Contables ───────────────── --}}
                @if($paymentMode === 'single' && ($retentionConcept || $otherTaxesTotal > 0))
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        Códigos Contables Aplicables
                    </h2>
                    <div class="space-y-2">
                        @if($retefuente > 0)
                        <div class="flex justify-between items-center bg-gray-50 rounded-lg px-4 py-2">
                            <span class="text-sm text-gray-700">
                                @if(in_array($retentionConcept, ['servicios', 'arrendamiento_sitios_web', 'arrendamiento_inmuebles', 'transporte_pasajeros']))
                                    {{ \App\Models\PaymentOrder::ACCOUNTING_CODES['retefuente_servicios'] }}
                                @elseif($retentionConcept === 'compras')
                                    {{ \App\Models\PaymentOrder::ACCOUNTING_CODES['retefuente_compras'] }}
                                @elseif($retentionConcept === 'honorarios')
                                    {{ \App\Models\PaymentOrder::ACCOUNTING_CODES['retefuente_honorarios'] }}
                                @endif
                            </span>
                            <span class="text-sm font-bold text-red-600">${{ number_format($retefuente, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($reteiva > 0)
                        <div class="flex justify-between items-center bg-gray-50 rounded-lg px-4 py-2">
                            <span class="text-sm text-gray-700">{{ \App\Models\PaymentOrder::ACCOUNTING_CODES['reteiva'] }}</span>
                            <span class="text-sm font-bold text-red-600">${{ number_format($reteiva, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($estampillaProdultoMayor > 0)
                        <div class="flex justify-between items-center bg-gray-50 rounded-lg px-4 py-2">
                            <span class="text-sm text-gray-700">{{ \App\Models\PaymentOrder::ACCOUNTING_CODES['estampilla_produlto_mayor'] }}</span>
                            <span class="text-sm font-bold text-orange-600">${{ number_format($estampillaProdultoMayor, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($estampillaProcultura > 0)
                        <div class="flex justify-between items-center bg-gray-50 rounded-lg px-4 py-2">
                            <span class="text-sm text-gray-700">{{ \App\Models\PaymentOrder::ACCOUNTING_CODES['estampilla_procultura'] }}</span>
                            <span class="text-sm font-bold text-orange-600">${{ number_format($estampillaProcultura, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($retencionIca > 0)
                        <div class="flex justify-between items-center bg-gray-50 rounded-lg px-4 py-2">
                            <span class="text-sm text-gray-700">{{ \App\Models\PaymentOrder::ACCOUNTING_CODES['retencion_ica'] }}</span>
                            <span class="text-sm font-bold text-orange-600">${{ number_format($retencionIca, 0, ',', '.') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- ─── SECCIÓN 10: Observaciones ──────────────────── --}}
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
                            <p class="text-sm text-gray-900">{{ $supplier?->municipality?->name ?? 'No registrado' }}</p>
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

                {{-- Valores del Pago --}}
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

                {{-- Distribución por Código de Gasto (si tiene líneas) --}}
                @if($paymentOrder->expenseLines && $paymentOrder->expenseLines->count() > 0)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Distribución por Código de Gasto
                        <span class="text-xs font-normal text-gray-400">({{ $paymentOrder->expenseLines->count() }} {{ $paymentOrder->expenseLines->count() === 1 ? 'código' : 'códigos' }})</span>
                    </h2>
                    @php
                        // Mapa budget_id → info de RP (fuente, banco, cuenta)
                        $rpMap = [];
                        foreach ($paymentOrder->contract->rps ?? [] as $rp) {
                            foreach ($rp->fundingSources as $rpFs) {
                                if ($rpFs->budget_id) {
                                    $rpMap[$rpFs->budget_id] = [
                                        'funding_source' => $rpFs->fundingSource ? ($rpFs->fundingSource->code . ' - ' . $rpFs->fundingSource->name) : null,
                                        'bank' => $rpFs->bank?->name,
                                        'account' => $rpFs->bankAccount ? ($rpFs->bankAccount->account_type . ' - ' . $rpFs->bankAccount->account_number) : null,
                                    ];
                                }
                            }
                        }
                    @endphp
                    <div class="space-y-3">
                        @foreach($paymentOrder->expenseLines as $el)
                        @php $elRpInfo = $rpMap[$el->expenseDistribution?->budget_id ?? 0] ?? []; @endphp
                        <div class="border border-gray-200 rounded-xl p-4 bg-gray-50">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-semibold text-gray-800">{{ $el->expenseCode?->code }} - {{ $el->expenseCode?->name }}</p>
                            </div>
                            @if(!empty($elRpInfo['funding_source']) || !empty($elRpInfo['bank']))
                                <div class="flex flex-wrap gap-x-4 gap-y-1 mb-2 text-xs text-gray-500">
                                    @if(!empty($elRpInfo['funding_source']))
                                        <span>Fuente: <span class="font-medium text-gray-700">{{ $elRpInfo['funding_source'] }}</span></span>
                                    @endif
                                    @if(!empty($elRpInfo['bank']))
                                        <span>Banco: <span class="font-medium text-gray-700">{{ $elRpInfo['bank'] }}</span></span>
                                    @endif
                                    @if(!empty($elRpInfo['account']))
                                        <span>Cuenta: <span class="font-medium text-gray-700">{{ $elRpInfo['account'] }}</span></span>
                                    @endif
                                </div>
                            @endif
                            <div class="grid grid-cols-2 md:grid-cols-6 gap-2 text-center">
                                <div class="bg-white rounded-lg p-2 border border-gray-200">
                                    <p class="text-[10px] text-gray-500">Subtotal</p>
                                    <p class="text-sm font-bold text-gray-900">${{ number_format($el->subtotal, 0, ',', '.') }}</p>
                                </div>
                                <div class="bg-white rounded-lg p-2 border border-gray-200">
                                    <p class="text-[10px] text-gray-500">IVA</p>
                                    <p class="text-sm font-bold text-gray-900">${{ number_format($el->iva, 0, ',', '.') }}</p>
                                </div>
                                <div class="bg-white rounded-lg p-2 border border-gray-200">
                                    <p class="text-[10px] text-gray-500">Retefuente</p>
                                    <p class="text-sm font-bold text-red-600">${{ number_format($el->retefuente, 0, ',', '.') }}</p>
                                </div>
                                <div class="bg-white rounded-lg p-2 border border-gray-200">
                                    <p class="text-[10px] text-gray-500">ReteIVA</p>
                                    <p class="text-sm font-bold text-red-600">${{ number_format($el->reteiva, 0, ',', '.') }}</p>
                                </div>
                                <div class="bg-white rounded-lg p-2 border border-gray-200">
                                    <p class="text-[10px] text-gray-500">Total Desc.</p>
                                    <p class="text-sm font-bold text-red-700">${{ number_format($el->total_retentions, 0, ',', '.') }}</p>
                                </div>
                                <div class="bg-emerald-50 rounded-lg p-2 border border-emerald-200">
                                    <p class="text-[10px] text-emerald-600">Neto</p>
                                    <p class="text-sm font-bold text-emerald-700">${{ number_format($el->net_payment, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Retenciones DIAN --}}
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
                            <p class="text-xs text-red-600 font-medium">Total Ret. DIAN</p>
                            <p class="text-lg font-bold text-red-800">${{ number_format((float)$paymentOrder->retefuente + (float)$paymentOrder->reteiva, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    {{-- Otros impuestos --}}
                    @if((float)$paymentOrder->estampilla_produlto_mayor > 0 || (float)$paymentOrder->estampilla_procultura > 0 || (float)$paymentOrder->retencion_ica > 0)
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 mt-4">Otros Impuestos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        @if((float)$paymentOrder->estampilla_produlto_mayor > 0)
                        <div class="bg-orange-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-orange-500">Estampilla Produlto Mayor</p>
                            <p class="text-lg font-bold text-orange-700">${{ number_format($paymentOrder->estampilla_produlto_mayor, 0, ',', '.') }}</p>
                        </div>
                        @endif
                        @if((float)$paymentOrder->estampilla_procultura > 0)
                        <div class="bg-orange-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-orange-500">Estampilla Procultura</p>
                            <p class="text-lg font-bold text-orange-700">${{ number_format($paymentOrder->estampilla_procultura, 0, ',', '.') }}</p>
                        </div>
                        @endif
                        @if((float)$paymentOrder->retencion_ica > 0)
                        <div class="bg-orange-50 rounded-xl p-3 text-center">
                            <p class="text-xs text-orange-500">Retención ICA</p>
                            <p class="text-lg font-bold text-orange-700">${{ number_format($paymentOrder->retencion_ica, 0, ',', '.') }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Neto --}}
                    <div class="bg-emerald-100 rounded-xl p-4 text-center">
                        <div class="flex justify-center items-center gap-6 mb-2">
                            <div>
                                <p class="text-xs text-gray-500">Total Factura</p>
                                <p class="text-sm font-bold text-gray-700">${{ number_format($paymentOrder->total, 0, ',', '.') }}</p>
                            </div>
                            <span class="text-gray-400">−</span>
                            <div>
                                <p class="text-xs text-red-500">Total Descuentos</p>
                                <p class="text-sm font-bold text-red-700">${{ number_format($paymentOrder->total_retentions, 0, ',', '.') }}</p>
                            </div>
                            <span class="text-gray-400">=</span>
                        </div>
                        <p class="text-sm text-emerald-700 font-medium">NETO PAGADO AL PROVEEDOR</p>
                        <p class="text-3xl font-bold text-emerald-800">${{ number_format($paymentOrder->net_payment, 0, ',', '.') }}</p>
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

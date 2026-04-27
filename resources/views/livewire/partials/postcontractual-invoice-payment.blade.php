{{-- Datos de Factura y Pago (compartido) --}}
@php
    $supplierInvoices = $supplierData['electronic_invoicing'] ?? true;
@endphp
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
        {{ $supplierInvoices ? 'Datos de Factura y Pago' : 'Datos de Pago' }}
    </h2>

    @if(!$supplierInvoices)
        @php
            $nextDocSupport = \App\Models\PaymentOrder::getNextDocumentSupportNumber(session('selected_school_id'));
            $school = \App\Models\School::find(session('selected_school_id'));
            $dianRange = $school?->dian_range_1 ?? 'N/A';
        @endphp
        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-xl text-sm text-yellow-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <span>Este proveedor <span class="font-medium">no factura electrónicamente</span>. Se generará documento soporte.</span>
                <p class="mt-1">Número de documento soporte: <span class="font-bold text-yellow-900">{{ $nextDocSupport }}</span> <span class="text-xs">(Rango DIAN: {{ $dianRange }})</span></p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-{{ $supplierInvoices ? '3' : '1' }} gap-4 mb-4">
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
        @if($supplierInvoices)
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
        @endif
    </div>

    @if($showFullPaymentToggle ?? false)
    {{-- ¿Pago completo? (solo para contratos) --}}
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
    </div>
    @endif

    {{-- Valores del pago --}}
    @php
        $isReadonly = ($paymentType === 'contract') && ($isFullPayment || $paymentMode === 'split');
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subtotal *</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                <input type="number" wire:model.live.debounce.300ms="paySubtotal" step="0.01" class="w-full rounded-xl border-gray-300 pl-7 focus:border-emerald-500 focus:ring-emerald-500 {{ $isReadonly ? 'bg-gray-50' : '' }}" {{ $isReadonly ? 'readonly' : '' }}>
            </div>
            @error('paySubtotal') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">IVA</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">$</span>
                <input type="number" wire:model.live.debounce.300ms="payIva" step="0.01" class="w-full rounded-xl border-gray-300 pl-7 focus:border-emerald-500 focus:ring-emerald-500 {{ $isReadonly ? 'bg-gray-50' : '' }}" {{ $isReadonly ? 'readonly' : '' }}>
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

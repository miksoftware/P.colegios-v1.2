{{-- Valor del Contrato + Fuentes (solo flujo contrato) --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Valor del Contrato
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div class="bg-gray-50 rounded-xl p-3 text-center">
            <p class="text-xs text-gray-500">Subtotal</p>
            <p class="text-lg font-bold text-gray-900">${{ number_format($contractData['subtotal'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3 text-center">
            <p class="text-xs text-gray-500">IVA</p>
            <p class="text-lg font-bold text-gray-900">${{ number_format($contractData['iva'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-emerald-50 rounded-xl p-3 text-center">
            <p class="text-xs text-emerald-600">Total Contrato</p>
            <p class="text-lg font-bold text-emerald-700">${{ number_format($contractData['total'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-amber-50 rounded-xl p-3 text-center">
            <p class="text-xs text-amber-600">Saldo Pendiente</p>
            <p class="text-lg font-bold text-amber-700">${{ number_format($contractData['remaining'], 2, ',', '.') }}</p>
        </div>
    </div>

    @if(count($fundingSourcesData) > 0)
    <div class="bg-blue-50 rounded-xl p-4">
        <p class="text-xs font-medium text-blue-700 uppercase mb-2">Distribución por Fuente de Financiación</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @foreach($fundingSourcesData as $fs)
                <div class="bg-white rounded-lg p-3 border border-blue-100">
                    <p class="text-xs text-gray-500">{{ $fs['name'] }}</p>
                    <p class="text-sm font-bold text-blue-700">${{ number_format($fs['amount'], 2, ',', '.') }}</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($contractData['total_paid'] > 0)
    <div class="mt-4 bg-orange-50 border border-orange-200 rounded-xl p-3">
        <p class="text-xs text-orange-700">
            Ya se han registrado pagos por <span class="font-bold">${{ number_format($contractData['total_paid'], 2, ',', '.') }}</span>.
            Saldo pendiente: <span class="font-bold">${{ number_format($contractData['remaining'], 2, ',', '.') }}</span>
        </p>
    </div>
    @endif
</div>

{{-- Datos del Proveedor (compartido entre flujo contrato y directo) --}}
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

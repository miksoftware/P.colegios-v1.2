{{-- Resumen Final + Cuenta Bancaria (compartido) --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Resumen de Descuentos y Neto a Pagar</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="bg-gray-50 rounded-xl p-3 text-center">
            <p class="text-xs text-gray-500">Total Factura</p>
            <p class="text-lg font-bold text-gray-900">${{ number_format($payTotal, 2, ',', '.') }}</p>
        </div>
        <div class="bg-red-50 rounded-xl p-3 text-center">
            <p class="text-xs text-red-500">Total Descuentos</p>
            <p class="text-lg font-bold text-red-700">- ${{ number_format($totalRetentions, 2, ',', '.') }}</p>
            <p class="text-[10px] text-red-400">DIAN: ${{ number_format($totalRetentionsDian, 2, ',', '.') }} + Otros: ${{ number_format($otherTaxesTotal, 2, ',', '.') }}</p>
        </div>
        <div class="bg-emerald-100 rounded-xl p-4 text-center">
            <p class="text-sm text-emerald-700 font-medium">VALOR NETO A PAGAR</p>
            <p class="text-3xl font-bold text-emerald-800">${{ number_format($netPayment, 2, ',', '.') }}</p>
        </div>
    </div>

    {{-- Cuenta bancaria del proveedor --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mt-4">
        <p class="text-xs font-medium text-blue-700 uppercase mb-2">Cuenta Bancaria del Proveedor (donde se realiza el pago)</p>

        @if(count($supplierBankAccounts) > 0)
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Seleccionar Cuenta Bancaria *</label>
                <select wire:model="selectedBankAccountId" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
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

        @if(!$showNewBankAccountForm)
            <button type="button" wire:click="toggleNewBankAccountForm" class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Agregar nueva cuenta bancaria
            </button>
        @else
            <div class="bg-white border border-blue-200 rounded-lg p-3 mt-2">
                <p class="text-xs font-medium text-gray-700 mb-2">Nueva Cuenta Bancaria</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Banco *</label>
                        <input type="text" wire:model="newBankName" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Bancolombia">
                        @error('newBankName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tipo de Cuenta *</label>
                        <select wire:model="newAccountType" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach(\App\Models\SupplierBankAccount::ACCOUNT_TYPES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">N° Cuenta *</label>
                        <input type="text" wire:model="newAccountNumber" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-mono" placeholder="123456789">
                        @error('newAccountNumber') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex gap-2 mt-3">
                    <button type="button" wire:click="saveNewBankAccount" class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">Guardar Cuenta</button>
                    <button type="button" wire:click="toggleNewBankAccountForm" class="px-3 py-1.5 bg-gray-200 text-gray-700 text-xs rounded-lg hover:bg-gray-300">Cancelar</button>
                </div>
            </div>
        @endif
    </div>
</div>

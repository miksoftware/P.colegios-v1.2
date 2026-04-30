<div>
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reporte General de Inventarios</h1>
            <p class="text-sm text-gray-500">Visualización y cálculo de depreciación a la fecha de corte.</p>
        </div>
        
        <div class="flex items-center gap-3 w-full md:w-auto">
            <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border shadow-sm">
                <label class="text-sm font-medium text-gray-700">Fecha de Corte:</label>
                <input type="date" wire:model.live="cutOffDate" class="border-0 bg-transparent text-sm font-bold text-gray-900 focus:ring-0 p-0">
            </div>

            <button wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="relative flex items-center justify-center gap-2 px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors shadow-sm whitespace-nowrap min-w-[180px] disabled:opacity-70 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="exportExcel" class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Exportar a Excel
                </span>
                <span wire:loading wire:target="exportExcel" class="flex items-center gap-2">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Descargando...
                </span>
            </button>
        </div>
    </div>

    <!-- Tabla Resumen en Pantalla -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Cta Contable</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">Artículo / Placa</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-500 uppercase tracking-wider">F. Compra</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-500 uppercase tracking-wider">V. Inicial</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">Meses Uso</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-500 uppercase tracking-wider">Depr. Mensual</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-500 uppercase tracking-wider">Depr. Acumulada</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-500 uppercase tracking-wider">Saldo (Libros)</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-500 uppercase tracking-wider">Estado / Baja</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($this->items as $item)
                        @php
                            $accumulated = $item->getAccumulatedDepreciation($parsedDate);
                            $netValue = $item->getNetBookValue($parsedDate);
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors {{ $item->inventory_discharge_id ? 'bg-red-50/30' : '' }}">
                            <td class="px-4 py-3 text-gray-600">
                                {{ $item->account->code }}
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $item->name }}
                                <span class="block text-gray-400 text-[10px]">{{ $item->current_tag }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $item->acquisition_date ? $item->acquisition_date->format('d/m/Y') : 'N/A' }}
                            </td>
                            <td class="px-4 py-3 font-medium text-right text-gray-900">
                                ${{ number_format($item->initial_value, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">
                                {{ $item->getMonthsInUse($parsedDate) }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600">
                                ${{ number_format($item->monthly_depreciation, 2) }}
                            </td>
                            <td class="px-4 py-3 font-medium text-right text-orange-600">
                                ${{ number_format($accumulated, 2) }}
                            </td>
                            <td class="px-4 py-3 font-bold text-right {{ $netValue > 0 ? 'text-green-600' : 'text-gray-500' }}">
                                ${{ number_format($netValue, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->inventory_discharge_id)
                                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-[10px] font-bold block mb-1">DADO DE BAJA</span>
                                    <span class="text-[10px] text-gray-500">{{ $item->discharge_info }}</span>
                                @else
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-[10px] font-bold">ACTIVO</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                No hay artículos en el inventario.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->items->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $this->items->links() }}
            </div>
        @endif
    </div>
</div>

<div>
    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Comprobante de Depreciación</h1>
            <p class="text-sm text-gray-500">Cálculo mensual por categoría contable · Descarga en Excel</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            {{-- Selector Año --}}
            <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border shadow-sm">
                <label class="text-sm font-medium text-gray-600">Año:</label>
                <select wire:model.live="year" class="border-0 bg-transparent text-sm font-bold text-gray-900 focus:ring-0 p-0">
                    @for($y = now()->year; $y >= now()->year - 10; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            {{-- Selector Mes --}}
            <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border shadow-sm">
                <label class="text-sm font-medium text-gray-600">Mes:</label>
                <select wire:model.live="month" class="border-0 bg-transparent text-sm font-bold text-gray-900 focus:ring-0 p-0">
                    @foreach($monthsEs as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Exportar --}}
            <button
                wire:click="exportExcel"
                wire:loading.attr="disabled"
                wire:target="exportExcel"
                class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove wire:target="exportExcel" class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Exportar Excel
                </span>
                <span wire:loading wire:target="exportExcel" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    Generando…
                </span>
            </button>
        </div>
    </div>

    {{-- Sin datos --}}
    @if($data['grand_total'] == 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-500 font-medium">No hay depreciación para {{ $monthsEs[$month] }} {{ $year }}</p>
            <p class="text-sm text-gray-400 mt-1">Verifique que existan artículos activos con cuentas de depreciación configuradas.</p>
        </div>

    @else
        {{-- TARJETAS RESUMEN --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-5">
                <p class="text-xs font-semibold text-blue-500 uppercase tracking-wide mb-1">Depreciación PP&amp;E</p>
                <p class="text-2xl font-bold text-blue-700">${{ number_format($data['ppe']['total'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ count($data['ppe']['rows']) }} categorías</p>
            </div>
            <div class="bg-white rounded-2xl border border-purple-100 shadow-sm p-5">
                <p class="text-xs font-semibold text-purple-500 uppercase tracking-wide mb-1">Amortización Intangibles</p>
                <p class="text-2xl font-bold text-purple-700">${{ number_format($data['intangible']['total'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ count($data['intangible']['rows']) }} categorías</p>
            </div>
            <div class="bg-white rounded-2xl border border-green-100 shadow-sm p-5">
                <p class="text-xs font-semibold text-green-500 uppercase tracking-wide mb-1">Total del período</p>
                <p class="text-2xl font-bold text-green-700">${{ number_format($data['grand_total'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $data['period_label'] }}</p>
            </div>
        </div>

        {{-- COMPROBANTE --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

            {{-- Título del comprobante --}}
            <div class="bg-gradient-to-r from-blue-900 to-blue-700 px-6 py-4 text-white">
                <h2 class="text-base font-bold tracking-wide uppercase">Comprobante de Depreciación y Amortización</h2>
                <p class="text-blue-200 text-sm mt-0.5">Período: {{ $data['period_label'] }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    {{-- Cabecera de columnas --}}
                    <thead>
                        <tr class="bg-blue-50 border-b border-blue-200">
                            <th class="px-4 py-3 text-left font-bold text-blue-900 w-36">CÓDIGO</th>
                            <th class="px-4 py-3 text-left font-bold text-blue-900">CONCEPTO</th>
                            <th class="px-4 py-3 text-right font-bold text-blue-900 w-40">DEBE</th>
                            <th class="px-4 py-3 text-right font-bold text-blue-900 w-40">HABER</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">

                        {{-- SECCIÓN PP&E --}}
                        @if(!empty($data['ppe']['rows']))
                            {{-- Grupo padre --}}
                            <tr class="bg-blue-900">
                                <td colspan="4" class="px-4 py-2 text-xs font-bold text-white uppercase tracking-wider">
                                    5.3 · Deterioro, Depreciaciones, Amortizaciones y Provisiones
                                </td>
                            </tr>
                            <tr class="bg-blue-50">
                                <td colspan="4" class="px-4 py-2 text-xs font-semibold text-blue-800 italic">
                                    &nbsp;&nbsp;&nbsp;5.3.60 · Depreciación de Propiedades, Planta y Equipo
                                </td>
                            </tr>

                            @foreach($data['ppe']['rows'] as $r)
                                {{-- Fila DEBE --}}
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2.5 font-mono text-xs font-semibold text-blue-800">{{ $r['debit'] }}</td>
                                    <td class="px-4 py-2.5 text-gray-800">{{ $r['name'] }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-gray-900">${{ number_format($r['amount'], 2) }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-400">—</td>
                                </tr>
                                {{-- Fila HABER --}}
                                <tr class="bg-amber-50 hover:bg-amber-100">
                                    <td class="px-4 py-2.5 font-mono text-xs font-semibold text-amber-700 pl-8">{{ $r['credit'] }}</td>
                                    <td class="px-4 py-2.5 text-gray-600 italic pl-8">{{ $r['name'] }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-400">—</td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-amber-700">${{ number_format($r['amount'], 2) }}</td>
                                </tr>
                            @endforeach

                            {{-- Subtotal PP&E --}}
                            <tr class="bg-blue-100 border-t border-blue-300">
                                <td colspan="2" class="px-4 py-2 text-xs font-bold text-blue-900 uppercase">Subtotal Depreciación PP&amp;E</td>
                                <td class="px-4 py-2 text-right font-bold text-blue-900">${{ number_format($data['ppe']['total'], 2) }}</td>
                                <td class="px-4 py-2 text-right font-bold text-blue-900">${{ number_format($data['ppe']['total'], 2) }}</td>
                            </tr>
                        @endif

                        {{-- SECCIÓN INTANGIBLES --}}
                        @if(!empty($data['intangible']['rows']))
                            <tr class="bg-purple-900">
                                <td colspan="4" class="px-4 py-2 text-xs font-bold text-white uppercase tracking-wider">
                                    5.3.66 · Amortización de Activos Intangibles
                                </td>
                            </tr>

                            @foreach($data['intangible']['rows'] as $r)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2.5 font-mono text-xs font-semibold text-purple-800">{{ $r['debit'] }}</td>
                                    <td class="px-4 py-2.5 text-gray-800">{{ $r['name'] }}</td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-gray-900">${{ number_format($r['amount'], 2) }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-400">—</td>
                                </tr>
                                <tr class="bg-amber-50 hover:bg-amber-100">
                                    <td class="px-4 py-2.5 font-mono text-xs font-semibold text-amber-700 pl-8">{{ $r['credit'] }}</td>
                                    <td class="px-4 py-2.5 text-gray-600 italic pl-8">{{ $r['name'] }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-400">—</td>
                                    <td class="px-4 py-2.5 text-right font-semibold text-amber-700">${{ number_format($r['amount'], 2) }}</td>
                                </tr>
                            @endforeach

                            <tr class="bg-purple-100 border-t border-purple-300">
                                <td colspan="2" class="px-4 py-2 text-xs font-bold text-purple-900 uppercase">Subtotal Amortización Intangibles</td>
                                <td class="px-4 py-2 text-right font-bold text-purple-900">${{ number_format($data['intangible']['total'], 2) }}</td>
                                <td class="px-4 py-2 text-right font-bold text-purple-900">${{ number_format($data['intangible']['total'], 2) }}</td>
                            </tr>
                        @endif

                        {{-- TOTAL GENERAL --}}
                        <tr class="bg-blue-900">
                            <td colspan="2" class="px-4 py-3 text-sm font-bold text-white uppercase tracking-wide">TOTAL</td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-white">${{ number_format($data['grand_total'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-white">${{ number_format($data['grand_total'], 2) }}</td>
                        </tr>

                    </tbody>
                </table>
            </div>

            {{-- Nota informativa --}}
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                <p class="text-xs text-gray-500">
                    <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Las filas en fondo <span class="bg-white border px-1 rounded text-gray-700">blanco</span> corresponden al débito (cuenta de gasto 5.3.xx) y las
                    <span class="bg-amber-100 px-1 rounded text-amber-700">amarillas</span> al crédito (depreciación acumulada 1.6.85.xx / 1.9.75.xx).
                    Los montos son iguales porque son las dos caras del mismo asiento contable.
                </p>
            </div>
        </div>
    @endif
</div>

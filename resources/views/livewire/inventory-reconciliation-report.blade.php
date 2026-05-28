<div>
    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Conciliación Propiedad, Planta y Equipo</h1>
            <p class="text-sm text-gray-500">Formato de conciliación entre libros contables e inventario físico</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            {{-- Fecha de corte --}}
            <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border shadow-sm">
                <label class="text-sm font-medium text-gray-600 whitespace-nowrap">Fecha de corte:</label>
                <input
                    type="date"
                    wire:model.live="cutoffDate"
                    class="border-0 bg-transparent text-sm font-bold text-gray-900 focus:ring-0 p-0"
                />
            </div>

            {{-- Nombre almacenista --}}
            <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border shadow-sm">
                <label class="text-sm font-medium text-gray-600 whitespace-nowrap">Almacenista:</label>
                <input
                    type="text"
                    wire:model.live="storageKeeperName"
                    placeholder="Nombre almacenista"
                    class="border-0 bg-transparent text-sm text-gray-900 focus:ring-0 p-0 w-40"
                />
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
    @if(empty($data['asset_rows']))
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-500 font-medium">No hay artículos devolutivos activos para esta fecha de corte.</p>
            <p class="text-sm text-gray-400 mt-1">Verifique que existan artículos con tipo "Devolutivo" y fecha de adquisición anterior al corte.</p>
        </div>

    @else
        {{-- TARJETAS RESUMEN --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-5">
                <p class="text-xs font-semibold text-blue-500 uppercase tracking-wide mb-1">Saldo en Libros (Activos)</p>
                <p class="text-2xl font-bold text-blue-700">${{ number_format($data['asset_total'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ count($data['asset_rows']) }} cuentas</p>
            </div>
            <div class="bg-white rounded-2xl border border-orange-100 shadow-sm p-5">
                <p class="text-xs font-semibold text-orange-500 uppercase tracking-wide mb-1">Depreciación Acumulada</p>
                <p class="text-2xl font-bold text-orange-700">${{ number_format($data['dep_total'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ count($data['dep_rows']) }} cuentas · corte {{ $data['cutoff_label'] }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-green-100 shadow-sm p-5">
                <p class="text-xs font-semibold text-green-500 uppercase tracking-wide mb-1">Valor Neto PP&E</p>
                <p class="text-2xl font-bold text-green-700">${{ number_format($data['net_total'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-1">Libros − Depreciación</p>
            </div>
        </div>

        {{-- TABLA DE CONCILIACIÓN --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

            {{-- Título --}}
            <div class="bg-gradient-to-r from-blue-900 to-blue-700 px-6 py-4 text-white">
                <h2 class="text-base font-bold tracking-wide uppercase">Formato para Conciliación Propiedad Planta y Equipo</h2>
                <p class="text-blue-200 text-sm mt-0.5">Proceso Gestión Financiera y Contable · Corte: {{ $data['cutoff_label'] }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-200 border-b border-gray-300">
                            <th class="px-3 py-3 text-center font-bold text-gray-800 w-32">CÓDIGO</th>
                            <th class="px-3 py-3 text-left font-bold text-gray-800">DESCRIPCIÓN</th>
                            <th class="px-3 py-3 text-right font-bold text-gray-800 w-40">
                                SALDO EN LIBROS<br>
                                <span class="text-xs font-normal text-gray-500">(SIIF NACIÓN)</span>
                            </th>
                            <th class="px-3 py-3 text-right font-bold text-gray-800 w-40">
                                SALDO INVENTARIO<br>
                                <span class="text-xs font-normal text-gray-500">FÍSICO</span>
                            </th>
                            <th class="px-3 py-3 text-right font-bold text-gray-800 w-32">DIFERENCIAS</th>
                            <th class="px-3 py-3 text-center font-bold text-gray-800 w-32">OBSERVACIONES</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">

                        {{-- ═══ SECCIÓN ACTIVOS ═══ --}}
                        @foreach($data['asset_rows'] as $r)
                            <tr class="hover:bg-blue-50">
                                <td class="px-3 py-2.5 text-center font-mono text-xs font-semibold text-blue-800">{{ $r['code'] }}</td>
                                <td class="px-3 py-2.5 text-gray-800">{{ $r['name'] }}</td>
                                <td class="px-3 py-2.5 text-right font-semibold text-gray-900">${{ number_format($r['books_value'], 2) }}</td>
                                <td class="px-3 py-2.5 text-right font-semibold text-gray-900">${{ number_format($r['books_value'], 2) }}</td>
                                <td class="px-3 py-2.5 text-right text-gray-500">$0,00</td>
                                <td class="px-3 py-2.5 text-center text-gray-400">—</td>
                            </tr>
                        @endforeach

                        {{-- Total activos --}}
                        <tr class="bg-gray-200 border-y border-gray-400">
                            <td colspan="2" class="px-3 py-2.5 font-bold text-gray-800 uppercase text-center">TOTAL</td>
                            <td class="px-3 py-2.5 text-right font-bold text-gray-900">${{ number_format($data['asset_total'], 2) }}</td>
                            <td class="px-3 py-2.5 text-right font-bold text-gray-900">${{ number_format($data['asset_total'], 2) }}</td>
                            <td class="px-3 py-2.5 text-right font-bold text-gray-700">$0,00</td>
                            <td class="px-3 py-2.5"></td>
                        </tr>

                        {{-- Spacer --}}
                        <tr class="h-3 bg-gray-50"><td colspan="6"></td></tr>

                        {{-- ═══ SECCIÓN DEPRECIACIÓN ═══ --}}
                        @if(!empty($data['dep_rows']))
                            <tr class="bg-gray-300">
                                <td class="px-3 py-2.5 font-bold text-gray-800">1685</td>
                                <td colspan="5" class="px-3 py-2.5 font-bold text-gray-800 uppercase">Depreciación Acumulada (CR)</td>
                            </tr>

                            @foreach($data['dep_rows'] as $r)
                                <tr class="hover:bg-orange-50">
                                    <td class="px-3 py-2.5 text-center font-mono text-xs font-semibold text-orange-700">{{ $r['code'] }}</td>
                                    <td class="px-3 py-2.5 text-gray-700">&nbsp;&nbsp;{{ $r['name'] }}</td>
                                    <td class="px-3 py-2.5 text-right text-gray-700">$ {{ number_format($r['books_value'], 2) }}</td>
                                    <td class="px-3 py-2.5 text-right text-gray-700">$ {{ number_format($r['books_value'], 2) }}</td>
                                    <td class="px-3 py-2.5 text-right text-gray-500">$0,00</td>
                                    <td class="px-3 py-2.5 text-center text-gray-400">—</td>
                                </tr>
                            @endforeach

                            {{-- Total depreciación --}}
                            <tr class="bg-gray-200 border-y border-gray-400">
                                <td colspan="2" class="px-3 py-2.5 font-bold text-gray-800 uppercase text-center">TOTAL</td>
                                <td class="px-3 py-2.5 text-right font-bold text-gray-900">${{ number_format($data['dep_total'], 2) }}</td>
                                <td class="px-3 py-2.5 text-right font-bold text-gray-900">${{ number_format($data['dep_total'], 2) }}</td>
                                <td class="px-3 py-2.5 text-right font-bold text-gray-700">$0,00</td>
                                <td class="px-3 py-2.5"></td>
                            </tr>
                        @endif

                        {{-- Spacer --}}
                        <tr class="h-3 bg-gray-50"><td colspan="6"></td></tr>

                        {{-- ═══ TOTAL PP&E ═══ --}}
                        <tr class="bg-blue-900">
                            <td colspan="2" class="px-3 py-3 font-bold text-white uppercase text-center text-sm tracking-wide">
                                TOTAL PROPIEDAD PLANTA Y EQUIPO
                            </td>
                            <td class="px-3 py-3 text-right font-bold text-white text-sm">${{ number_format($data['net_total'], 2) }}</td>
                            <td class="px-3 py-3 text-right font-bold text-white text-sm">${{ number_format($data['net_total'], 2) }}</td>
                            <td class="px-3 py-3 text-right font-bold text-white text-sm">$0,00</td>
                            <td class="px-3 py-3"></td>
                        </tr>

                    </tbody>
                </table>
            </div>

            {{-- Firmas --}}
            <div class="border-t border-gray-200 mt-4">
                <div class="px-6 pt-3 pb-1">
                    <p class="text-xs font-bold text-gray-600">Análisis</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="border border-gray-300 px-3 py-2 text-center font-bold">PROCESO RESPONSABLE</th>
                                <th class="border border-gray-300 px-3 py-2 text-center font-bold">SOPORTES DE LA CONCILIACIÓN</th>
                                <th class="border border-gray-300 px-3 py-2 text-center font-bold">ELABORACIÓN</th>
                                <th class="border border-gray-300 px-3 py-2 text-center font-bold">REVISIÓN</th>
                                <th class="border border-gray-300 px-3 py-2 text-center font-bold">APROBACIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-300 px-3 py-2">Pagaduría y Contabilidad</td>
                                <td class="border border-gray-300 px-3 py-2">Inventario Físico presentado por la dependencia</td>
                                <td class="border border-gray-300 px-3 py-2 text-center">{{ $data['cutoff_label'] }}</td>
                                <td class="border border-gray-300 px-3 py-2 text-center">{{ $storageKeeperName }}</td>
                                <td class="border border-gray-300 px-3 py-2 text-center">Aprobado</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 px-3 py-2"></td>
                                <td class="border border-gray-300 px-3 py-2">Estado de Situación Financiera a {{ strtolower($data['cutoff_label']) }}</td>
                                <td class="border border-gray-300 px-3 py-2"></td>
                                <td class="border border-gray-300 px-3 py-2"></td>
                                <td class="border border-gray-300 px-3 py-2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Nota --}}
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                <p class="text-xs text-gray-500">
                    <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Las columnas <strong>SALDO EN LIBROS</strong> e <strong>INVENTARIO FÍSICO</strong> muestran el mismo valor del sistema. El Excel exportado permite editar la columna de inventario físico para registrar diferencias encontradas en el conteo.
                </p>
            </div>
        </div>
    @endif
</div>

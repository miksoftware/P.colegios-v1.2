<div>
    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Balance de Inventarios</h1>
            <p class="text-sm text-gray-500">Saldo inicial · Ingresos · Salidas · Saldo final de la vigencia</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            {{-- Tipo --}}
            <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border shadow-sm">
                <label class="text-sm font-medium text-gray-600 whitespace-nowrap">Tipo:</label>
                <select wire:model.live="inventoryType" class="border-0 bg-transparent text-sm font-bold text-gray-900 focus:ring-0 p-0">
                    <option value="devolutivo">Devolutivo (PP&E)</option>
                    <option value="consumo">Consumo</option>
                </select>
            </div>

            {{-- Año --}}
            <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border shadow-sm">
                <label class="text-sm font-medium text-gray-600 whitespace-nowrap">Año:</label>
                <select wire:model.live="year" class="border-0 bg-transparent text-sm font-bold text-gray-900 focus:ring-0 p-0">
                    @for($y = now()->year + 1; $y >= now()->year - 10; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            {{-- Fecha de corte --}}
            <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border shadow-sm">
                <label class="text-sm font-medium text-gray-600 whitespace-nowrap">Corte:</label>
                <input
                    type="date"
                    wire:model.live="cutoffDate"
                    class="border-0 bg-transparent text-sm font-bold text-gray-900 focus:ring-0 p-0"
                />
            </div>

            {{-- Almacenista --}}
            <div class="flex items-center gap-2 bg-white px-3 py-2 rounded-xl border shadow-sm">
                <label class="text-sm font-medium text-gray-600 whitespace-nowrap">Almacenista:</label>
                <input
                    type="text"
                    wire:model.live="storageKeeperName"
                    placeholder="Nombre"
                    class="border-0 bg-transparent text-sm text-gray-900 focus:ring-0 p-0 w-36"
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
    @if(empty($data['rows']))
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-500 font-medium">No hay artículos para la vigencia {{ $year }}.</p>
            <p class="text-sm text-gray-400 mt-1">Verifique que existan artículos con tipo "{{ $inventoryType === 'devolutivo' ? 'Devolutivo' : 'Consumo' }}" y fecha de adquisición en este período.</p>
        </div>

    @else
        {{-- TARJETAS RESUMEN --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Saldo Inicial</p>
                <p class="text-xl font-bold text-gray-800">${{ number_format($data['totalInicial'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Ene 1, {{ $year }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-green-100 shadow-sm p-4">
                <p class="text-xs font-semibold text-green-600 uppercase tracking-wide mb-1">Ingresos / Entradas</p>
                <p class="text-xl font-bold text-green-700">+${{ number_format($data['totalIngresos'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Vigencia {{ $year }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-4">
                <p class="text-xs font-semibold text-red-500 uppercase tracking-wide mb-1">Salidas / Bajas</p>
                <p class="text-xl font-bold text-red-600">-${{ number_format($data['totalSalidas'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Vigencia {{ $year }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-4">
                <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide mb-1">Saldo Final</p>
                <p class="text-xl font-bold text-blue-700">${{ number_format($data['totalFinal'], 2) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $data['cutoffLabel'] }}</p>
            </div>
        </div>

        {{-- TABLA BALANCE --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

            {{-- Título --}}
            <div class="bg-gradient-to-r from-gray-700 to-gray-600 px-6 py-4 text-white">
                <h2 class="text-base font-bold tracking-wide uppercase">Balance de Inventarios — {{ $inventoryType === 'devolutivo' ? 'Propiedad, Planta y Equipo' : 'Elementos de Consumo Controlado' }}</h2>
                <p class="text-gray-300 text-sm mt-0.5">Vigencia {{ $year }} · Corte: {{ $data['cutoffLabel'] }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-200 border-b border-gray-300">
                            <th class="px-3 py-3 text-center font-bold text-gray-800 w-28">CÓDIGO</th>
                            <th class="px-3 py-3 text-left font-bold text-gray-800">DESCRIPCIÓN</th>
                            <th class="px-3 py-3 text-right font-bold text-gray-800 w-36">
                                SALDO INICIAL<br>
                                <span class="text-xs font-normal text-gray-500">A ENERO DE {{ $year }}</span>
                            </th>
                            <th class="px-3 py-3 text-right font-bold text-green-700 w-36">
                                INGRESOS<br>
                                <span class="text-xs font-normal text-gray-500">ENTRADAS</span>
                            </th>
                            <th class="px-3 py-3 text-right font-bold text-red-600 w-36">
                                SALIDAS<br>
                                <span class="text-xs font-normal text-gray-500">BAJAS</span>
                            </th>
                            <th class="px-3 py-3 text-right font-bold text-blue-700 w-36">
                                SALDO FINAL<br>
                                <span class="text-xs font-normal text-gray-500">VIGENCIA {{ $year }}</span>
                            </th>
                            <th class="px-3 py-3 text-center font-bold text-gray-800 w-28">OBSERV.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">

                        @foreach($data['rows'] as $r)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2.5 text-center font-mono text-xs font-semibold text-gray-700">{{ $r['code'] }}</td>
                                <td class="px-3 py-2.5 text-gray-800">{{ $r['name'] }}</td>
                                <td class="px-3 py-2.5 text-right text-gray-700">${{ number_format($r['inicial'], 2) }}</td>
                                <td class="px-3 py-2.5 text-right {{ $r['ingresos'] > 0 ? 'text-green-700 font-semibold' : 'text-gray-400' }}">
                                    ${{ number_format($r['ingresos'], 2) }}
                                </td>
                                <td class="px-3 py-2.5 text-right {{ $r['salidas'] > 0 ? 'text-red-600 font-semibold' : 'text-gray-400' }}">
                                    ${{ number_format($r['salidas'], 2) }}
                                </td>
                                <td class="px-3 py-2.5 text-right font-bold text-blue-800">${{ number_format($r['final'], 2) }}</td>
                                <td class="px-3 py-2.5 text-center text-gray-400">—</td>
                            </tr>
                        @endforeach

                        {{-- Filas vacías de relleno (estética) --}}
                        @for($i = 0; $i < max(0, 3 - count($data['rows'])); $i++)
                            <tr class="h-8"><td colspan="7" class="border-b border-gray-100"></td></tr>
                        @endfor

                        {{-- Spacer --}}
                        <tr class="h-3 bg-gray-50"><td colspan="7"></td></tr>

                        {{-- TOTAL --}}
                        <tr class="bg-gray-800">
                            <td colspan="2" class="px-3 py-3 font-bold text-white uppercase text-center text-sm tracking-wide">
                                {{ $data['totalLabel'] }}
                            </td>
                            <td class="px-3 py-3 text-right font-bold text-white text-sm">
                                ${{ number_format($data['totalInicial'], 2) }}
                            </td>
                            <td class="px-3 py-3 text-right font-bold text-green-300 text-sm">
                                ${{ number_format($data['totalIngresos'], 2) }}
                            </td>
                            <td class="px-3 py-3 text-right font-bold text-red-300 text-sm">
                                ${{ number_format($data['totalSalidas'], 2) }}
                            </td>
                            <td class="px-3 py-3 text-right font-bold text-blue-300 text-sm">
                                ${{ number_format($data['totalFinal'], 2) }}
                            </td>
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
                                <td class="border border-gray-300 px-3 py-2 text-center">{{ $data['cutoffLabel'] }}</td>
                                <td class="border border-gray-300 px-3 py-2 text-center">{{ $storageKeeperName }}</td>
                                <td class="border border-gray-300 px-3 py-2 text-center">Aprobado</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 px-3 py-2"></td>
                                <td class="border border-gray-300 px-3 py-2">Estado de Situación Financiera a {{ strtolower($data['cutoffLabel']) }}</td>
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
                    <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <strong>Saldo Inicial</strong>: artículos adquiridos antes del 1 ene {{ $year }} y aún no dados de baja.
                    <strong>Ingresos</strong>: artículos adquiridos entre 1 ene y la fecha de corte.
                    <strong>Salidas</strong>: artículos dados de baja mediante acta entre 1 ene y la fecha de corte.
                    <strong>Saldo Final</strong> = Inicial + Ingresos − Salidas.
                </p>
            </div>
        </div>
    @endif
</div>

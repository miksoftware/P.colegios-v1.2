<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Gastos - Contraloría</h1>
                <p class="text-gray-500 mt-1">PAC y pagos por código de gasto</p>
            </div>
            @can('reports.export')
            <button id="btn-export-expense"
                class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Exportar Excel
            </button>
            @endcan
        </div>

        {{-- Info del Colegio --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><span class="text-gray-500">NIT (Sin DV):</span> <span class="font-semibold">{{ preg_replace('/-\d+$/', '', $school->nit ?? '') }}</span></div>
                <div><span class="text-gray-500">Institución:</span> <span class="font-semibold">{{ $school->name }}</span></div>
                <div><span class="text-gray-500">Dirección:</span> <span class="font-semibold">{{ $school->address ?? 'ND' }}</span></div>
                <div><span class="text-gray-500">Vigencia:</span> <span class="font-semibold">{{ $filterYear }}</span></div>
            </div>
        </div>

        {{-- Filtro --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia</label>
                <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                    @for($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>

        {{-- Tarjetas resumen --}}
        @php
            $totalRegistros = count($rows);
        @endphp
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Rubros</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalRegistros }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">PAC Período Rendido</p>
                <p class="text-xl font-bold text-blue-700 mt-1">${{ number_format($totals['pac_periodo'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">PAC Situado</p>
                <p class="text-xl font-bold text-indigo-700 mt-1">${{ number_format($totals['pac_situado'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Pagos</p>
                <p class="text-xl font-bold text-emerald-600 mt-1">${{ number_format($totals['pago'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">Gastos — {{ $totalRegistros }} código(s)</h2>
            </div>

            @if($totalRegistros > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs" id="table-expense">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Código Rubro Presupuestal</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Nombre Rubro Presupuestal</th>
                            <th class="px-3 py-3 text-right font-semibold text-blue-700 uppercase tracking-wider whitespace-nowrap">PAC Período Rendido</th>
                            <th class="px-3 py-3 text-right font-semibold text-gray-600 uppercase tracking-wider">Anticipos</th>
                            <th class="px-3 py-3 text-right font-semibold text-green-700 uppercase tracking-wider">Adiciones</th>
                            <th class="px-3 py-3 text-right font-semibold text-red-600 uppercase tracking-wider">Reducciones</th>
                            <th class="px-3 py-3 text-right font-semibold text-gray-600 uppercase tracking-wider">Aplazamientos</th>
                            <th class="px-3 py-3 text-right font-semibold text-indigo-700 uppercase tracking-wider whitespace-nowrap">PAC Situado</th>
                            <th class="px-3 py-3 text-right font-semibold text-emerald-700 uppercase tracking-wider">Pago</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($rows as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-800 font-medium">{{ $row['codigo'] }}</td>
                            <td class="px-3 py-2 text-gray-700 max-w-xs">{{ $row['nombre'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-mono text-blue-700">${{ number_format($row['pac_periodo'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-mono text-gray-500">0</td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-mono text-green-700">${{ number_format($row['adiciones'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-mono text-red-600">${{ number_format($row['reducciones'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-mono text-gray-500">0</td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-mono text-indigo-700 font-semibold">${{ number_format($row['pac_situado'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-mono text-emerald-700 font-semibold">${{ number_format($row['pago'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr>
                            <td colspan="2" class="px-3 py-3 font-bold text-gray-700 text-xs uppercase">Totales</td>
                            <td class="px-3 py-3 text-right font-bold text-blue-700 font-mono whitespace-nowrap">${{ number_format($totals['pac_periodo'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right font-bold text-gray-500 font-mono">0</td>
                            <td class="px-3 py-3 text-right font-bold text-green-700 font-mono whitespace-nowrap">${{ number_format($totals['adiciones'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right font-bold text-red-600 font-mono whitespace-nowrap">${{ number_format($totals['reducciones'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right font-bold text-gray-500 font-mono">0</td>
                            <td class="px-3 py-3 text-right font-bold text-indigo-700 font-mono whitespace-nowrap">${{ number_format($totals['pac_situado'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right font-bold text-emerald-700 font-mono whitespace-nowrap">${{ number_format($totals['pago'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                <svg class="w-16 h-16 mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                </svg>
                <p class="text-lg font-medium">Sin registros</p>
                <p class="text-sm mt-1">No se encontraron gastos para la vigencia {{ $filterYear }}.</p>
            </div>
            @endif
        </div>

    </div>
</div>

@push('scripts')
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('btn-export-expense');
    if (!btn) return;

    btn.addEventListener('click', function () {
        var schoolName = @json($school->name ?? 'Colegio');
        var year       = @json($filterYear);
        var rows       = @json($rows);
        var totals     = @json($totals);

        var headers = [
            'Código Rubro Presupuestal',
            'Nombre Rubro Presupuestal',
            'PAC Período Rendido',
            'Anticipos',
            'Adiciones',
            'Reducciones',
            'Aplazamientos',
            'PAC Situado',
            'Pago',
        ];

        var dataRows = rows.map(function (r) {
            return [
                r.codigo,
                r.nombre,
                r.pac_periodo,
                0,
                r.adiciones,
                r.reducciones,
                0,
                r.pac_situado,
                r.pago,
            ];
        });

        // Totals row
        dataRows.push([
            '', 'TOTALES',
            totals.pac_periodo,
            0,
            totals.adiciones,
            totals.reducciones,
            0,
            totals.pac_situado,
            totals.pago,
        ]);

        var ws = XLSX.utils.aoa_to_sheet([headers].concat(dataRows));

        ws['!cols'] = [
            { wch: 26 }, // Código Rubro
            { wch: 40 }, // Nombre Rubro
            { wch: 22 }, // PAC Período Rendido
            { wch: 12 }, // Anticipos
            { wch: 16 }, // Adiciones
            { wch: 16 }, // Reducciones
            { wch: 16 }, // Aplazamientos
            { wch: 18 }, // PAC Situado
            { wch: 18 }, // Pago
        ];

        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Gastos');
        XLSX.writeFile(wb, 'Contraloria_Gastos_' + schoolName + '_' + year + '.xlsx');
    });
});
</script>
@endpush

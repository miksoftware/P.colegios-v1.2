<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Contratos - Contraloría</h1>
                <p class="text-gray-500 mt-1">CDPs y Registros Presupuestales por contrato</p>
            </div>
            @can('reports.export')
            <button id="btn-export-contracts"
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
            $totalCdp = collect($rows)->sum('valor_cdp');
            $totalRp  = collect($rows)->sum('valor_rp');
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Contratos</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ count($rows) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Valor Total CDPs</p>
                <p class="text-2xl font-bold text-blue-700 mt-1">${{ number_format($totalCdp, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Valor Total RPs</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">${{ number_format($totalRp, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">Contratos — {{ count($rows) }} registro(s)</h2>
            </div>

            @if(count($rows) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs" id="table-contracts">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Código Rubro Presupuestal</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Nombre Rubro Presupuestal</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Número Del CDP</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Fecha Del CDP</th>
                            <th class="px-3 py-3 text-right font-semibold text-blue-700 uppercase tracking-wider whitespace-nowrap">Valor Del CDP</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">No. Registro Presupuestal</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Fecha Registro Presupuestal</th>
                            <th class="px-3 py-3 text-right font-semibold text-emerald-700 uppercase tracking-wider whitespace-nowrap">Valor Registro Presupuestal</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Beneficiario</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Cédula / NIT</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($rows as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-800 font-medium">{{ $row['codigo_rubro'] }}</td>
                            <td class="px-3 py-2 text-gray-700 max-w-xs">{{ $row['nombre_rubro'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-700">{{ $row['num_cdp'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-700">{{ $row['fecha_cdp'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-mono text-blue-700">${{ number_format($row['valor_cdp'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-700">{{ $row['num_rp'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-700">{{ $row['fecha_rp'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-mono text-emerald-700 font-semibold">${{ number_format($row['valor_rp'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-800 font-medium">{{ $row['beneficiario'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-700">{{ $row['nit'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr>
                            <td colspan="4" class="px-3 py-3 font-bold text-gray-700 text-xs uppercase">Totales</td>
                            <td class="px-3 py-3 text-right font-bold text-blue-700 font-mono whitespace-nowrap">${{ number_format($totalCdp, 0, ',', '.') }}</td>
                            <td colspan="2"></td>
                            <td class="px-3 py-3 text-right font-bold text-emerald-700 font-mono whitespace-nowrap">${{ number_format($totalRp, 0, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                <svg class="w-16 h-16 mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-lg font-medium">Sin registros</p>
                <p class="text-sm mt-1">No se encontraron contratos para la vigencia {{ $filterYear }}.</p>
            </div>
            @endif
        </div>

    </div>
</div>

@push('scripts')
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('btn-export-contracts');
    if (!btn) return;

    btn.addEventListener('click', function () {
        var schoolName = @json($school->name ?? 'Colegio');
        var year       = @json($filterYear);
        var rows       = @json($rows);

        var headers = [
            'Codigo Rubro Presupuestal',
            'Nombre Rubro Presupuestal',
            'Número Del CDP',
            'Fecha Del CDP',
            'Valor Del CDP',
            'Numero Registro Presupuestal',
            'Fecha De Registro Presupuestal',
            'Valor Del Registro Presupuestal',
            'Beneficiario',
            'Cedula O Nit',
        ];

        var dataRows = rows.map(function (r) {
            return [
                r.codigo_rubro,
                r.nombre_rubro,
                r.num_cdp,
                r.fecha_cdp,
                r.valor_cdp,
                r.num_rp,
                r.fecha_rp,
                r.valor_rp,
                r.beneficiario,
                r.nit,
            ];
        });

        // Totals row
        var totalCdp = rows.reduce(function (s, r) { return s + r.valor_cdp; }, 0);
        var totalRp  = rows.reduce(function (s, r) { return s + r.valor_rp; }, 0);
        dataRows.push(['', '', '', 'TOTALES', totalCdp, '', '', totalRp, '', '']);

        var ws = XLSX.utils.aoa_to_sheet([headers].concat(dataRows));

        ws['!cols'] = [
            { wch: 22 }, // Código Rubro
            { wch: 40 }, // Nombre Rubro
            { wch: 14 }, // Número CDP
            { wch: 14 }, // Fecha CDP
            { wch: 20 }, // Valor CDP
            { wch: 22 }, // Número RP
            { wch: 22 }, // Fecha RP
            { wch: 24 }, // Valor RP
            { wch: 30 }, // Beneficiario
            { wch: 16 }, // NIT
        ];

        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Contratos');
        XLSX.writeFile(wb, 'Contraloria_Contratos_' + schoolName + '_' + year + '.xlsx');
    });
});
</script>
@endpush

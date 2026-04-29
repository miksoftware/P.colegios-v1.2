<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Ingresos - Contraloría</h1>
                <p class="text-gray-500 mt-1">Recaudos reales por rubro presupuestal</p>
            </div>
            @can('reports.export')
            <button id="btn-export-income"
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
            $totalValor    = collect($rows)->sum('valor');
            $totalRegistros = count($rows);
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Recaudos</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalRegistros }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Valor Total Recaudado</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">${{ number_format($totalValor, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">Ingresos — {{ $totalRegistros }} registro(s)</h2>
            </div>

            @if($totalRegistros > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs" id="table-income">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Código Presupuestal</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Fecha De Recaudo</th>
                            <th class="px-3 py-3 text-center font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Número De Recibo</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Recibido De</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Concepto Recaudo</th>
                            <th class="px-3 py-3 text-right font-semibold text-emerald-700 uppercase tracking-wider whitespace-nowrap">Valor</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Cuenta Bancaria</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($rows as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-800 font-medium">{{ $row['codigo_presupuestal'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-700">{{ $row['fecha_recaudo'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-center font-mono text-gray-500">{{ $row['numero_recibo'] }}</td>
                            <td class="px-3 py-2 text-gray-800 font-medium">{{ $row['recibido_de'] }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $row['concepto_recaudo'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-mono text-emerald-700 font-semibold">${{ number_format($row['valor'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-700">{{ $row['cuenta_bancaria'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr>
                            <td colspan="5" class="px-3 py-3 font-bold text-gray-700 text-xs uppercase">Total</td>
                            <td class="px-3 py-3 text-right font-bold text-emerald-700 font-mono whitespace-nowrap">${{ number_format($totalValor, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                <svg class="w-16 h-16 mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-lg font-medium">Sin registros</p>
                <p class="text-sm mt-1">No se encontraron ingresos para la vigencia {{ $filterYear }}.</p>
            </div>
            @endif
        </div>

    </div>
</div>

@push('scripts')
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('btn-export-income');
    if (!btn) return;

    btn.addEventListener('click', function () {
        var schoolName = @json($school->name ?? 'Colegio');
        var year       = @json($filterYear);
        var rows       = @json($rows);

        var headers = [
            'Codigo Presupuestal',
            'Fecha De Recaudo',
            'Numero De Recibo',
            'Recibido De',
            'Concepto Recaudo',
            'Valor',
            'Cuenta Bancaria',
        ];

        var dataRows = rows.map(function (r) {
            return [
                r.codigo_presupuestal,
                r.fecha_recaudo,
                r.numero_recibo,
                r.recibido_de,
                r.concepto_recaudo,
                r.valor,
                r.cuenta_bancaria,
            ];
        });

        // Totals row
        var totalValor = rows.reduce(function (s, r) { return s + r.valor; }, 0);
        dataRows.push(['', '', '', '', 'TOTAL', totalValor, '']);

        var ws = XLSX.utils.aoa_to_sheet([headers].concat(dataRows));

        ws['!cols'] = [
            { wch: 20 }, // Código Presupuestal
            { wch: 16 }, // Fecha De Recaudo
            { wch: 18 }, // Número De Recibo
            { wch: 35 }, // Recibido De
            { wch: 40 }, // Concepto Recaudo
            { wch: 20 }, // Valor
            { wch: 20 }, // Cuenta Bancaria
        ];

        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Ingresos');
        XLSX.writeFile(wb, 'Contraloria_Ingresos_' + schoolName + '_' + year + '.xlsx');
    });
});
</script>
@endpush

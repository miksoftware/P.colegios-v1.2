<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Pago Impuestos - Contraloría</h1>
                <p class="text-gray-500 mt-1">Pagos directos sin CDP ni RP (impuestos, DIAN y similares)</p>
            </div>
            @can('reports.export')
            <button id="btn-export-tax-payments"
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
            $totalValor      = collect($rows)->sum('valor');
            $totalDescuentos = collect($rows)->sum('descuentos');
            $totalNeto       = collect($rows)->sum('neto');
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Registros</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ count($rows) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Valor Comprobantes</p>
                <p class="text-2xl font-bold text-blue-700 mt-1">${{ number_format($totalValor, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Descuentos</p>
                <p class="text-2xl font-bold text-red-600 mt-1">${{ number_format($totalDescuentos, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Neto Pagado</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">${{ number_format($totalNeto, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">Pagos — {{ count($rows) }} registro(s)</h2>
            </div>

            @if(count($rows) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs" id="table-tax-payments">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Fecha De Pago</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">No. Comprobante</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Beneficiario</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Cédula / NIT</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Detalle De Pago</th>
                            <th class="px-3 py-3 text-right font-semibold text-blue-700 uppercase tracking-wider whitespace-nowrap">Valor Comprobante</th>
                            <th class="px-3 py-3 text-right font-semibold text-red-700 uppercase tracking-wider whitespace-nowrap">Descuentos</th>
                            <th class="px-3 py-3 text-right font-semibold text-emerald-700 uppercase tracking-wider whitespace-nowrap">Neto Pagado</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">Banco</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">No. De Cuenta</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider whitespace-nowrap">No. Cheque / ND</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($rows as $row)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-700">{{ $row['fecha'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-center font-mono text-gray-700">{{ $row['no_comprobante'] }}</td>
                            <td class="px-3 py-2 text-gray-800 font-medium">{{ $row['beneficiario'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-700">{{ $row['nit'] }}</td>
                            <td class="px-3 py-2 text-gray-600 max-w-xs truncate" title="{{ $row['detalle'] }}">{{ $row['detalle'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-mono text-blue-700">${{ number_format($row['valor'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-mono text-red-600">${{ number_format($row['descuentos'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-mono text-emerald-700 font-semibold">${{ number_format($row['neto'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-700">{{ $row['banco'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-gray-700">{{ $row['no_cuenta'] }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-500">{{ $row['no_cheque'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                        <tr>
                            <td colspan="5" class="px-3 py-3 font-bold text-gray-700 text-xs uppercase">Totales</td>
                            <td class="px-3 py-3 text-right font-bold text-blue-700 font-mono whitespace-nowrap">${{ number_format($totalValor, 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right font-bold text-red-600 font-mono whitespace-nowrap">${{ number_format($totalDescuentos, 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right font-bold text-emerald-700 font-mono whitespace-nowrap">${{ number_format($totalNeto, 0, ',', '.') }}</td>
                            <td colspan="3"></td>
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
                <p class="text-sm mt-1">No se encontraron pagos de impuestos para la vigencia {{ $filterYear }}.</p>
            </div>
            @endif
        </div>

    </div>
</div>

@push('scripts')
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('btn-export-tax-payments');
    if (!btn) return;

    btn.addEventListener('click', function () {
        var schoolName = @json($school->name ?? 'Colegio');
        var year       = @json($filterYear);
        var rows       = @json($rows);

        var headers = [
            'Fecha De Pago',
            'No. De Comprobante',
            'Beneficiario',
            'Cédula O NIT',
            'Detalle De Pago',
            'Valor Comprobante De Pago',
            'Descuentos',
            'Neto Pagado',
            'Banco',
            'No. De Cuenta',
            'No. De Cheque O ND',
        ];

        var dataRows = rows.map(function (r) {
            return [
                r.fecha,
                r.no_comprobante,
                r.beneficiario,
                r.nit,
                r.detalle,
                r.valor,
                r.descuentos,
                r.neto,
                r.banco,
                r.no_cuenta,
                r.no_cheque,
            ];
        });

        // Totals row
        var totalValor      = rows.reduce(function (s, r) { return s + r.valor; }, 0);
        var totalDescuentos = rows.reduce(function (s, r) { return s + r.descuentos; }, 0);
        var totalNeto       = rows.reduce(function (s, r) { return s + r.neto; }, 0);
        dataRows.push(['', '', '', '', 'TOTALES', totalValor, totalDescuentos, totalNeto, '', '', '']);

        var ws = XLSX.utils.aoa_to_sheet([headers].concat(dataRows));

        // Column widths
        ws['!cols'] = [
            { wch: 14 }, // Fecha
            { wch: 14 }, // No. Comprobante
            { wch: 30 }, // Beneficiario
            { wch: 16 }, // NIT
            { wch: 40 }, // Detalle
            { wch: 22 }, // Valor
            { wch: 16 }, // Descuentos
            { wch: 18 }, // Neto
            { wch: 20 }, // Banco
            { wch: 20 }, // No. Cuenta
            { wch: 16 }, // No. Cheque
        ];

        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Pago Impuestos');
        XLSX.writeFile(wb, 'Contraloria_PagoImpuestos_' + schoolName + '_' + year + '.xlsx');
    });
});
</script>
@endpush

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Liquidaci&oacute;n de Retenciones</h1>
                <p class="text-gray-500 mt-1">Formato para liquidar retenciones por fuente de financiaci&oacute;n</p>
            </div>
            @can('reports.export')
            <button onclick="window.exportRetentionExcel()" class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar Excel
            </button>
            @endcan
        </div>

        {{-- Info del Colegio --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div><span class="text-gray-500">INFORME:</span> <span class="font-semibold text-gray-900">LIQUIDACI&Oacute;N DE RETENCIONES</span></div>
                <div><span class="text-gray-500">C&Oacute;DIGO DANE:</span> <span class="font-semibold text-gray-900">{{ $school->dane_code ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">FONDO:</span> <span class="font-semibold text-gray-900">{{ $school->name ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">MUNICIPIO:</span> <span class="font-semibold text-gray-900">{{ $school->municipality ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">RECTOR:</span> <span class="font-semibold text-gray-900">{{ $school->rector_name ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">PAGADOR:</span> <span class="font-semibold text-gray-900">{{ $school->pagador_name ?? 'N/A' }}</span></div>
                <div class="lg:col-span-3"><span class="text-gray-500">PERIODO:</span> <span class="font-semibold text-gray-900">{{ $this->periodLabel }}</span></div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia</label>
                    <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                    <select wire:model.live="filterMonth" class="w-full rounded-xl border-gray-300">
                        <option value="">Todos (Consolidado)</option>
                        @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $m)
                            <option value="{{ $i + 1 }}">{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Tarjeta Resumen General --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Fuentes con Retenciones</p>
                <p class="text-2xl font-bold text-gray-900">{{ count($reportData) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total General Retenciones</p>
                <p class="text-2xl font-bold text-red-600">${{ number_format($grandTotals['total_retentions'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Vigencia</p>
                <p class="text-2xl font-bold text-blue-600">{{ $filterYear }}</p>
            </div>
        </div>

        {{-- Tablas por Fuente de Financiacion --}}
        @forelse($reportData as $fsIndex => $fsData)
        @php
            $bgColors = ['bg-yellow-50 border-yellow-200', 'bg-green-50 border-green-200', 'bg-blue-50 border-blue-200', 'bg-purple-50 border-purple-200', 'bg-orange-50 border-orange-200', 'bg-pink-50 border-pink-200'];
            $headerColors = ['bg-yellow-100', 'bg-green-100', 'bg-blue-100', 'bg-purple-100', 'bg-orange-100', 'bg-pink-100'];
            $totalRowColors = ['bg-yellow-200 text-yellow-900', 'bg-green-200 text-green-900', 'bg-blue-200 text-blue-900', 'bg-purple-200 text-purple-900', 'bg-orange-200 text-orange-900', 'bg-pink-200 text-pink-900'];
            $colorIndex = $fsIndex % count($bgColors);
        @endphp
        <div class="mb-8 {{ $bgColors[$colorIndex] }} rounded-2xl border overflow-hidden shadow-sm" wire:key="fs-{{ $fsIndex }}">
            {{-- Titulo de la tabla --}}
            <div class="px-6 py-4 {{ $headerColors[$colorIndex] }} border-b flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900">FORMATO PARA LIQUIDAR RETENCIONES</h3>
                <span class="px-4 py-1.5 text-sm font-bold rounded-full bg-white/80 text-gray-800 shadow-sm">
                    {{ strtoupper($fsData['funding_source']) }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="{{ $headerColors[$colorIndex] }}">
                            <th rowspan="2" class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase border-r border-gray-300 w-40">
                                Concepto de Retenci&oacute;n
                            </th>
                            <th colspan="2" class="px-4 py-2 text-center text-xs font-bold text-gray-700 uppercase border-r border-gray-300">
                                Persona Jur&iacute;dica
                            </th>
                            <th colspan="2" class="px-4 py-2 text-center text-xs font-bold text-gray-700 uppercase border-r border-gray-300">
                                Persona Natural
                            </th>
                            <th rowspan="2" class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase w-36">
                                Total Retenci&oacute;n
                            </th>
                        </tr>
                        <tr class="{{ $headerColors[$colorIndex] }}">
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-600 border-r border-gray-300">Valor Base (Subtotal)</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-600 border-r border-gray-300">Valor Retenci&oacute;n</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-600 border-r border-gray-300">Valor Base (Subtotal)</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-600 border-r border-gray-300">Valor Retenci&oacute;n</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white/60">
                        @php
                            $conceptLabels = [
                                'servicios' => 'SERVICIOS',
                                'compras' => 'COMPRAS',
                                'honorarios' => 'HONORARIOS',
                                'reteiva' => 'RETEIVA',
                            ];
                        @endphp
                        @foreach(['servicios', 'compras', 'honorarios', 'reteiva'] as $concept)
                        @php $row = $fsData['rows'][$concept]; @endphp
                        <tr class="border-t border-gray-200 hover:bg-white/80 transition-colors">
                            <td class="px-4 py-3 font-semibold text-gray-800 border-r border-gray-200">{{ $conceptLabels[$concept] }}</td>
                            @if($concept === 'reteiva')
                                {{-- ReteIVA no tiene base, solo valor de retencion --}}
                                <td class="px-4 py-3 text-right font-mono text-gray-400 border-r border-gray-200"></td>
                                <td class="px-4 py-3 text-right font-mono border-r border-gray-200 {{ $row['juridica_retention'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                    @if($row['juridica_retention'] > 0)
                                        ${{ number_format($row['juridica_retention'], 2, ',', '.') }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-gray-400 border-r border-gray-200"></td>
                                <td class="px-4 py-3 text-right font-mono border-r border-gray-200 {{ $row['natural_retention'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                    @if($row['natural_retention'] > 0)
                                        ${{ number_format($row['natural_retention'], 2, ',', '.') }}
                                    @endif
                                </td>
                            @else
                                <td class="px-4 py-3 text-right font-mono border-r border-gray-200 {{ $row['juridica_base'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                    @if($row['juridica_base'] > 0)
                                        ${{ number_format($row['juridica_base'], 2, ',', '.') }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono border-r border-gray-200 {{ $row['juridica_retention'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                    @if($row['juridica_retention'] > 0)
                                        ${{ number_format($row['juridica_retention'], 2, ',', '.') }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono border-r border-gray-200 {{ $row['natural_base'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                    @if($row['natural_base'] > 0)
                                        ${{ number_format($row['natural_base'], 2, ',', '.') }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono border-r border-gray-200 {{ $row['natural_retention'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                    @if($row['natural_retention'] > 0)
                                        ${{ number_format($row['natural_retention'], 2, ',', '.') }}
                                    @endif
                                </td>
                            @endif
                            <td class="px-4 py-3 text-right font-mono font-semibold {{ $row['total_retention'] > 0 ? 'text-red-700' : 'text-gray-400' }}">
                                {{ number_format($row['total_retention'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="{{ $totalRowColors[$colorIndex] }} font-bold text-sm">
                            <td colspan="5" class="px-4 py-3 text-center uppercase tracking-wide">
                                Total Retenciones por {{ $fsData['funding_source'] }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-lg">
                                {{ number_format($fsData['total'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-500 text-lg">No se encontraron retenciones para el periodo seleccionado</p>
            <p class="text-gray-400 text-sm mt-1">Intente cambiar los filtros de vigencia o mes</p>
        </div>
        @endforelse

        {{-- Resumen General --}}
        @if(count($reportData) > 1)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-bold text-gray-700 uppercase">Resumen General de Retenciones</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fuente de Financiaci&oacute;n</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Retenciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($reportData as $fsData)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 font-medium text-gray-900">{{ $fsData['funding_source'] }}</td>
                        <td class="px-6 py-3 text-right font-mono font-semibold text-red-700">${{ number_format($fsData['total'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-100 font-bold">
                    <tr>
                        <td class="px-6 py-3 text-right uppercase text-xs text-gray-700">Total General:</td>
                        <td class="px-6 py-3 text-right font-mono text-lg text-red-800">${{ number_format($grandTotals['total_retentions'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

    {{-- Hidden data for JS --}}
    <div id="retention-report-data" class="hidden"
         data-school="{{ json_encode($school) }}"
         data-report="{{ json_encode($reportData) }}"
         data-totals="{{ json_encode($grandTotals) }}"
         data-period="{{ $this->periodLabel }}"
         data-year="{{ $filterYear }}">
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    function getData() {
        var el = document.getElementById('retention-report-data');
        if (!el) return null;
        return {
            school: JSON.parse(el.dataset.school || '{}'),
            report: JSON.parse(el.dataset.report || '[]'),
            totals: JSON.parse(el.dataset.totals || '{}'),
            period: el.dataset.period || '',
            year: el.dataset.year || ''
        };
    }

    window.exportRetentionExcel = function() {
        if (typeof XLSX === 'undefined') { alert('Cargando libreria...'); return; }
        var d = getData();
        if (!d || !d.report.length) { alert('No hay datos para exportar.'); return; }

        var wb = XLSX.utils.book_new();
        var allRows = [];
        var merges = [];
        var currentRow = 0;

        // Header del colegio
        allRows.push(['FORMATO PARA LIQUIDAR RETENCIONES']);
        merges.push({s:{r:currentRow,c:0},e:{r:currentRow,c:5}});
        currentRow++;
        allRows.push(['COLEGIO:', d.school.name || 'N/A']);
        merges.push({s:{r:currentRow,c:1},e:{r:currentRow,c:5}});
        currentRow++;
        allRows.push(['CODIGO DANE:', d.school.dane_code || 'N/A']);
        currentRow++;
        allRows.push(['MUNICIPIO:', d.school.municipality || 'N/A']);
        currentRow++;
        allRows.push(['PERIODO:', d.period]);
        currentRow++;
        allRows.push([]);
        currentRow++;

        var conceptLabels = {
            'servicios': 'SERVICIOS',
            'compras': 'COMPRAS',
            'honorarios': 'HONORARIOS',
            'reteiva': 'RETEIVA'
        };
        var conceptOrder = ['servicios', 'compras', 'honorarios', 'reteiva'];

        d.report.forEach(function(fs) {
            // Titulo de la fuente
            allRows.push(['FORMATO PARA LIQUIDAR RETENCIONES', '', '', '', 'FUENTE DE FINANCIACION']);
            merges.push({s:{r:currentRow,c:0},e:{r:currentRow,c:3}});
            currentRow++;

            // Headers de la tabla
            allRows.push(['CONCEPTO DE RETENCION', 'PERSONA JURIDICA', '', 'PERSONA NATURAL', '', 'TOTAL RETENCION']);
            merges.push({s:{r:currentRow,c:1},e:{r:currentRow,c:2}});
            merges.push({s:{r:currentRow,c:3},e:{r:currentRow,c:4}});
            currentRow++;

            allRows.push(['', 'VALOR BASE (SUBTOTAL)', 'VALOR RETENCION', 'VALOR BASE (SUBTOTAL)', 'VALOR RETENCION', fs.funding_source.toUpperCase()]);
            currentRow++;

            // Filas de datos
            conceptOrder.forEach(function(key) {
                var row = fs.rows[key];
                if (key === 'reteiva') {
                    allRows.push([
                        conceptLabels[key],
                        '',
                        row.juridica_retention || '',
                        '',
                        row.natural_retention || '',
                        row.total_retention
                    ]);
                } else {
                    allRows.push([
                        conceptLabels[key],
                        row.juridica_base || '',
                        row.juridica_retention || '',
                        row.natural_base || '',
                        row.natural_retention || '',
                        row.total_retention
                    ]);
                }
                currentRow++;
            });

            // Total por fuente
            allRows.push(['TOTAL RETENCIONES POR ' + fs.funding_source.toUpperCase(), '', '', '', '', fs.total]);
            merges.push({s:{r:currentRow,c:0},e:{r:currentRow,c:4}});
            currentRow++;

            // Espacio entre tablas
            allRows.push([]);
            currentRow++;
        });

        var ws = XLSX.utils.aoa_to_sheet(allRows);
        ws['!merges'] = merges;
        ws['!cols'] = [
            {wch: 35},
            {wch: 22},
            {wch: 20},
            {wch: 22},
            {wch: 20},
            {wch: 18}
        ];

        // Format currency cells
        for (var r = 0; r < allRows.length; r++) {
            for (var c = 1; c <= 5; c++) {
                var cell = XLSX.utils.encode_cell({r: r, c: c});
                if (ws[cell] && typeof ws[cell].v === 'number' && ws[cell].v !== 0) {
                    ws[cell].z = '#,##0';
                }
            }
        }

        XLSX.utils.book_append_sheet(wb, ws, 'Liquidacion Retenciones');
        var fileName = 'Liquidacion_Retenciones_' + (d.school.name || 'Colegio').replace(/[^a-zA-Z0-9]/g, '_') + '_' + d.year + '.xlsx';
        XLSX.writeFile(wb, fileName);
    };
});
</script>
@endpush

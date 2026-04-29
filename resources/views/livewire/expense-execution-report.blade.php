<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Ejecución de Gastos</h1>
                <p class="text-gray-500 mt-1">Informe de ejecución presupuestal de gastos</p>
            </div>
            @can('reports.export')
            <button id="btn-export-expense" class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar Excel
            </button>
            @endcan
        </div>

        {{-- Info del Colegio --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div><span class="text-gray-500">INFORME:</span> <span class="font-semibold text-gray-900">EJECUCIÓN DE GASTOS</span></div>
                <div><span class="text-gray-500">CÓDIGO DANE:</span> <span class="font-semibold text-gray-900">{{ $school->dane_code ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">FONDO:</span> <span class="font-semibold text-gray-900">{{ $school->name ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">MUNICIPIO:</span> <span class="font-semibold text-gray-900">{{ $school->municipality ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">RECTOR:</span> <span class="font-semibold text-gray-900">{{ $school->rector_name ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">EMAIL:</span> <span class="font-semibold text-gray-900">{{ $school->email ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">PAGADOR:</span> <span class="font-semibold text-gray-900">{{ $school->pagador_name ?? 'N/A' }}</span></div>
                <div class="lg:col-span-2"><span class="text-gray-500">PERIODO:</span> <span class="font-semibold text-gray-900">{{ $this->periodLabel }}</span></div>
            </div>
        </div>

        {{-- Filtro --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia</label>
                    <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Período (acumulado)</label>
                    <select wire:model.live="filterQuarter" class="w-full rounded-xl border-gray-300">
                        <option value="">Anual (Consolidado)</option>
                        <optgroup label="── Trimestral ──">
                            <option value="1">Al 1er Trimestre (Ene – Mar)</option>
                            <option value="2">Al 2do Trimestre (Ene – Jun)</option>
                            <option value="3">Al 3er Trimestre (Ene – Sep)</option>
                            <option value="4">Al 4to Trimestre (Ene – Dic)</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Semestral (acumulado)</label>
                    <select wire:model.live="filterSemester" class="w-full rounded-xl border-gray-300">
                        <option value="">—</option>
                        <option value="1">Al 1er Semestre (Ene – Jun)</option>
                        <option value="2">Al 2do Semestre (Ene – Dic)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Tarjetas --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Apropiación Definitiva</p>
                <p class="text-xl font-bold text-gray-900">${{ number_format($totals['definitive'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Compromisos (RP)</p>
                <p class="text-xl font-bold text-blue-600">${{ number_format($totals['commitments'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Obligaciones</p>
                <p class="text-xl font-bold text-indigo-600">${{ number_format($totals['obligations'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Pagos</p>
                <p class="text-xl font-bold text-emerald-600">${{ number_format($totals['payments'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Por Ejecutar</p>
                <p class="text-xl font-bold text-amber-600">${{ number_format($totals['pending'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Gráfica --}}
        @if(count($rows) > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6" wire:ignore>
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Ejecución Presupuestal</h3>
            <div style="height: 300px;"><canvas id="chartExecution"></canvas></div>
        </div>
        @endif

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Rubro</th>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase" rowspan="2">Nombre del Rubro</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Fuente</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Aprob. Inicial</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase" colspan="4">Modificaciones</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Aprob. Definitiva</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Compromisos</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Obligaciones</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Pagos</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Por Ejecutar</th>
                        </tr>
                        <tr>
                            <th class="px-2 py-2 text-right font-medium text-gray-400 uppercase text-[10px]">Adición</th>
                            <th class="px-2 py-2 text-right font-medium text-gray-400 uppercase text-[10px]">Reducción</th>
                            <th class="px-2 py-2 text-right font-medium text-gray-400 uppercase text-[10px]">Crédito</th>
                            <th class="px-2 py-2 text-right font-medium text-gray-400 uppercase text-[10px]">Contracréd.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $r)
                        <tr class="hover:bg-blue-50/50 transition-colors" wire:key="row-{{ $r['budget_id'] }}">
                            <td class="px-3 py-2.5 whitespace-nowrap font-mono text-xs text-blue-700">{{ $r['rubro_code'] }}</td>
                            <td class="px-3 py-2.5 text-gray-900 max-w-[250px] truncate" title="{{ $r['rubro_name'] }}">{{ $r['rubro_name'] }}</td>
                            <td class="px-3 py-2.5 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-blue-100 text-blue-700">{{ $r['funding_source_code'] }} - {{ $r['funding_source_name'] }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-gray-700">${{ number_format($r['initial'], 0, ',', '.') }}</td>
                            <td class="px-2 py-2.5 text-right whitespace-nowrap font-mono text-green-600">{{ $r['additions'] > 0 ? '$'.number_format($r['additions'], 0, ',', '.') : '-' }}</td>
                            <td class="px-2 py-2.5 text-right whitespace-nowrap font-mono text-red-600">{{ $r['reductions'] > 0 ? '$'.number_format($r['reductions'], 0, ',', '.') : '-' }}</td>
                            <td class="px-2 py-2.5 text-right whitespace-nowrap font-mono text-green-600">{{ $r['credits'] > 0 ? '$'.number_format($r['credits'], 0, ',', '.') : '-' }}</td>
                            <td class="px-2 py-2.5 text-right whitespace-nowrap font-mono text-red-600">{{ $r['contracredits'] > 0 ? '$'.number_format($r['contracredits'], 0, ',', '.') : '-' }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono font-medium text-gray-900">${{ number_format($r['definitive'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-blue-700">${{ number_format($r['commitments'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-indigo-700">${{ number_format($r['obligations'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-emerald-700">${{ number_format($r['payments'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono font-medium {{ $r['pending'] > 0 ? 'text-amber-600' : 'text-gray-500' }}">${{ number_format($r['pending'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="13" class="px-6 py-12 text-center text-gray-500">
                                <p class="mt-2">No se encontraron presupuestos de gasto para esta vigencia</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($rows) > 0)
                    <tfoot class="bg-gray-50 font-semibold text-xs">
                        <tr>
                            <td colspan="3" class="px-3 py-3 text-right text-gray-700 uppercase">Totales:</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-gray-900">${{ number_format($totals['initial'], 0, ',', '.') }}</td>
                            <td class="px-2 py-3 text-right whitespace-nowrap font-mono text-green-700">${{ number_format($totals['additions'], 0, ',', '.') }}</td>
                            <td class="px-2 py-3 text-right whitespace-nowrap font-mono text-red-700">${{ number_format($totals['reductions'], 0, ',', '.') }}</td>
                            <td class="px-2 py-3 text-right whitespace-nowrap font-mono text-green-700">${{ number_format($totals['credits'], 0, ',', '.') }}</td>
                            <td class="px-2 py-3 text-right whitespace-nowrap font-mono text-red-700">${{ number_format($totals['contracredits'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-gray-900">${{ number_format($totals['definitive'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-blue-700">${{ number_format($totals['commitments'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-indigo-700">${{ number_format($totals['obligations'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-emerald-700">${{ number_format($totals['payments'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-amber-700">${{ number_format($totals['pending'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Hidden data for JS --}}
    <div id="report-data" class="hidden"
         data-school="{{ json_encode($school) }}"
         data-rows="{{ json_encode($rows) }}"
         data-totals="{{ json_encode($totals) }}"
         data-period="{{ $this->periodLabel }}">
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var chartExec = null;

    function getReportData() {
        var el = document.getElementById('report-data');
        if (!el) return null;
        return {
            school: JSON.parse(el.dataset.school || '{}'),
            rows: JSON.parse(el.dataset.rows || '[]'),
            totals: JSON.parse(el.dataset.totals || '{}'),
            period: el.dataset.period || ''
        };
    }

    function renderChart() {
        var canvas = document.getElementById('chartExecution');
        if (!canvas) return;
        if (chartExec) chartExec.destroy();

        var d = getReportData();
        if (!d) return;
        var t = d.totals;

        chartExec = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: ['Aprob. Inicial', 'Aprob. Definitiva', 'Compromisos', 'Obligaciones', 'Pagos', 'Por Ejecutar'],
                datasets: [{
                    label: 'Monto',
                    data: [t.initial||0, t.definitive||0, t.commitments||0, t.obligations||0, t.payments||0, t.pending||0],
                    backgroundColor: ['rgba(107,114,128,0.7)','rgba(17,24,39,0.75)','rgba(59,130,246,0.7)','rgba(99,102,241,0.7)','rgba(16,185,129,0.7)','rgba(245,158,11,0.7)'],
                    borderRadius: 8,
                    barPercentage: 0.5
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(ctx) { return ' $' + ctx.parsed.y.toLocaleString('es-CO'); } } } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function(v) { return '$' + (v >= 1000000 ? (v/1000000).toFixed(1)+'M' : (v/1000).toFixed(0)+'K'); } }, grid: { color: 'rgba(0,0,0,0.04)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    renderChart();

    document.addEventListener('livewire:navigated', function() { setTimeout(renderChart, 200); });
    if (typeof Livewire !== 'undefined') {
        Livewire.hook('morph.updated', function() { setTimeout(renderChart, 200); });
    }

    // Export
    var btnExport = document.getElementById('btn-export-expense');
    if (btnExport) {
        btnExport.addEventListener('click', function() {
            if (typeof XLSX === 'undefined') { alert('Cargando librería...'); return; }
            var d = getReportData();
            if (!d) return;

            var wb = XLSX.utils.book_new();
            var hdr = [
                ['INFORME:', 'EJECUCIÓN DE GASTOS'],
                ['CÓDIGO DANE:', d.school.dane_code || 'N/A'],
                ['FONDO:', d.school.name || 'N/A'],
                ['MUNICIPIO:', d.school.municipality || 'N/A'],
                ['RECTOR:', d.school.rector_name || 'N/A'],
                ['EMAIL:', d.school.email || 'N/A'],
                ['PAGADOR:', d.school.pagador_name || 'N/A'],
                ['PERIODO:', d.period],
                [],
                ['RUBRO','NOMBRE DEL RUBRO','FUENTE DE FINANCIACIÓN','APROPIACIÓN INICIAL','MODIFICACIONES','','','','APROPIACIÓN DEFINITIVA','COMPROMISOS (RP EXPEDIDOS)','OBLIGACIONES ACUMULADAS','PAGOS','POR EJECUTAR'],
                ['','','','','ADICIÓN','REDUCCIÓN','CRÉDITO','CONTRACRÉDITO','','','','','']
            ];

            var data = d.rows.map(function(r) {
                return [r.rubro_code, r.rubro_name, r.funding_source_code+' - '+r.funding_source_name,
                    r.initial, r.additions, r.reductions, r.credits, r.contracredits,
                    r.definitive, r.commitments, r.obligations, r.payments, r.pending];
            });

            var tot = ['','','TOTALES:', d.totals.initial, d.totals.additions, d.totals.reductions, d.totals.credits, d.totals.contracredits,
                d.totals.definitive, d.totals.commitments, d.totals.obligations, d.totals.payments, d.totals.pending];

            var all = hdr.concat(data, [[]], [tot]);
            var ws = XLSX.utils.aoa_to_sheet(all);

            ws['!cols'] = [{wch:28},{wch:50},{wch:35},{wch:20},{wch:18},{wch:18},{wch:18},{wch:18},{wch:22},{wch:22},{wch:22},{wch:20},{wch:20}];
            ws['!merges'] = [
                {s:{r:0,c:1},e:{r:0,c:4}}, {s:{r:9,c:4},e:{r:9,c:7}},
                {s:{r:9,c:0},e:{r:10,c:0}}, {s:{r:9,c:1},e:{r:10,c:1}}, {s:{r:9,c:2},e:{r:10,c:2}}, {s:{r:9,c:3},e:{r:10,c:3}},
                {s:{r:9,c:8},e:{r:10,c:8}}, {s:{r:9,c:9},e:{r:10,c:9}}, {s:{r:9,c:10},e:{r:10,c:10}}, {s:{r:9,c:11},e:{r:10,c:11}}, {s:{r:9,c:12},e:{r:10,c:12}}
            ];

            var cc = [3,4,5,6,7,8,9,10,11,12];
            for (var r = hdr.length; r < hdr.length + data.length; r++) {
                cc.forEach(function(c) { var cell = XLSX.utils.encode_cell({r:r,c:c}); if (ws[cell] && typeof ws[cell].v === 'number') ws[cell].z = '#,##0.00'; });
            }
            var ti = all.length - 1;
            cc.forEach(function(c) { var cell = XLSX.utils.encode_cell({r:ti,c:c}); if (ws[cell] && typeof ws[cell].v === 'number') ws[cell].z = '#,##0.00'; });

            XLSX.utils.book_append_sheet(wb, ws, 'Ejecución de Gastos');
            XLSX.writeFile(wb, 'Ejecucion_Gastos_' + (d.school.name||'Colegio').replace(/[^a-zA-Z0-9]/g,'_') + '.xlsx');
        });
    }
});
</script>
@endpush

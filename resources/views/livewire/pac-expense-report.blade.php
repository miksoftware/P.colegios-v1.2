<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">PAC de Gastos</h1>
                <p class="text-gray-500 mt-1">Plan Anual de Caja - Ejecuci&oacute;n mensual de gastos</p>
            </div>
            @can('reports.export')
            <button id="btn-export-pac" class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar Excel
            </button>
            @endcan
        </div>

        {{-- Info del Colegio --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div><span class="text-gray-500">INFORME:</span> <span class="font-semibold text-gray-900">PAC DE GASTOS</span></div>
                <div><span class="text-gray-500">C&Oacute;DIGO DANE:</span> <span class="font-semibold text-gray-900">{{ $school->dane_code ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">FONDO:</span> <span class="font-semibold text-gray-900">{{ $school->name ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">MUNICIPIO:</span> <span class="font-semibold text-gray-900">{{ $school->municipality ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">RECTOR:</span> <span class="font-semibold text-gray-900">{{ $school->rector_name ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">FECHA APROB. PRESUPUESTO:</span> <span class="font-semibold text-gray-900">{{ $this->approvalDate }}</span></div>
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
            </div>
        </div>

        {{-- Tarjetas --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Presupuesto Inicial</p>
                <p class="text-xl font-bold text-gray-900">${{ number_format($totals['initial'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Presupuesto Definitivo</p>
                <p class="text-xl font-bold text-blue-600">${{ number_format($totals['definitive'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Resumen Ejecutado</p>
                <p class="text-xl font-bold text-emerald-600">${{ number_format($totals['executed'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Por Ejecutar</p>
                <p class="text-xl font-bold text-amber-600">${{ number_format($totals['pending'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Gr&aacute;fica --}}
        @if(count($rows) > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6" wire:ignore>
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Ejecuci&oacute;n Mensual de Gastos</h3>
            <div style="height: 300px;"><canvas id="chartPacMonthly"></canvas></div>
        </div>
        @endif

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase whitespace-nowrap sticky left-0 bg-gray-50 z-10" rowspan="2">C&oacute;digo Presupuestal</th>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase" rowspan="2">Rubro</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Ppto. Inicial</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Adiciones</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Reducciones</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Cr&eacute;ditos</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Contracr&eacute;ditos</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Ppto. Definitivo</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase" colspan="12">RP Expedidos por Mes</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Resumen Ejecutado</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap" rowspan="2">Por Ejecutar</th>
                        </tr>
                        <tr>
                            @foreach(['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'] as $mLabel)
                                <th class="px-2 py-2 text-right font-medium text-gray-400 uppercase text-[10px] whitespace-nowrap">{{ $mLabel }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $idx => $r)
                        <tr class="hover:bg-blue-50/50 transition-colors" wire:key="pac-row-{{ $idx }}">
                            <td class="px-3 py-2.5 whitespace-nowrap font-mono text-xs text-blue-700 sticky left-0 bg-white z-10">{{ $r['code'] }}</td>
                            <td class="px-3 py-2.5 text-gray-900 max-w-[220px] truncate" title="{{ $r['name'] }}">{{ $r['name'] }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-gray-700">${{ number_format($r['initial'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-green-600">{{ $r['additions'] > 0 ? '$'.number_format($r['additions'], 0, ',', '.') : '-' }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-red-600">{{ $r['reductions'] > 0 ? '$'.number_format($r['reductions'], 0, ',', '.') : '-' }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-green-600">{{ $r['credits'] > 0 ? '$'.number_format($r['credits'], 0, ',', '.') : '-' }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-red-600">{{ $r['contracredits'] > 0 ? '$'.number_format($r['contracredits'], 0, ',', '.') : '-' }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono font-medium text-gray-900">${{ number_format($r['definitive'], 0, ',', '.') }}</td>
                            @for($m = 1; $m <= 12; $m++)
                                <td class="px-2 py-2.5 text-right whitespace-nowrap font-mono text-indigo-700 text-[11px]">{{ $r['months'][$m] > 0 ? '$'.number_format($r['months'][$m], 0, ',', '.') : '$0' }}</td>
                            @endfor
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono font-medium text-emerald-700">${{ number_format($r['executed'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono font-medium {{ $r['pending'] > 0 ? 'text-amber-600' : 'text-gray-500' }}">${{ number_format($r['pending'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="22" class="px-6 py-12 text-center text-gray-500">
                                <p>No se encontraron presupuestos de gasto para esta vigencia</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($rows) > 0)
                    <tfoot class="bg-gray-50 font-semibold text-xs">
                        <tr>
                            <td colspan="2" class="px-3 py-3 text-right text-gray-700 uppercase sticky left-0 bg-gray-50 z-10">Total Presupuesto Gastos:</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-gray-900">${{ number_format($totals['initial'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-green-700">${{ number_format($totals['additions'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-red-700">${{ number_format($totals['reductions'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-green-700">${{ number_format($totals['credits'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-red-700">${{ number_format($totals['contracredits'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-gray-900">${{ number_format($totals['definitive'], 0, ',', '.') }}</td>
                            @for($m = 1; $m <= 12; $m++)
                                <td class="px-2 py-3 text-right whitespace-nowrap font-mono text-indigo-700 text-[11px]">${{ number_format($totals['months'][$m] ?? 0, 0, ',', '.') }}</td>
                            @endfor
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-emerald-700">${{ number_format($totals['executed'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-amber-700">${{ number_format($totals['pending'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Hidden data for JS --}}
    <div id="pac-report-data" class="hidden"
         data-school="{{ json_encode($school) }}"
         data-rows="{{ json_encode($rows) }}"
         data-totals="{{ json_encode($totals) }}"
         data-period="{{ $this->periodLabel }}"
         data-approval-date="{{ $this->approvalDate }}">
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var chartPac = null;
    var mNames = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    function getData() {
        var el = document.getElementById('pac-report-data');
        if (!el) return null;
        return {
            school: JSON.parse(el.dataset.school || '{}'),
            rows: JSON.parse(el.dataset.rows || '[]'),
            totals: JSON.parse(el.dataset.totals || '{}'),
            period: el.dataset.period || '',
            approvalDate: el.dataset.approvalDate || ''
        };
    }

    function renderChart() {
        var canvas = document.getElementById('chartPacMonthly');
        if (!canvas) return;
        if (chartPac) chartPac.destroy();

        var d = getData();
        if (!d) return;
        var mt = d.totals.months || {};
        var vals = [];
        for (var i = 1; i <= 12; i++) vals.push(mt[i] || 0);

        chartPac = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: mNames,
                datasets: [{
                    label: 'RP Expedidos',
                    data: vals,
                    backgroundColor: 'rgba(99,102,241,0.75)',
                    borderRadius: 8,
                    barPercentage: 0.5
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function(ctx) { return ' $' + ctx.parsed.y.toLocaleString('es-CO'); } } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function(v) { return '$' + (v >= 1000000 ? (v/1000000).toFixed(1)+'M' : (v/1000).toFixed(0)+'K'); } }, grid: { color: 'rgba(0,0,0,0.04)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    renderChart();
    if (typeof Livewire !== 'undefined') {
        Livewire.hook('morph.updated', function() { setTimeout(renderChart, 200); });
    }

    // Export Excel
    var btnExport = document.getElementById('btn-export-pac');
    if (btnExport) {
        btnExport.addEventListener('click', function() {
            if (typeof XLSX === 'undefined') { alert('Cargando libreria...'); return; }
            var d = getData();
            if (!d) return;

            var wb = XLSX.utils.book_new();
            var hdr = [
                ['INFORME:', 'PAC DE GASTOS'],
                ['CODIGO DANE:', d.school.dane_code || 'N/A'],
                ['FONDO:', d.school.name || 'N/A'],
                ['MUNICIPIO:', d.school.municipality || 'N/A'],
                ['RECTOR:', d.school.rector_name || 'N/A'],
                ['PAGADOR:', d.school.pagador_name || 'N/A'],
                ['FECHA APROBACION PRESUPUESTO:', d.approvalDate],
                ['PERIODO:', d.period],
                [],
                ['CODIGO PRESUPUESTAL','RUBRO','PRESUPUESTO INICIAL','ADICIONES','REDUCCIONES','CREDITOS','CONTRACREDITOS','PRESUPUESTO DEFINITIVO',
                 'RP.EXP.ENERO','RP.EXP.FEBRERO','RP.EXP.MARZO','RP.EXP.ABRIL','RP.EXP.MAYO','RP.EXP.JUNIO',
                 'RP.EXP.JULIO','RP.EXP.AGOSTO','RP.EXP.SEPTIEMBRE','RP.EXP.OCTUBRE','RP.EXP.NOVIEMBRE','RP.EXP.DICIEMBRE',
                 'RESUMEN EJECUTADO','POR EJECUTAR']
            ];

            var data = d.rows.map(function(r) {
                var row = [r.code, r.name, r.initial, r.additions, r.reductions, r.credits, r.contracredits, r.definitive];
                for (var m = 1; m <= 12; m++) row.push(r.months[m] || 0);
                row.push(r.executed, r.pending);
                return row;
            });

            var mt = d.totals.months || {};
            var tot = ['','TOTAL PRESUPUESTO GASTOS', d.totals.initial, d.totals.additions, d.totals.reductions, d.totals.credits, d.totals.contracredits, d.totals.definitive];
            for (var m = 1; m <= 12; m++) tot.push(mt[m] || 0);
            tot.push(d.totals.executed, d.totals.pending);

            var all = hdr.concat(data, [[]], [tot]);
            var ws = XLSX.utils.aoa_to_sheet(all);

            ws['!cols'] = [
                {wch:30},{wch:55},{wch:22},{wch:18},{wch:18},{wch:18},{wch:18},{wch:22},
                {wch:16},{wch:16},{wch:16},{wch:16},{wch:16},{wch:16},
                {wch:16},{wch:16},{wch:16},{wch:16},{wch:16},{wch:16},
                {wch:22},{wch:22}
            ];

            ws['!merges'] = [{s:{r:0,c:1},e:{r:0,c:4}}];

            // Format currency columns
            var cc = [2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21];
            for (var r = hdr.length; r < hdr.length + data.length; r++) {
                cc.forEach(function(c) {
                    var cell = XLSX.utils.encode_cell({r:r,c:c});
                    if (ws[cell] && typeof ws[cell].v === 'number') ws[cell].z = '#,##0.00';
                });
            }
            var ti = all.length - 1;
            cc.forEach(function(c) {
                var cell = XLSX.utils.encode_cell({r:ti,c:c});
                if (ws[cell] && typeof ws[cell].v === 'number') ws[cell].z = '#,##0.00';
            });

            XLSX.utils.book_append_sheet(wb, ws, 'PAC Gastos');
            XLSX.writeFile(wb, 'PAC_Gastos_' + (d.school.name || 'Colegio').replace(/[^a-zA-Z0-9]/g, '_') + '.xlsx');
        });
    }
});
</script>
@endpush

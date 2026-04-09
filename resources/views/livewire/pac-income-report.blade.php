<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">PAC de Ingresos</h1>
                <p class="text-gray-500 mt-1">Plan Anual de Caja - Recaudo mensual de ingresos</p>
            </div>
            @can('reports.export')
            <button id="btn-export-pac-income" class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar Excel
            </button>
            @endcan
        </div>

        {{-- Info del Colegio --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div><span class="text-gray-500">INFORME:</span> <span class="font-semibold text-gray-900">PAC DE INGRESOS</span></div>
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Recaudado</p>
                <p class="text-xl font-bold text-emerald-600">${{ number_format($totals['total'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Conceptos de Ingreso</p>
                <p class="text-xl font-bold text-blue-600">{{ count($rows) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Meses con Recaudo</p>
                <p class="text-xl font-bold text-gray-900">
                    {{ collect($totals['months'] ?? [])->filter(fn($v) => $v > 0)->count() }} / 12
                </p>
            </div>
        </div>

        {{-- Grafica --}}
        @if(count($rows) > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6" wire:ignore>
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Recaudo Mensual de Ingresos</h3>
            <div style="height: 300px;"><canvas id="chartPacIncomeMonthly"></canvas></div>
        </div>
        @endif

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase whitespace-nowrap sticky left-0 bg-gray-50 z-10">Concepto de Ingreso</th>
                            @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $mLabel)
                                <th class="px-2 py-3 text-right font-medium text-gray-500 uppercase text-[10px] whitespace-nowrap">{{ $mLabel }}</th>
                            @endforeach
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">Total</th>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase whitespace-nowrap">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $idx => $r)
                        <tr class="hover:bg-blue-50/50 transition-colors" wire:key="pac-inc-row-{{ $idx }}">
                            <td class="px-3 py-2.5 whitespace-nowrap font-semibold text-xs text-gray-900 sticky left-0 bg-white z-10 uppercase">{{ $r['name'] }}</td>
                            @for($m = 1; $m <= 12; $m++)
                                <td class="px-2 py-2.5 text-right whitespace-nowrap font-mono text-indigo-700 text-[11px]">
                                    @if($r['months'][$m] > 0)
                                        ${{ number_format($r['months'][$m], 2, ',', '.') }}
                                    @else
                                        <span class="text-gray-300">$-</span>
                                    @endif
                                </td>
                            @endfor
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono font-medium text-emerald-700">${{ number_format($r['total'], 2, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-gray-600 max-w-[250px] truncate" title="{{ $r['observations'] }}">{{ $r['observations'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="15" class="px-6 py-12 text-center text-gray-500">
                                <p>No se encontraron presupuestos de ingreso para esta vigencia</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($rows) > 0)
                    <tfoot class="bg-gray-50 font-semibold text-xs">
                        <tr>
                            <td class="px-3 py-3 text-right text-gray-700 uppercase sticky left-0 bg-gray-50 z-10">TOTALES</td>
                            @for($m = 1; $m <= 12; $m++)
                                <td class="px-2 py-3 text-right whitespace-nowrap font-mono text-indigo-700 text-[11px]">${{ number_format($totals['months'][$m] ?? 0, 2, ',', '.') }}</td>
                            @endfor
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-emerald-700">${{ number_format($totals['total'] ?? 0, 2, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Hidden data for JS --}}
    <div id="pac-income-report-data" class="hidden"
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
    var chartPacIncome = null;
    var mNames = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    var mFull = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

    function getData() {
        var el = document.getElementById('pac-income-report-data');
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
        var canvas = document.getElementById('chartPacIncomeMonthly');
        if (!canvas) return;
        if (chartPacIncome) chartPacIncome.destroy();

        var d = getData();
        if (!d) return;
        var mt = d.totals.months || {};
        var vals = [];
        for (var i = 1; i <= 12; i++) vals.push(mt[i] || 0);

        chartPacIncome = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: mNames,
                datasets: [{
                    label: 'Recaudo',
                    data: vals,
                    backgroundColor: 'rgba(16,185,129,0.75)',
                    borderRadius: 8,
                    barPercentage: 0.5
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function(ctx) { return '$' + ctx.parsed.y.toLocaleString('es-CO'); } } }
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
    var btnExport = document.getElementById('btn-export-pac-income');
    if (btnExport) {
        btnExport.addEventListener('click', function() {
            if (typeof XLSX === 'undefined') { alert('Cargando libreria...'); return; }
            var d = getData();
            if (!d) return;

            var wb = XLSX.utils.book_new();
            var hdr = [
                [d.school.name || 'INSTITUCION EDUCATIVA'],
                ['PAC INGRESOS ' + d.period],
                [],
                ['MES / CONCEPTO DE INGRESO'].concat(mFull, ['TOTAL', 'OBSERVACIONES'])
            ];

            var data = d.rows.map(function(r) {
                var row = [r.name];
                for (var m = 1; m <= 12; m++) row.push(r.months[m] || 0);
                row.push(r.total, r.observations || '');
                return row;
            });

            var mt = d.totals.months || {};
            var tot = ['TOTALES'];
            for (var m = 1; m <= 12; m++) tot.push(mt[m] || 0);
            tot.push(d.totals.total, '');

            var all = hdr.concat(data, [[]], [tot]);
            var ws = XLSX.utils.aoa_to_sheet(all);

            ws['!cols'] = [
                {wch:40},
                {wch:18},{wch:18},{wch:18},{wch:18},{wch:18},{wch:18},
                {wch:18},{wch:18},{wch:18},{wch:18},{wch:18},{wch:18},
                {wch:22},{wch:40}
            ];

            // Merge title rows
            ws['!merges'] = [
                {s:{r:0,c:0},e:{r:0,c:14}},
                {s:{r:1,c:0},e:{r:1,c:14}}
            ];

            // Format currency columns (1-13)
            var cc = [];
            for (var ci = 1; ci <= 13; ci++) cc.push(ci);
            for (var r = hdr.length; r < hdr.length + data.length; r++) {
                cc.forEach(function(c) {
                    var cell = XLSX.utils.encode_cell({r:r,c:c});
                    if (ws[cell] && typeof ws[cell].v === 'number') ws[cell].z = '$#,##0.00';
                });
            }
            var ti = all.length - 1;
            cc.forEach(function(c) {
                var cell = XLSX.utils.encode_cell({r:ti,c:c});
                if (ws[cell] && typeof ws[cell].v === 'number') ws[cell].z = '$#,##0.00';
            });

            XLSX.utils.book_append_sheet(wb, ws, 'PAC Ingresos');
            XLSX.writeFile(wb, 'PAC_Ingresos_' + (d.school.name || 'Colegio').replace(/[^a-zA-Z0-9]/g, '_') + '.xlsx');
        });
    }
});
</script>
@endpush

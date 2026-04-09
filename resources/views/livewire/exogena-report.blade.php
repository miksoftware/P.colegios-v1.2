<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Información Exógena - Formato 1001</h1>
                <p class="text-gray-500 mt-1">Pagos y abonos en cuenta a terceros (Medios Magnéticos DIAN)</p>
            </div>
            @can('reports.export')
            <div class="flex gap-3">
                <button id="btn-export-csv" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Exportar CSV (DIAN)
                </button>
                <button id="btn-export-excel" class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/30 transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Exportar Excel
                </button>
            </div>
            @endcan
        </div>

        {{-- Info del Colegio --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div><span class="text-gray-500">INFORME:</span> <span class="font-semibold text-gray-900">INFORMACIÓN EXÓGENA - FORMATO 1001</span></div>
                <div><span class="text-gray-500">NIT INFORMANTE:</span> <span class="font-semibold text-gray-900">{{ $school->nit ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">RAZÓN SOCIAL:</span> <span class="font-semibold text-gray-900">{{ $school->name ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">CÓDIGO DANE:</span> <span class="font-semibold text-gray-900">{{ $school->dane_code ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">MUNICIPIO:</span> <span class="font-semibold text-gray-900">{{ $school->municipality ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">RECTOR:</span> <span class="font-semibold text-gray-900">{{ $school->rector_name ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">PAGADOR:</span> <span class="font-semibold text-gray-900">{{ $school->pagador_name ?? 'N/A' }}</span></div>
                <div class="lg:col-span-2"><span class="text-gray-500">PERIODO:</span> <span class="font-semibold text-gray-900">{{ $this->periodLabel }}</span></div>
            </div>
        </div>

        {{-- Filtro --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año Gravable</label>
                    <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>

        {{-- Tarjetas resumen --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Terceros Reportados</p>
                <p class="text-xl font-bold text-gray-900">{{ $supplierCount }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Pagos/Abonos</p>
                <p class="text-xl font-bold text-blue-600">${{ number_format($totals['payment_deductible'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">IVA Mayor Valor</p>
                <p class="text-xl font-bold text-indigo-600">${{ number_format($totals['iva_greater_value'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Retención en la Fuente</p>
                <p class="text-xl font-bold text-emerald-600">${{ number_format($totals['retefuente'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">ReteIVA Practicado</p>
                <p class="text-xl font-bold text-amber-600">${{ number_format($totals['reteiva'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Gráfica --}}
        @if(count($rows) > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6" wire:ignore>
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Distribución por Concepto de Pago</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div style="height: 300px;"><canvas id="chartConceptos"></canvas></div>
                <div style="height: 300px;"><canvas id="chartRetenciones"></canvas></div>
            </div>
        </div>
        @endif

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Detalle Formato 1001 - Pagos a Terceros</h3>
                <p class="text-xs text-gray-500 mt-1">{{ count($rows) }} registros</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase whitespace-nowrap">Concepto</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">Tipo Doc.</th>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase whitespace-nowrap">No. Identificación</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">DV</th>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase">Razón Social / Nombre</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">Depto.</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">Mpio.</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">Pago Deducible</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">Pago No Deducible</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">IVA Mayor Valor</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">Ret. Fuente</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">ReteIVA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $idx => $r)
                        <tr class="hover:bg-blue-50/50 transition-colors" wire:key="row-{{ $idx }}">
                            <td class="px-3 py-2.5 whitespace-nowrap">
                                <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-purple-100 text-purple-700">{{ $r['concept_code'] }}</span>
                                <span class="text-gray-600 ml-1">{{ $r['concept_name'] }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-center font-mono">{{ $r['dian_doc_type'] }}</td>
                            <td class="px-3 py-2.5 font-mono text-blue-700">{{ $r['document_number'] }}</td>
                            <td class="px-3 py-2.5 text-center font-mono">{{ $r['dv'] ?: '-' }}</td>
                            <td class="px-3 py-2.5 text-gray-900 max-w-[200px] truncate" title="{{ $r['full_name'] }}">
                                @if($r['person_type'] === 'juridica')
                                    {{ $r['first_surname'] }}
                                @else
                                    {{ $r['first_surname'] }} {{ $r['second_surname'] }} {{ $r['first_name'] }} {{ $r['second_name'] }}
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center text-gray-600">{{ $r['department_code'] ?: '-' }}</td>
                            <td class="px-3 py-2.5 text-center text-gray-600">{{ $r['municipality_code'] ?: '-' }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-gray-700">${{ number_format($r['payment_deductible'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-gray-500">{{ $r['payment_non_deductible'] > 0 ? '$'.number_format($r['payment_non_deductible'], 0, ',', '.') : '-' }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-indigo-700">{{ $r['iva_greater_value'] > 0 ? '$'.number_format($r['iva_greater_value'], 0, ',', '.') : '-' }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-emerald-700">{{ $r['retefuente'] > 0 ? '$'.number_format($r['retefuente'], 0, ',', '.') : '-' }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-amber-700">{{ $r['reteiva'] > 0 ? '$'.number_format($r['reteiva'], 0, ',', '.') : '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="px-6 py-12 text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p>No se encontraron pagos a terceros para esta vigencia</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($rows) > 0)
                    <tfoot class="bg-gray-50 font-semibold text-xs">
                        <tr>
                            <td colspan="7" class="px-3 py-3 text-right text-gray-700 uppercase">Totales:</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-gray-900">${{ number_format($totals['payment_deductible'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-gray-900">${{ number_format($totals['payment_non_deductible'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-indigo-700">${{ number_format($totals['iva_greater_value'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-emerald-700">${{ number_format($totals['retefuente'], 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-amber-700">${{ number_format($totals['reteiva'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Hidden data for JS --}}
    <div id="exogena-data" class="hidden"
         data-school="{{ json_encode($school) }}"
         data-rows="{{ json_encode($rows) }}"
         data-totals="{{ json_encode($totals) }}"
         data-period="{{ $this->periodLabel }}"
         data-year="{{ $filterYear }}">
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var chartConceptos = null;
    var chartRetenciones = null;

    function getData() {
        var el = document.getElementById('exogena-data');
        if (!el) return null;
        return {
            school: JSON.parse(el.dataset.school || '{}'),
            rows: JSON.parse(el.dataset.rows || '[]'),
            totals: JSON.parse(el.dataset.totals || '{}'),
            period: el.dataset.period || '',
            year: el.dataset.year || ''
        };
    }

    function renderCharts() {
        var c1 = document.getElementById('chartConceptos');
        var c2 = document.getElementById('chartRetenciones');
        if (!c1 || !c2) return;
        if (chartConceptos) chartConceptos.destroy();
        if (chartRetenciones) chartRetenciones.destroy();

        var d = getData();
        if (!d || !d.rows.length) return;

        // Agrupar por concepto
        var byConcepto = {};
        d.rows.forEach(function(r) {
            var k = r.concept_code + ' - ' + r.concept_name;
            if (!byConcepto[k]) byConcepto[k] = { total: 0, retefuente: 0, reteiva: 0 };
            byConcepto[k].total += r.payment_deductible;
            byConcepto[k].retefuente += r.retefuente;
            byConcepto[k].reteiva += r.reteiva;
        });

        var labels = Object.keys(byConcepto);
        var colors = ['rgba(139,92,246,0.7)','rgba(59,130,246,0.7)','rgba(16,185,129,0.7)','rgba(245,158,11,0.7)','rgba(239,68,68,0.7)','rgba(99,102,241,0.7)'];

        chartConceptos = new Chart(c1, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: labels.map(function(l) { return byConcepto[l].total; }),
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2, borderColor: '#fff'
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11 } } },
                    title: { display: true, text: 'Pagos por Concepto', font: { size: 13 } },
                    tooltip: { callbacks: { label: function(ctx) { return ctx.label + ': $' + ctx.parsed.toLocaleString('es-CO'); } } }
                }
            }
        });

        chartRetenciones = new Chart(c2, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Ret. Fuente', data: labels.map(function(l) { return byConcepto[l].retefuente; }), backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 6 },
                    { label: 'ReteIVA', data: labels.map(function(l) { return byConcepto[l].reteiva; }), backgroundColor: 'rgba(245,158,11,0.7)', borderRadius: 6 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: 'Retenciones por Concepto', font: { size: 13 } },
                    tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': $' + ctx.parsed.y.toLocaleString('es-CO'); } } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function(v) { return '$' + (v >= 1000000 ? (v/1000000).toFixed(1)+'M' : (v/1000).toFixed(0)+'K'); } }, grid: { color: 'rgba(0,0,0,0.04)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    renderCharts();
    document.addEventListener('livewire:navigated', function() { setTimeout(renderCharts, 200); });
    if (typeof Livewire !== 'undefined') {
        Livewire.hook('morph.updated', function() { setTimeout(renderCharts, 200); });
    }

    // ── Export CSV formato DIAN 1001 ──
    var btnCsv = document.getElementById('btn-export-csv');
    if (btnCsv) {
        btnCsv.addEventListener('click', function() {
            var d = getData();
            if (!d || !d.rows.length) { alert('No hay datos para exportar.'); return; }

            // Estructura CSV del Formato 1001 DIAN
            var headers = [
                'Concepto',
                'Tipo Documento',
                'Numero Identificacion',
                'DV',
                'Primer Apellido',
                'Segundo Apellido',
                'Primer Nombre',
                'Otros Nombres',
                'Razon Social',
                'Direccion',
                'Codigo Dpto',
                'Codigo Mpio',
                'Pais',
                'Email',
                'Pago o abono en cuenta deducible',
                'Pago o abono en cuenta no deducible',
                'IVA mayor valor del costo o gasto deducible',
                'Retencion en la fuente practicada Renta',
                'IVA retenido'
            ];

            var csvRows = [headers.join('|')];

            d.rows.forEach(function(r) {
                var razonSocial = r.person_type === 'juridica' ? r.first_surname : '';
                var primerApellido = r.person_type === 'natural' ? r.first_surname : '';
                var segundoApellido = r.person_type === 'natural' ? r.second_surname : '';
                var primerNombre = r.person_type === 'natural' ? r.first_name : '';
                var otrosNombres = r.person_type === 'natural' ? r.second_name : '';

                var row = [
                    r.concept_code,
                    r.dian_doc_type,
                    r.document_number,
                    r.dv || '',
                    primerApellido,
                    segundoApellido,
                    primerNombre,
                    otrosNombres,
                    razonSocial,
                    r.address,
                    r.department_code,
                    r.municipality_code,
                    r.country_code,
                    r.email,
                    r.payment_deductible.toFixed(2),
                    r.payment_non_deductible.toFixed(2),
                    r.iva_greater_value.toFixed(2),
                    r.retefuente.toFixed(2),
                    r.reteiva.toFixed(2)
                ];
                csvRows.push(row.join('|'));
            });

            var csvContent = csvRows.join('\r\n');
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'Formato_1001_Exogena_' + d.year + '_' + (d.school.nit || 'NIT').replace(/[^a-zA-Z0-9]/g, '_') + '.csv';
            link.click();
        });
    }

    // ── Export Excel ──
    var btnExcel = document.getElementById('btn-export-excel');
    if (btnExcel) {
        btnExcel.addEventListener('click', function() {
            if (typeof XLSX === 'undefined') { alert('Cargando librería...'); return; }
            var d = getData();
            if (!d || !d.rows.length) { alert('No hay datos para exportar.'); return; }

            var wb = XLSX.utils.book_new();

            // Header info
            var hdr = [
                ['INFORMACIÓN EXÓGENA - FORMATO 1001'],
                ['PAGOS Y ABONOS EN CUENTA A TERCEROS'],
                [],
                ['NIT INFORMANTE:', d.school.nit || 'N/A'],
                ['RAZÓN SOCIAL:', d.school.name || 'N/A'],
                ['CÓDIGO DANE:', d.school.dane_code || 'N/A'],
                ['MUNICIPIO:', d.school.municipality || 'N/A'],
                ['AÑO GRAVABLE:', d.year],
                [],
                ['Concepto','Tipo Doc.','No. Identificación','DV','Primer Apellido','Segundo Apellido','Primer Nombre','Otros Nombres','Razón Social','Dirección','Cód. Depto.','Cód. Mpio.','País','Email','Pago Deducible','Pago No Deducible','IVA Mayor Valor','Ret. Fuente','ReteIVA']
            ];

            var data = d.rows.map(function(r) {
                var razonSocial = r.person_type === 'juridica' ? r.first_surname : '';
                var primerApellido = r.person_type === 'natural' ? r.first_surname : '';
                var segundoApellido = r.person_type === 'natural' ? r.second_surname : '';
                var primerNombre = r.person_type === 'natural' ? r.first_name : '';
                var otrosNombres = r.person_type === 'natural' ? r.second_name : '';

                return [
                    r.concept_code + ' - ' + r.concept_name,
                    r.dian_doc_type,
                    r.document_number,
                    r.dv || '',
                    primerApellido,
                    segundoApellido,
                    primerNombre,
                    otrosNombres,
                    razonSocial,
                    r.address,
                    r.department_code,
                    r.municipality_code,
                    r.country_code,
                    r.email,
                    r.payment_deductible,
                    r.payment_non_deductible,
                    r.iva_greater_value,
                    r.retefuente,
                    r.reteiva
                ];
            });

            var totRow = ['','','','','','','','','','','','','','TOTALES:',
                d.totals.payment_deductible, d.totals.payment_non_deductible,
                d.totals.iva_greater_value, d.totals.retefuente, d.totals.reteiva];

            var all = hdr.concat(data, [[]], [totRow]);
            var ws = XLSX.utils.aoa_to_sheet(all);

            ws['!cols'] = [
                {wch:30},{wch:10},{wch:18},{wch:5},{wch:18},{wch:18},{wch:18},{wch:18},
                {wch:35},{wch:30},{wch:12},{wch:12},{wch:8},{wch:30},
                {wch:20},{wch:20},{wch:20},{wch:18},{wch:18}
            ];

            // Merge title rows
            ws['!merges'] = [
                {s:{r:0,c:0},e:{r:0,c:6}},
                {s:{r:1,c:0},e:{r:1,c:6}}
            ];

            // Format currency columns
            var cc = [14,15,16,17,18];
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

            XLSX.utils.book_append_sheet(wb, ws, 'Formato 1001');
            XLSX.writeFile(wb, 'Exogena_1001_' + d.year + '_' + (d.school.nit || 'NIT').replace(/[^a-zA-Z0-9]/g, '_') + '.xlsx');
        });
    }
});
</script>
@endpush

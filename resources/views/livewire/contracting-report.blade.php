<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Informe de Contrataci&oacute;n</h1>
                <p class="text-gray-500 mt-1">Registro general de contratos y procesos de contrataci&oacute;n</p>
            </div>
            @can('reports.export')
            <button id="btn-export-contracting" class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar Excel
            </button>
            @endcan
        </div>

        {{-- Info del Colegio --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div><span class="text-gray-500">INFORME:</span> <span class="font-semibold text-gray-900">REGISTRO GENERAL DE CUENTAS</span></div>
                <div><span class="text-gray-500">C&Oacute;DIGO DANE:</span> <span class="font-semibold text-gray-900">{{ $school->dane_code ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">FONDO:</span> <span class="font-semibold text-gray-900">{{ $school->name ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">MUNICIPIO:</span> <span class="font-semibold text-gray-900">{{ $school->municipality ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">RECTOR:</span> <span class="font-semibold text-gray-900">{{ $school->rector_name ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">EMAIL:</span> <span class="font-semibold text-gray-900">{{ $school->email ?? 'N/A' }}</span></div>
                <div><span class="text-gray-500">PAGADOR:</span> <span class="font-semibold text-gray-900">{{ $school->pagador_name ?? 'N/A' }}</span></div>
                <div class="lg:col-span-2"><span class="text-gray-500">PERIODO:</span> <span class="font-semibold text-gray-900">{{ $this->periodLabel }}</span></div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vigencia</label>
                    <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                    <input type="text" wire:model.live.debounce.400ms="filterSupplier" class="w-full rounded-xl border-gray-300" placeholder="Nombre o documento...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select wire:model.live="filterStatus" class="w-full rounded-xl border-gray-300">
                        <option value="">Todos</option>
                        @foreach(\App\Models\Contract::STATUSES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Tarjetas Resumen --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Contratos</p>
                <p class="text-2xl font-bold text-gray-900">{{ $summary['total_contracts'] ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Valor Total</p>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($summary['total_amount'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Subtotal</p>
                <p class="text-2xl font-bold text-blue-600">${{ number_format($summary['total_subtotal'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">IVA Total</p>
                <p class="text-2xl font-bold text-amber-600">${{ number_format($summary['total_iva'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Gr&aacute;ficas --}}
        @if(count($rows) > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6" wire:ignore>
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Contratos por Estado</h3>
                <div style="height: 250px;"><canvas id="chartByStatus"></canvas></div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6" wire:ignore>
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Valor por Fuente de Financiaci&oacute;n</h3>
                <div style="height: 250px;"><canvas id="chartByFunding"></canvas></div>
            </div>
        </div>
        @endif

        {{-- Tabla Principal --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase whitespace-nowrap">No.</th>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase whitespace-nowrap">Rubro</th>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase">Nombre Rubro</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">CDP</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">Disponibilidad</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">Fecha CDP</th>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase">Proveedor</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">C&eacute;dula/NIT</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">Subtotal</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">IVA</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500 uppercase whitespace-nowrap">Total</th>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase">Objeto Contratado</th>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase whitespace-nowrap">C&oacute;digo</th>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase">Supervisor</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">Duraci&oacute;n</th>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase">Fuente</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">RP No.</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">Fecha RP</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">Inicio</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">Fin</th>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase whitespace-nowrap">Contrato</th>
                            <th class="px-3 py-3 text-left font-medium text-gray-500 uppercase whitespace-nowrap">Modalidad</th>
                            <th class="px-3 py-3 text-center font-medium text-gray-500 uppercase whitespace-nowrap">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $i => $r)
                        <tr class="hover:bg-blue-50/50 transition-colors" wire:key="row-{{ $r['id'] }}">
                            <td class="px-3 py-2.5 whitespace-nowrap text-gray-500">{{ $i + 1 }}</td>
                            <td class="px-3 py-2.5 whitespace-nowrap font-mono text-xs text-blue-700">{{ $r['expense_code'] }}</td>
                            <td class="px-3 py-2.5 text-gray-900 max-w-[200px] truncate" title="{{ $r['rubro_name'] }}">{{ $r['rubro_name'] }}</td>
                            <td class="px-3 py-2.5 text-center font-mono text-gray-700">{{ $r['cdp_number'] }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-gray-700">${{ number_format($r['disponibilidad'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-center whitespace-nowrap text-gray-600">{{ $r['fecha_cdp'] }}</td>
                            <td class="px-3 py-2.5">
                                <div class="font-medium text-gray-900 truncate max-w-[180px]" title="{{ $r['supplier_name'] }}">{{ $r['supplier_name'] }}</div>
                            </td>
                            <td class="px-3 py-2.5 text-center whitespace-nowrap font-mono text-gray-700">
                                {{ $r['supplier_document'] }}{{ $r['supplier_dv'] ? '-'.$r['supplier_dv'] : '' }}
                            </td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-gray-700">${{ number_format($r['subtotal'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono text-gray-700">${{ number_format($r['iva'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-right whitespace-nowrap font-mono font-medium text-gray-900">${{ number_format($r['total'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-gray-900 max-w-[250px] truncate" title="{{ $r['objeto'] }}">{{ $r['objeto'] }}</td>
                            <td class="px-3 py-2.5 whitespace-nowrap font-mono text-xs text-blue-700">{{ $r['expense_code'] }}</td>
                            <td class="px-3 py-2.5 text-gray-700 truncate max-w-[150px]" title="{{ $r['supervisor_name'] }}">{{ $r['supervisor_name'] }}</td>
                            <td class="px-3 py-2.5 text-center whitespace-nowrap text-gray-700">{{ $r['duracion_label'] }}</td>
                            <td class="px-3 py-2.5 whitespace-nowrap">
                                <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-blue-100 text-blue-700">{{ $r['funding_source'] }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-center font-mono text-gray-700">{{ $r['rp_number'] }}</td>
                            <td class="px-3 py-2.5 text-center whitespace-nowrap text-gray-600">{{ $r['fecha_rp'] }}</td>
                            <td class="px-3 py-2.5 text-center whitespace-nowrap text-gray-600">{{ $r['fecha_inicio'] }}</td>
                            <td class="px-3 py-2.5 text-center whitespace-nowrap text-gray-600">{{ $r['fecha_fin'] }}</td>
                            <td class="px-3 py-2.5 whitespace-nowrap text-gray-700 text-xs">{{ $r['contract_formatted'] }}</td>
                            <td class="px-3 py-2.5 whitespace-nowrap text-gray-700 text-xs">{{ $r['modalidad'] }}</td>
                            <td class="px-3 py-2.5 text-center whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-gray-100 text-gray-700',
                                        'active' => 'bg-blue-100 text-blue-700',
                                        'in_execution' => 'bg-yellow-100 text-yellow-700',
                                        'completed' => 'bg-green-100 text-green-700',
                                        'annulled' => 'bg-red-100 text-red-700',
                                        'suspended' => 'bg-orange-100 text-orange-700',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 text-[10px] font-medium rounded-full {{ $statusColors[$r['status']] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $r['status_name'] }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="23" class="px-6 py-12 text-center text-gray-500">
                                <p>No se encontraron contratos para la vigencia seleccionada</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(count($rows) > 0)
                    <tfoot class="bg-gray-50 font-semibold text-xs">
                        <tr>
                            <td colspan="8" class="px-3 py-3 text-right text-gray-700 uppercase">Totales:</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-gray-900">${{ number_format($summary['total_subtotal'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-gray-900">${{ number_format($summary['total_iva'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap font-mono text-gray-900">${{ number_format($summary['total_amount'] ?? 0, 0, ',', '.') }}</td>
                            <td colspan="12"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- Resumen por Estado --}}
        @if(count($summary['by_status'] ?? []) > 0)
        <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Resumen por Estado</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Contratos</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($summary['by_status'] as $st)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 font-medium text-gray-900">{{ $st['name'] }}</td>
                        <td class="px-6 py-3 text-center text-gray-700">{{ $st['count'] }}</td>
                        <td class="px-6 py-3 text-right font-mono text-gray-900">${{ number_format($st['total'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Hidden data for JS --}}
    <div id="contracting-report-data" class="hidden"
         data-school="{{ json_encode($school) }}"
         data-rows="{{ json_encode($rows) }}"
         data-summary="{{ json_encode($summary) }}"
         data-period="{{ $this->periodLabel }}"
         data-year="{{ $filterYear }}">
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var chartStatus = null, chartFunding = null;
    var colors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#f97316'];

    function getData() {
        var el = document.getElementById('contracting-report-data');
        if (!el) return null;
        return {
            school: JSON.parse(el.dataset.school || '{}'),
            rows: JSON.parse(el.dataset.rows || '[]'),
            summary: JSON.parse(el.dataset.summary || '{}'),
            period: el.dataset.period || '',
            year: el.dataset.year || ''
        };
    }

    function renderCharts() {
        var d = getData(); if (!d) return;
        var sd = d.summary.by_status || [], fd = d.summary.by_funding || [];

        var cS = document.getElementById('chartByStatus');
        if (cS) {
            if (chartStatus) chartStatus.destroy();
            chartStatus = new Chart(cS, {
                type: 'doughnut',
                data: {
                    labels: sd.map(function(x) { return x.name; }),
                    datasets: [{
                        data: sd.map(function(x) { return x.count; }),
                        backgroundColor: sd.map(function(_, i) { return colors[i % colors.length]; }),
                        borderWidth: 2, borderColor: '#fff', hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '60%',
                    plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } } } }
                }
            });
        }

        var cF = document.getElementById('chartByFunding');
        if (cF) {
            if (chartFunding) chartFunding.destroy();
            chartFunding = new Chart(cF, {
                type: 'bar',
                data: {
                    labels: fd.map(function(x) { return x.name.length > 30 ? x.name.substring(0, 30) + '...' : x.name; }),
                    datasets: [{
                        label: 'Valor Total',
                        data: fd.map(function(x) { return x.total; }),
                        backgroundColor: 'rgba(59,130,246,0.7)',
                        borderRadius: 6, barPercentage: 0.5
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(ctx) { return ' $' + ctx.parsed.x.toLocaleString('es-CO'); } } } },
                    scales: { x: { beginAtZero: true, ticks: { callback: function(v) { return '$' + (v >= 1000000 ? (v / 1000000).toFixed(1) + 'M' : (v / 1000).toFixed(0) + 'K'); } }, grid: { color: 'rgba(0,0,0,0.04)' } }, y: { grid: { display: false } } }
                }
            });
        }
    }

    renderCharts();
    if (typeof Livewire !== 'undefined') Livewire.hook('morph.updated', function() { setTimeout(renderCharts, 200); });

    // Export Excel
    var btnExport = document.getElementById('btn-export-contracting');
    if (btnExport) {
        btnExport.addEventListener('click', function() {
            if (typeof XLSX === 'undefined') { alert('Cargando libreria...'); return; }
            var d = getData(); if (!d) return;
            var s = d.school;

            var wb = XLSX.utils.book_new();

            // Header info
            var hdr = [
                ['REGISTRO GENERAL DE CUENTAS'],
                ['INFORME DE CONTRATACIÓN - ' + (s.name || 'N/A')],
                ['CÓDIGO DANE: ' + (s.dane_code || 'N/A'), '', 'MUNICIPIO: ' + (s.municipality || 'N/A'), '', 'RECTOR: ' + (s.rector_name || 'N/A')],
                ['VIGENCIA: ' + d.year, '', 'NIT: ' + (s.nit || 'N/A'), '', 'PAGADOR: ' + (s.pagador_name || 'N/A')],
                []
            ];

            // Column headers - matching the original Excel structure
            var colHeaders = [
                'RUBRO', 'PROX. DISP', 'RUBRO CÓDIGO', 'CDP N.', 'DISPONIBILIDAD', 'FECHA CDP',
                '1ER APELLIDO', '2DO APELLIDO', '1ER NOMBRE', '2DO NOMBRE', 'NUMERO', 'DV',
                'RUBRO NOMBRE', 'VALOR', 'IVA', 'TOTAL', 'VALOR LETRAS',
                'OBJETO CONTRATADO', 'CODIGO', 'SUPERVISOR',
                'CUENTA CONTABLE', 'CUENTA NOMBRE',
                'CUENTA PADRE CÓDIGO', 'CUENTA PADRE NOMBRE',
                'CÓDIGO GASTO', 'NECESIDADES A SATISFACER',
                'DURACION CONTRATO', 'RIESGOS AMPARADOS',
                'DIRECCION', 'TELEFONO', 'CIUDAD', 'REGIMEN',
                'FORMA DE PAGO', 'RECURSOS FINANCIACION',
                'FECHA REGISTRO PRESUPUESTAL', 'FECHA INICIACION CONTRATO', 'FECHA TERMINACION CONTRATO',
                'No. CONTRATO U ORDEN', 'DEPENDENCIA SOLICITANTE',
                'CUENTA CORRIENTE', 'BANCO',
                'FECHA LIQUIDACION CONTRATO', 'PROVEEDOR',
                'PLAZO', 'CEDULA SUPERVISOR', 'NOMBRE SUPERVISOR', 'CARGO SUPERVISOR',
                'FECHA DESIGNACION SUPERVISOR', 'NUMERO RESOLUCION DESIGNACION SUPERVISOR',
                'MODALIDAD DE CONTRATACIÓN', 'NUMERO DE RP', 'FECHA RP',
                'FORMA DE PAGO DEL CONTRATO',
                'CRITERIOS PARA EVALUAR LA PROPUESTA MAS FAVORABLE',
                'LUGAR DE EJECUCION DEL CONTRATO', 'FECHA DE CONTRATO',
                'REPRESENTANTE LEGAL DE LA EMPRESA (SI APLICA)',
                'INVITACION A COTIZAR O PRESENTAR PROPUESTA', 'FECHA DE INVITACION',
                'INTRODUCCION DE ACUERDO AL MANUAL DE LA INSTITUCIÓN',
                'PRESUPUESTO ASIGNADO',
                'FECHA MAXIMA PRESENTACION PROPUESTA', 'HORA DE PRESENTACIÓN DE PROPUESTA',
                'FECHA REVISION PROPUESTAS', 'HORA DE CIERRE DE REVISION DE PROPUESTAS',
                'FECHA ACTA DE EVALUACIÓN', 'NUMERO DE PROPUESTAS RECIBIDAS',
                'ACUERDO PAA', 'FECHA DE ACUERDO', 'FECHA DE CERTIFICACION',
                'CODIGO GASTO SIFSE', 'CODIGO FUENTE DE FINANCIÓN'
            ];

            hdr.push(colHeaders);

            // Data rows
            var data = d.rows.map(function(r) {
                return [
                    r.rubro_name,
                    r.prox_disp,
                    r.rubro_code,
                    r.cdp_number,
                    r.disponibilidad,
                    r.fecha_cdp,
                    r.supplier_first_surname,
                    r.supplier_second_surname,
                    r.supplier_first_name,
                    r.supplier_second_name,
                    r.supplier_document,
                    r.supplier_dv,
                    r.rubro_name,
                    r.subtotal,
                    r.iva,
                    r.total,
                    r.valor_letras || '',
                    r.objeto,
                    r.expense_code,
                    r.supervisor_name,
                    r.acct_code,
                    r.acct_name,
                    r.acct_parent_code,
                    r.acct_parent_name,
                    r.expense_code,
                    r.necesidades,
                    r.duracion_label,
                    r.riesgos,
                    r.supplier_address,
                    r.supplier_phone,
                    r.supplier_city,
                    r.supplier_regime,
                    r.forma_pago,
                    r.funding_source,
                    r.fecha_rp,
                    r.fecha_inicio,
                    r.fecha_fin,
                    r.contract_formatted,
                    r.dependencia,
                    r.bank_account,
                    r.bank_name,
                    r.fecha_liquidacion,
                    r.supplier_name,
                    r.plazo,
                    r.supervisor_document,
                    r.supervisor_name,
                    r.supervisor_cargo,
                    r.fecha_inicio,
                    r.convocatoria_formatted ? 'ACUERDO No. ' + r.convocatoria_formatted : '',
                    r.modalidad,
                    r.rp_number,
                    r.fecha_rp,
                    r.forma_pago,
                    r.criterio_evaluacion,
                    r.lugar_ejecucion,
                    r.contract_date,
                    r.representante_legal,
                    r.convocatoria_formatted || '',
                    r.fecha_invitacion,
                    r.intro_manual,
                    r.presupuesto_asignado,
                    r.fecha_max_propuesta,
                    r.hora_propuesta || '',
                    r.fecha_revision,
                    r.hora_revision || '',
                    r.fecha_evaluacion,
                    r.num_propuestas,
                    r.acuerdo_paa,
                    r.fecha_acuerdo,
                    r.fecha_certificacion,
                    r.sifse_code,
                    r.funding_source_codes
                ];
            });

            // Totals row
            var totals = ['', '', '', '', '', '', '', '', '', '', '', '', 'TOTALES:',
                d.summary.total_subtotal, d.summary.total_iva, d.summary.total_amount];
            // Pad remaining columns
            for (var p = totals.length; p < colHeaders.length; p++) totals.push('');

            var all = hdr.concat(data, [[]], [totals]);
            var ws = XLSX.utils.aoa_to_sheet(all);

            // Column widths
            ws['!cols'] = [
                {wch:40},{wch:15},{wch:28},{wch:8},{wch:18},{wch:12},
                {wch:18},{wch:18},{wch:18},{wch:18},{wch:15},{wch:5},
                {wch:40},{wch:18},{wch:15},{wch:18},{wch:35},
                {wch:60},{wch:28},{wch:30},
                {wch:12},{wch:30},{wch:12},{wch:30},{wch:28},
                {wch:60},{wch:18},{wch:20},
                {wch:35},{wch:15},{wch:18},{wch:20},
                {wch:25},{wch:30},
                {wch:15},{wch:15},{wch:15},
                {wch:25},{wch:18},
                {wch:20},{wch:25},
                {wch:15},{wch:35},
                {wch:12},{wch:20},{wch:30},{wch:18},
                {wch:15},{wch:25},
                {wch:25},{wch:10},{wch:12},
                {wch:30},{wch:40},{wch:30},{wch:12},
                {wch:40},{wch:10},{wch:12},
                {wch:80},
                {wch:18},{wch:12},{wch:15},{wch:12},{wch:15},
                {wch:12},{wch:8},
                {wch:20},{wch:12},{wch:12},
                {wch:12},{wch:15}
            ];

            // Merges for header
            ws['!merges'] = [
                {s:{r:0,c:0},e:{r:0,c:10}},
                {s:{r:1,c:0},e:{r:1,c:10}}
            ];

            // Format currency columns
            var currCols = [1,4,13,14,15,60];
            var headerRows = hdr.length;
            for (var r = headerRows; r < headerRows + data.length; r++) {
                currCols.forEach(function(c) {
                    var cell = XLSX.utils.encode_cell({r:r,c:c});
                    if (ws[cell] && typeof ws[cell].v === 'number') ws[cell].z = '#,##0';
                });
            }
            // Format totals
            var ti = all.length - 1;
            currCols.forEach(function(c) {
                var cell = XLSX.utils.encode_cell({r:ti,c:c});
                if (ws[cell] && typeof ws[cell].v === 'number') ws[cell].z = '#,##0';
            });

            XLSX.utils.book_append_sheet(wb, ws, 'Informe Contratación');
            XLSX.writeFile(wb, 'Informe_Contratacion_' + (s.name || 'Colegio').replace(/[^a-zA-Z0-9]/g, '_') + '_' + d.year + '.xlsx');
        });
    }
});
</script>
@endpush

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Relaci&oacute;n de Pagos</h1>
                <p class="text-gray-500 mt-1">Informe consolidado de comprobantes de egreso</p>
            </div>
            @can('reports.export')
            <button id="btn-export-payment" onclick="window.exportPaymentExcel()" class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exportar Excel
            </button>
            @endcan
        </div>

        {{-- Info del Colegio --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                <div><span class="text-gray-500">INFORME:</span> <span class="font-semibold text-gray-900">RELACI&Oacute;N DE PAGOS</span></div>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Período</label>
                    <select wire:model.live="filterPeriodType" class="w-full rounded-xl border-gray-300">
                        <option value="annual">Anual</option>
                        <option value="semiannual">Semestral</option>
                        <option value="quarterly">Trimestral</option>
                        <option value="monthly">Mensual</option>
                    </select>
                </div>
                <div>
                    @if($filterPeriodType === 'monthly')
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
                        <select wire:model.live="filterMonth" class="w-full rounded-xl border-gray-300">
                            <option value="">— Seleccionar mes —</option>
                            @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $i => $m)
                                <option value="{{ $i + 1 }}">{{ $m }}</option>
                            @endforeach
                        </select>
                    @elseif($filterPeriodType === 'quarterly')
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trimestre</label>
                        <select wire:model.live="filterQuarter" class="w-full rounded-xl border-gray-300">
                            <option value="">— Seleccionar trimestre —</option>
                            <option value="1">1° Trimestre (Ene – Mar)</option>
                            <option value="2">2° Trimestre (Abr – Jun)</option>
                            <option value="3">3° Trimestre (Jul – Sep)</option>
                            <option value="4">4° Trimestre (Oct – Dic)</option>
                        </select>
                    @elseif($filterPeriodType === 'semiannual')
                        <label class="block text-sm font-medium text-gray-700 mb-1">Semestre</label>
                        <select wire:model.live="filterSemester" class="w-full rounded-xl border-gray-300">
                            <option value="">— Seleccionar semestre —</option>
                            <option value="1">1° Semestre (Ene – Jun)</option>
                            <option value="2">2° Semestre (Jul – Dic)</option>
                        </select>
                    @else
                        <label class="block text-sm font-medium text-gray-700 mb-1">&nbsp;</label>
                        <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-400">Todos los meses del año</div>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                    <input type="text" wire:model.live.debounce.400ms="filterSupplier" class="w-full rounded-xl border-gray-300" placeholder="Nombre o documento...">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-3">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fuente de Ingreso</label>
                    <input type="text" wire:model.live.debounce.400ms="filterFundingSource" class="w-full rounded-xl border-gray-300" placeholder="SGP, RP...">
                </div>
            </div>
        </div>

        {{-- Tarjetas --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Pagos</p>
                <p class="text-2xl font-bold text-gray-900">{{ $summary['total_payments'] ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Valor Total Cuentas</p>
                <p class="text-2xl font-bold text-gray-900">${{ number_format($summary['total_amount'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Retenciones</p>
                <p class="text-2xl font-bold text-red-600">${{ number_format($summary['total_retentions'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500">Total Transferido</p>
                <p class="text-2xl font-bold text-emerald-600">${{ number_format($summary['total_net'] ?? 0, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Graficas --}}
        @if(count($payments) > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6" wire:ignore>
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Pagos por Fuente de Financiaci&oacute;n</h3>
                <div style="height: 250px;"><canvas id="chartByFunding"></canvas></div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6" wire:ignore>
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Pagos por Mes</h3>
                <div style="height: 250px;"><canvas id="chartByMonth"></canvas></div>
            </div>
        </div>
        @endif

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. CE</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fuente</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rubro</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contrato</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">CDP</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">RP</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor Total</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Retenciones</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Neto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $pi => $p)
                    <tr class="hover:bg-blue-50/50 transition-colors" wire:key="payment-{{ $p['id'] }}-{{ $pi }}">
                        <td class="px-4 py-3 whitespace-nowrap font-mono font-medium text-blue-600">{{ $p['formatted_number'] }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $p['payment_date'] }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 truncate max-w-[200px]" title="{{ $p['supplier_name'] }}">{{ $p['supplier_name'] }}</div>
                            <div class="text-xs text-gray-500 font-mono">{{ $p['supplier_document'] }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">{{ $p['funding_source'] }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs text-gray-900">{{ $p['rubro_code'] }}</div>
                            <div class="text-xs text-gray-500 truncate max-w-[240px]" title="{{ $p['rubro_name'] }}">{{ $p['rubro_name'] }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700 text-xs">{{ $p['contract_number'] }}</td>
                        <td class="px-4 py-3 text-center font-mono text-gray-700">{{ $p['cdp_number'] }}</td>
                        <td class="px-4 py-3 text-center font-mono text-gray-700">{{ $p['rp_number'] }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap font-mono font-medium text-gray-900">${{ number_format($p['total'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap font-mono text-red-600">${{ number_format($p['retefuente'] + $p['reteiva'] + $p['estampillas'] + $p['otros_impuestos'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap font-mono font-medium text-emerald-700">${{ number_format($p['net_payment'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-6 py-12 text-center text-gray-500">
                            <p>No se encontraron pagos para el periodo seleccionado</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($payments) > 0)
                <tfoot class="bg-gray-50 font-semibold text-sm">
                    <tr>
                        <td colspan="8" class="px-4 py-3 text-right text-gray-700 uppercase text-xs">Totales:</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap font-mono text-gray-900">${{ number_format($summary['total_amount'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap font-mono text-red-700">${{ number_format($summary['total_retentions'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap font-mono text-emerald-700">${{ number_format($summary['total_net'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        {{-- Resumen por Fuente --}}
        @if(count($summary['by_funding_source'] ?? []) > 0)
        <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Resumen por Fuente de Financiaci&oacute;n</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fuente</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Pagos</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valor Total</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Neto Transferido</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($summary['by_funding_source'] as $fs)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 font-medium text-gray-900">{{ $fs['name'] }}</td>
                        <td class="px-6 py-3 text-center text-gray-700">{{ $fs['count'] }}</td>
                        <td class="px-6 py-3 text-right font-mono text-gray-900">${{ number_format($fs['total'], 0, ',', '.') }}</td>
                        <td class="px-6 py-3 text-right font-mono text-emerald-700">${{ number_format($fs['net'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Hidden data for JS --}}
    <div id="payment-report-data" class="hidden"
         data-school="{{ json_encode($school) }}"
         data-payments="{{ json_encode($payments) }}"
         data-summary="{{ json_encode($summary) }}"
         data-period="{{ $this->periodLabel }}">
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var chartFunding = null, chartMonth = null;
    var colors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4'];
    var mNames = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    function getData() {
        var el = document.getElementById('payment-report-data');
        if (!el) return null;
        return { school: JSON.parse(el.dataset.school||'{}'), payments: JSON.parse(el.dataset.payments||'[]'), summary: JSON.parse(el.dataset.summary||'{}'), period: el.dataset.period||'' };
    }

    function renderCharts() {
        var d = getData(); if (!d) return;
        var fd = d.summary.by_funding_source||[], md = d.summary.by_month||[];

        var cF = document.getElementById('chartByFunding');
        if (cF) {
            if (chartFunding) chartFunding.destroy();
            chartFunding = new Chart(cF, {
                type:'doughnut',
                data:{labels:fd.map(function(x){return x.name;}),datasets:[{data:fd.map(function(x){return x.total;}),backgroundColor:fd.map(function(_,i){return colors[i%colors.length];}),borderWidth:2,borderColor:'#fff',hoverOffset:6}]},
                options:{responsive:true,maintainAspectRatio:false,cutout:'60%',plugins:{legend:{position:'bottom',labels:{padding:12,usePointStyle:true,pointStyle:'circle',font:{size:11}}},tooltip:{callbacks:{label:function(ctx){return ' $'+ctx.parsed.toLocaleString('es-CO');}}}}}
            });
        }

        var cM = document.getElementById('chartByMonth');
        if (cM) {
            if (chartMonth) chartMonth.destroy();
            chartMonth = new Chart(cM, {
                type:'bar',
                data:{labels:md.map(function(x){var p=x.month.split('-');return mNames[parseInt(p[1])]||x.month;}),datasets:[{label:'Valor Total',data:md.map(function(x){return x.total;}),backgroundColor:'rgba(99,102,241,0.75)',borderRadius:6,barPercentage:0.5},{label:'Neto',data:md.map(function(x){return x.net;}),backgroundColor:'rgba(16,185,129,0.75)',borderRadius:6,barPercentage:0.5}]},
                options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{padding:12,usePointStyle:true,pointStyle:'circle',font:{size:11}}},tooltip:{callbacks:{label:function(ctx){return ' $'+ctx.parsed.y.toLocaleString('es-CO');}}}},scales:{y:{beginAtZero:true,ticks:{callback:function(v){return '$'+(v>=1000000?(v/1000000).toFixed(1)+'M':(v/1000).toFixed(0)+'K');}},grid:{color:'rgba(0,0,0,0.04)'}},x:{grid:{display:false}}}}
            });
        }
    }

    renderCharts();
    if (typeof Livewire!=='undefined') Livewire.hook('morph.updated',function(){setTimeout(renderCharts,200);});

    window.exportPaymentExcel = function() {
        if (typeof XLSX==='undefined'){alert('Cargando...');return;}
        var d = getData(); if (!d) return;
        var wb = XLSX.utils.book_new();
        var hdr = [['INFORME:','RELACION DE PAGOS'],['CODIGO DANE:',d.school.dane_code||'N/A'],['FONDO:',d.school.name||'N/A'],['MUNICIPIO:',d.school.municipality||'N/A'],['RECTOR:',d.school.rector_name||'N/A'],['EMAIL:',d.school.email||'N/A'],['PAGADOR:',d.school.pagador_name||'N/A'],['PERIODO:',d.period],[],['No. CE','Fecha CE','No. Factura','Fecha Factura','Proveedor','NIT/Cedula','Direccion','Fuente de Ingreso','Rubro Presupuestal','Nombre del Rubro','Detalle del Pago','Sede Destino','No. Contrato','No. CDP','No. RP','Valor Total','Retencion','ReteIVA','Estampillas','Otros Impuestos','Vr. Transferencia']];
        var data = d.payments.map(function(p){return [p.formatted_number,p.payment_date,p.invoice_number,p.invoice_date,p.supplier_name,p.supplier_document,p.supplier_address,p.funding_source,p.rubro_code,p.rubro_name,p.detail,p.sede,p.contract_number,p.cdp_number,p.rp_number,p.total,p.retefuente,p.reteiva,p.estampillas,p.otros_impuestos,p.net_payment];});
        var tot = ['','','','','','','','','','','','','','','TOTALES:',d.summary.total_amount,d.payments.reduce(function(s,p){return s+p.retefuente;},0),d.payments.reduce(function(s,p){return s+p.reteiva;},0),d.payments.reduce(function(s,p){return s+p.estampillas;},0),d.payments.reduce(function(s,p){return s+p.otros_impuestos;},0),d.summary.total_net];
        var all = hdr.concat(data,[[],[tot]]);
        var ws = XLSX.utils.aoa_to_sheet(all);
        ws['!merges']=[{s:{r:0,c:1},e:{r:0,c:4}}];
        ws['!cols']=[{wch:10},{wch:12},{wch:18},{wch:12},{wch:35},{wch:15},{wch:25},{wch:25},{wch:25},{wch:40},{wch:50},{wch:12},{wch:20},{wch:8},{wch:8},{wch:18},{wch:15},{wch:15},{wch:15},{wch:15},{wch:18}];
        var cc=[15,16,17,18,19,20];
        for(var r=hdr.length;r<hdr.length+data.length;r++){cc.forEach(function(c){var cell=XLSX.utils.encode_cell({r:r,c:c});if(ws[cell]&&typeof ws[cell].v==='number')ws[cell].z='#,##0.00';});}
        var ti=all.length-1;cc.forEach(function(c){var cell=XLSX.utils.encode_cell({r:ti,c:c});if(ws[cell]&&typeof ws[cell].v==='number')ws[cell].z='#,##0.00';});
        XLSX.utils.book_append_sheet(wb,ws,'Relacion de Pagos');
        XLSX.writeFile(wb,'Relacion_Pagos_'+(d.school.name||'Colegio').replace(/[^a-zA-Z0-9]/g,'_')+'.xlsx');
    };
});
</script>
@endpush

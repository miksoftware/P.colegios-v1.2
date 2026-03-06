<div>
    @if($school)
        {{-- ── Header del colegio ── --}}
        <div class="mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-blue-500 rounded-2xl shadow-xl shadow-blue-500/20 p-8 text-white">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">{{ $school->name }}</h1>
                        <div class="flex flex-wrap items-center gap-4 text-blue-100 text-sm">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                NIT: {{ $school->nit }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $school->municipality }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Rector(a): {{ $school->rector_name }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                DANE: {{ $school->dane_code }}
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl px-4 py-2">
                            <p class="text-xs text-blue-100">Vigencia</p>
                            <p class="text-2xl font-bold">{{ $year }}</p>
                        </div>
                        @if(auth()->user()->hasRole('Admin'))
                            <button 
                                x-data @click="$dispatch('open-school-modal')" 
                                class="mt-2 flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl transition-all cursor-pointer text-sm font-semibold"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                Cambiar Colegio
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── KPIs Principales ── --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Presupuesto de Ingresos --}}
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-xs font-medium px-2 py-1 rounded-full {{ $incomeExecutionPercent >= 80 ? 'bg-emerald-50 text-emerald-700' : ($incomeExecutionPercent >= 50 ? 'bg-yellow-50 text-yellow-700' : 'bg-red-50 text-red-700') }}">
                        {{ $incomeExecutionPercent }}%
                    </span>
                </div>
                <p class="text-xs text-gray-500 mb-1">Presupuesto Ingresos</p>
                <p class="text-xl font-bold text-gray-900">${{ number_format($totalIncomeBudget, 0, ',', '.') }}</p>
                <div class="mt-3">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Recaudado</span>
                        <span>${{ number_format($totalIncomeReceived, 0, ',', '.') }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: {{ min($incomeExecutionPercent, 100) }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Presupuesto de Gastos --}}
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <span class="text-xs font-medium px-2 py-1 rounded-full {{ $expenseExecutionPercent >= 80 ? 'bg-blue-50 text-blue-700' : ($expenseExecutionPercent >= 50 ? 'bg-yellow-50 text-yellow-700' : 'bg-gray-50 text-gray-700') }}">
                        {{ $expenseExecutionPercent }}%
                    </span>
                </div>
                <p class="text-xs text-gray-500 mb-1">Presupuesto Gastos</p>
                <p class="text-xl font-bold text-gray-900">${{ number_format($totalExpenseBudget, 0, ',', '.') }}</p>
                <div class="mt-3">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Ejecutado</span>
                        <span>${{ number_format($totalExpenseExecuted, 0, ',', '.') }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full transition-all" style="width: {{ min($expenseExecutionPercent, 100) }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Comprometido --}}
            @php
                $commitPercent = $totalExpenseBudget > 0 ? round(($totalExpenseCommitted / $totalExpenseBudget) * 100, 1) : 0;
                $availableBudget = $totalExpenseBudget - $totalExpenseExecuted - $totalExpenseCommitted;
            @endphp
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <span class="text-xs font-medium px-2 py-1 rounded-full bg-amber-50 text-amber-700">{{ $commitPercent }}%</span>
                </div>
                <p class="text-xs text-gray-500 mb-1">Comprometido</p>
                <p class="text-xl font-bold text-gray-900">${{ number_format($totalExpenseCommitted, 0, ',', '.') }}</p>
                <div class="mt-3">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Disponible</span>
                        <span>${{ number_format(max($availableBudget, 0), 0, ',', '.') }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="bg-amber-500 h-2 rounded-full transition-all" style="width: {{ min($commitPercent, 100) }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Pagos netos --}}
            @php
                $totalPaid = collect($recentPayments)->where('status', 'paid')->sum('net_payment');
            @endphp
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mb-1">Órdenes de Pago</p>
                <p class="text-xl font-bold text-gray-900">{{ $totalPaymentOrders }}</p>
                <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
                    <span>Contratos: {{ $totalContracts }}</span>
                    <span>CDPs: {{ $totalCdps }}</span>
                </div>
            </div>
        </div>

        {{-- ── Fila de contadores rápidos ── --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <a href="{{ route('budgets.index') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-md transition-shadow text-center group">
                <p class="text-2xl font-bold text-blue-600 group-hover:text-blue-700">{{ $totalIncomeBudget > 0 || $totalExpenseBudget > 0 ? '✓' : '—' }}</p>
                <p class="text-xs text-gray-500 mt-1">Presupuesto</p>
            </a>
            <a href="{{ route('incomes.index') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-md transition-shadow text-center group">
                <p class="text-2xl font-bold text-emerald-600 group-hover:text-emerald-700">${{ number_format($totalIncomeReceived / 1000000, 1) }}M</p>
                <p class="text-xs text-gray-500 mt-1">Ingresos</p>
            </a>
            <a href="{{ route('precontractual.index') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-md transition-shadow text-center group">
                <p class="text-2xl font-bold text-indigo-600 group-hover:text-indigo-700">{{ $totalConvocatorias }}</p>
                <p class="text-xs text-gray-500 mt-1">Convocatorias</p>
            </a>
            <a href="{{ route('contractual.index') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-md transition-shadow text-center group">
                <p class="text-2xl font-bold text-teal-600 group-hover:text-teal-700">{{ $totalContracts }}</p>
                <p class="text-xs text-gray-500 mt-1">Contratos</p>
            </a>
            <a href="{{ route('postcontractual.index') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-md transition-shadow text-center group">
                <p class="text-2xl font-bold text-purple-600 group-hover:text-purple-700">{{ $totalPaymentOrders }}</p>
                <p class="text-xs text-gray-500 mt-1">Pagos</p>
            </a>
            <a href="{{ route('budget-transfers.index') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:shadow-md transition-shadow text-center group">
                <p class="text-2xl font-bold text-orange-600 group-hover:text-orange-700">{{ $totalTransfers }}</p>
                <p class="text-xs text-gray-500 mt-1">Traslados</p>
            </a>
        </div>

        {{-- ── Gráficos y tablas ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Presupuesto por fuente de financiación --}}
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                    Presupuesto por Fuente
                </h3>
                @if(count($budgetBySource) > 0)
                    @php $maxSource = collect($budgetBySource)->max('total') ?: 1; @endphp
                    <div class="space-y-4">
                        @foreach($budgetBySource as $source)
                            @php
                                $percent = round(($source['total'] / $maxSource) * 100);
                                $colors = match($source['type']) {
                                    'SGP' => ['bg-blue-500', 'bg-blue-50', 'text-blue-700'],
                                    'Recursos Propios' => ['bg-emerald-500', 'bg-emerald-50', 'text-emerald-700'],
                                    'Recursos Balance' => ['bg-amber-500', 'bg-amber-50', 'text-amber-700'],
                                    default => ['bg-gray-500', 'bg-gray-50', 'text-gray-700'],
                                };
                            @endphp
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium text-gray-700">{{ $source['type'] }}</span>
                                    <span class="text-sm font-bold {{ $colors[2] }}">${{ number_format($source['total'], 0, ',', '.') }}</span>
                                </div>
                                <div class="w-full {{ $colors[1] }} rounded-full h-3">
                                    <div class="{{ $colors[0] }} h-3 rounded-full transition-all" style="width: {{ $percent }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                        <p class="text-sm">Sin datos presupuestales</p>
                    </div>
                @endif
            </div>

            {{-- Ingresos mensuales --}}
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    Ingresos Mensuales {{ $year }}
                </h3>
                @if(count($monthlyIncome) > 0)
                    @php
                        $months = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                        $maxMonth = collect($monthlyIncome)->max('total') ?: 1;
                    @endphp
                    <div class="flex items-end gap-2 h-48">
                        @for($m = 1; $m <= 12; $m++)
                            @php
                                $monthData = collect($monthlyIncome)->firstWhere('mes', $m);
                                $value = $monthData['total'] ?? 0;
                                $height = $maxMonth > 0 ? max(($value / $maxMonth) * 100, 2) : 2;
                            @endphp
                            <div class="flex-1 flex flex-col items-center gap-1">
                                <div class="w-full flex flex-col items-center justify-end" style="height: 160px;">
                                    @if($value > 0)
                                        <span class="text-[9px] text-gray-500 mb-1">{{ number_format($value / 1000000, 1) }}M</span>
                                    @endif
                                    <div class="w-full bg-emerald-400 hover:bg-emerald-500 rounded-t transition-all cursor-default" 
                                         style="height: {{ $height }}%"
                                         title="${{ number_format($value, 0, ',', '.') }}">
                                    </div>
                                </div>
                                <span class="text-[10px] text-gray-500">{{ $months[$m] }}</span>
                            </div>
                        @endfor
                    </div>
                @else
                    <div class="text-center py-8 text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <p class="text-sm">Sin ingresos registrados</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Top 5 Rubros de Gasto ── --}}
        @if(count($expenseByItem) > 0)
        <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100 mb-8">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Top 5 Rubros de Gasto
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100">
                            <th class="pb-3 font-medium">Código</th>
                            <th class="pb-3 font-medium">Rubro</th>
                            <th class="pb-3 font-medium text-right">Presupuestado</th>
                            <th class="pb-3 font-medium text-right">% del Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($expenseByItem as $item)
                            @php $itemPercent = $totalExpenseBudget > 0 ? round(($item['presupuestado'] / $totalExpenseBudget) * 100, 1) : 0; @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="py-3">
                                    <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded-md text-xs font-mono">{{ $item['code'] }}</span>
                                </td>
                                <td class="py-3 text-gray-900">{{ $item['name'] }}</td>
                                <td class="py-3 text-right font-semibold text-gray-900">${{ number_format($item['presupuestado'], 0, ',', '.') }}</td>
                                <td class="py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-16 bg-gray-100 rounded-full h-2">
                                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ min($itemPercent, 100) }}%"></div>
                                        </div>
                                        <span class="text-gray-600 text-xs w-10 text-right">{{ $itemPercent }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ── Contratos y Pagos recientes ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Contratos recientes --}}
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Contratos Recientes
                    </h3>
                    <a href="{{ route('contractual.index') }}" class="text-xs text-blue-600 hover:text-blue-700 font-medium">Ver todos →</a>
                </div>
                @if(count($recentContracts) > 0)
                    <div class="space-y-3">
                        @foreach($recentContracts as $contract)
                            <div class="p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-gray-500">#{{ $contract['number'] }}</span>
                                            @php
                                                $statusColors = match($contract['status']) {
                                                    'active', 'in_execution' => 'bg-emerald-50 text-emerald-700',
                                                    'completed' => 'bg-blue-50 text-blue-700',
                                                    'draft' => 'bg-gray-100 text-gray-600',
                                                    'annulled', 'suspended' => 'bg-red-50 text-red-700',
                                                    default => 'bg-gray-100 text-gray-600',
                                                };
                                                $statusLabels = match($contract['status']) {
                                                    'draft' => 'Borrador',
                                                    'active' => 'Activo',
                                                    'in_execution' => 'En ejecución',
                                                    'completed' => 'Completado',
                                                    'annulled' => 'Anulado',
                                                    'suspended' => 'Suspendido',
                                                    default => $contract['status'],
                                                };
                                            @endphp
                                            <span class="text-[10px] px-2 py-0.5 rounded-full font-medium {{ $statusColors }}">{{ $statusLabels }}</span>
                                        </div>
                                        <p class="text-sm text-gray-900 font-medium mt-1 truncate">{{ $contract['supplier'] }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ $contract['object'] }}</p>
                                    </div>
                                    <p class="text-sm font-bold text-gray-900 ml-3">${{ number_format($contract['total'], 0, ',', '.') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6 text-gray-400">
                        <p class="text-sm">Sin contratos registrados</p>
                    </div>
                @endif
            </div>

            {{-- Pagos recientes --}}
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Pagos Recientes
                    </h3>
                    <a href="{{ route('postcontractual.index') }}" class="text-xs text-blue-600 hover:text-blue-700 font-medium">Ver todos →</a>
                </div>
                @if(count($recentPayments) > 0)
                    <div class="space-y-3">
                        @foreach($recentPayments as $payment)
                            <div class="p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-gray-500">#{{ $payment['number'] }}</span>
                                            @php
                                                $payStatusColors = match($payment['status']) {
                                                    'paid' => 'bg-emerald-50 text-emerald-700',
                                                    'approved' => 'bg-blue-50 text-blue-700',
                                                    'draft' => 'bg-gray-100 text-gray-600',
                                                    'cancelled' => 'bg-red-50 text-red-700',
                                                    default => 'bg-gray-100 text-gray-600',
                                                };
                                                $payStatusLabels = match($payment['status']) {
                                                    'draft' => 'Borrador',
                                                    'approved' => 'Aprobada',
                                                    'paid' => 'Pagada',
                                                    'cancelled' => 'Cancelada',
                                                    default => $payment['status'],
                                                };
                                            @endphp
                                            <span class="text-[10px] px-2 py-0.5 rounded-full font-medium {{ $payStatusColors }}">{{ $payStatusLabels }}</span>
                                            @if($payment['date'])
                                                <span class="text-[10px] text-gray-400">{{ $payment['date'] }}</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-900 font-medium mt-1 truncate">{{ $payment['supplier'] }}</p>
                                    </div>
                                    <p class="text-sm font-bold text-gray-900 ml-3">${{ number_format($payment['net_payment'], 0, ',', '.') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6 text-gray-400">
                        <p class="text-sm">Sin pagos registrados</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Info del colegio ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Contacto
                </h3>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="truncate">{{ $school->email }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span>{{ $school->phone }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>{{ $school->address }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Presupuestal
                </h3>
                <div class="space-y-2 text-sm">
                    <div class="p-2 bg-blue-50 rounded-lg">
                        <p class="text-xs text-gray-500">Acuerdo de Presupuesto</p>
                        <p class="font-semibold text-gray-900">N° {{ $school->budget_agreement_number }}</p>
                    </div>
                    @if($school->budget_approval_date)
                    <div class="p-2 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500">Fecha Aprobación</p>
                        <p class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($school->budget_approval_date)->format('d/m/Y') }}</p>
                    </div>
                    @endif
                    <div class="p-2 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500">Código DANE</p>
                        <p class="font-semibold text-gray-900">{{ $school->dane_code }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Resumen Ejecutivo
                </h3>
                @php
                    $totalUsed = $totalExpenseExecuted + $totalExpenseCommitted;
                    $usedPercent = $totalExpenseBudget > 0 ? round(($totalUsed / $totalExpenseBudget) * 100, 1) : 0;
                @endphp
                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-500">Ejecutado + Comprometido</span>
                            <span class="font-bold text-gray-700">{{ $usedPercent }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-4 overflow-hidden">
                            @php
                                $execWidth = $totalExpenseBudget > 0 ? min(($totalExpenseExecuted / $totalExpenseBudget) * 100, 100) : 0;
                                $commitWidth = $totalExpenseBudget > 0 ? min(($totalExpenseCommitted / $totalExpenseBudget) * 100, 100 - $execWidth) : 0;
                            @endphp
                            <div class="h-4 flex">
                                <div class="bg-blue-500 h-4" style="width: {{ $execWidth }}%"></div>
                                <div class="bg-amber-400 h-4" style="width: {{ $commitWidth }}%"></div>
                            </div>
                        </div>
                        <div class="flex gap-4 mt-2 text-[10px]">
                            <span class="flex items-center gap-1"><span class="w-2 h-2 bg-blue-500 rounded-full"></span> Ejecutado</span>
                            <span class="flex items-center gap-1"><span class="w-2 h-2 bg-amber-400 rounded-full"></span> Comprometido</span>
                            <span class="flex items-center gap-1"><span class="w-2 h-2 bg-gray-200 rounded-full"></span> Disponible</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-100">
                        <div class="text-center">
                            <p class="text-lg font-bold text-blue-600">{{ $totalSuppliers }}</p>
                            <p class="text-[10px] text-gray-500">Proveedores</p>
                        </div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-teal-600">{{ $totalCdps }}</p>
                            <p class="text-[10px] text-gray-500">CDPs Emitidos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @else
        {{-- ── Sin colegio seleccionado ── --}}
        <div class="flex items-center justify-center min-h-[60vh]">
            <div class="text-center max-w-2xl">
                <div class="w-24 h-24 bg-gradient-to-br from-blue-600 to-teal-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-2xl shadow-blue-500/30">
                    <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Panel de Administración</h2>
                <p class="text-gray-600 mb-8 text-lg">Selecciona un colegio para acceder a su información y estadísticas.</p>
                
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <button 
                        x-data @click="$dispatch('open-school-modal')"
                        class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white font-semibold rounded-xl shadow-xl shadow-blue-500/30 hover:shadow-2xl hover:shadow-blue-500/40 transition-all cursor-pointer transform hover:-translate-y-1"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Seleccionar Colegio
                    </button>
                </div>

                <div class="mt-12 grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-1">Total Colegios</h3>
                        <p class="text-2xl font-bold text-blue-600">{{ \App\Models\School::count() }}</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                        <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-1">Total Usuarios</h3>
                        <p class="text-2xl font-bold text-teal-600">{{ \App\Models\User::count() }}</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                        <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-1">Año Actual</h3>
                        <p class="text-2xl font-bold text-indigo-600">{{ date('Y') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

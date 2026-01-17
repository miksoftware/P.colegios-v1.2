<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Presupuesto Inicial</h1>
                <p class="text-gray-500 mt-1">Gestión del presupuesto de ingresos y gastos por fuente de financiación</p>
            </div>
            @can('budgets.create')
            <button wire:click="openCreateModal" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nueva Línea
            </button>
            @endcan
        </div>

        {{-- Resumen de Totales --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Ingresos</p>
                        <p class="text-2xl font-bold text-green-600">${{ number_format($this->totals['total_income'], 0, ',', '.') }}</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-xl">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Gastos</p>
                        <p class="text-2xl font-bold text-red-600">${{ number_format($this->totals['total_expense'], 0, ',', '.') }}</p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-xl">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Balance</p>
                        <p class="text-2xl font-bold {{ $this->totals['balance'] >= 0 ? 'text-blue-600' : 'text-orange-600' }}">${{ number_format($this->totals['balance'], 0, ',', '.') }}</p>
                    </div>
                    <div class="p-3 {{ $this->totals['balance'] >= 0 ? 'bg-blue-100' : 'bg-orange-100' }} rounded-xl">
                        @if($this->totals['balance'] == 0)
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                        <svg class="w-6 h-6 {{ $this->totals['balance'] >= 0 ? 'text-blue-600' : 'text-orange-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        @endif
                    </div>
                </div>
                @if($this->totals['balance'] != 0)
                <p class="text-xs text-orange-600 mt-2">⚠️ El presupuesto no está balanceado</p>
                @else
                <p class="text-xs text-green-600 mt-2">✓ Presupuesto balanceado</p>
                @endif
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border-gray-300" placeholder="Código, nombre o fuente...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fuente</label>
                    <select wire:model.live="filterFundingSource" class="w-full rounded-xl border-gray-300">
                        <option value="">Todas</option>
                        @foreach($allFundingSources as $source)
                        <option value="{{ $source['id'] }}">{{ $source['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
                    <select wire:model.live="filterYear" class="w-full rounded-xl border-gray-300">
                        <option value="">Todos</option>
                        @foreach($this->availableYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                    <select wire:model.live="filterStatus" class="w-full rounded-xl border-gray-300">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button wire:click="clearFilters" class="text-sm text-gray-500 hover:text-gray-700">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Limpiar filtros
                </button>
            </div>
        </div>

        {{-- Vista Agrupada con Accordion --}}
        <div class="space-y-3">
            @forelse($this->groupedBudgets as $group)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" 
                 x-data="{ expanded: false }" 
                 wire:key="budget-group-{{ $group['key'] }}">
                {{-- Header del Rubro (Clickeable para expandir) --}}
                <button @click="expanded = !expanded" 
                        class="w-full bg-gradient-to-r from-gray-50 to-white px-6 py-4 border-b flex items-center justify-between hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-blue-100 rounded-xl">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="text-left">
                            <h3 class="font-bold text-gray-900">{{ $group['budget_item']->code }} - {{ $group['budget_item']->name }}</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">
                                    {{ $group['funding_source']->code }} - {{ $group['funding_source']->name }}
                                </span>
                                <span class="text-xs text-gray-500">• Vigencia {{ $group['fiscal_year'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Monto Presupuestado</p>
                            <p class="text-xl font-bold text-gray-900">${{ number_format($group['income']?->initial_amount ?? 0, 0, ',', '.') }}</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" 
                             :class="{ 'rotate-180': expanded }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </button>
                
                {{-- Contenido Expandible --}}
                <div x-show="expanded" 
                     x-collapse 
                     x-cloak>
                    <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                        {{-- Columna Ingreso --}}
                        <div class="p-5">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <div class="p-1.5 bg-green-100 rounded-lg">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
                                    </div>
                                    <span class="font-semibold text-green-700">INGRESO</span>
                                </div>
                                @if($group['income'])
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $group['income']->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $group['income']->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                                @endif
                            </div>
                            
                            @if($group['income'])
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">Inicial</span>
                                    <span class="font-medium">${{ number_format($group['income']->initial_amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">Actual</span>
                                    <span class="font-bold text-lg {{ $group['income']->current_amount >= $group['income']->initial_amount ? 'text-green-600' : 'text-orange-600' }}">
                                        ${{ number_format($group['income']->current_amount, 0, ',', '.') }}
                                    </span>
                                </div>
                                @if($group['income']->modifications->count() > 0)
                                <div class="flex justify-between items-center pt-2 border-t">
                                    <span class="text-xs text-gray-500">Modificaciones</span>
                                    <button wire:click="openHistoryModal({{ $group['income']->id }})" class="px-2 py-0.5 text-xs font-medium text-blue-700 bg-blue-100 rounded-full hover:bg-blue-200">
                                        {{ $group['income']->modifications->count() }} cambios
                                    </button>
                                </div>
                                @endif
                                <div class="flex gap-1 pt-2">
                                    @can('budgets.modify')
                                    <button wire:click="openModificationModal({{ $group['income']->id }})" class="flex-1 px-3 py-1.5 text-xs font-medium text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                                        Modificar
                                    </button>
                                    @endcan
                                    @can('budgets.edit')
                                    <button wire:click="editBudget({{ $group['income']->id }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    @endcan
                                </div>
                            </div>
                            @else
                            <p class="text-sm text-gray-400 text-center py-4">Sin presupuesto de ingreso</p>
                            @endif
                        </div>
                        
                        {{-- Columna Gasto --}}
                        <div class="p-5">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <div class="p-1.5 bg-red-100 rounded-lg">
                                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>
                                    </div>
                                    <span class="font-semibold text-red-700">GASTO</span>
                                </div>
                                @if($group['expense'])
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $group['expense']->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $group['expense']->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                                @endif
                            </div>
                            
                            @if($group['expense'])
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">Inicial</span>
                                    <span class="font-medium">${{ number_format($group['expense']->initial_amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">Actual</span>
                                    <span class="font-bold text-lg {{ $group['expense']->current_amount >= $group['expense']->initial_amount ? 'text-green-600' : 'text-orange-600' }}">
                                        ${{ number_format($group['expense']->current_amount, 0, ',', '.') }}
                                    </span>
                                </div>
                                @if($group['expense']->modifications->count() > 0)
                                <div class="flex justify-between items-center pt-2 border-t">
                                    <span class="text-xs text-gray-500">Modificaciones</span>
                                    <button wire:click="openHistoryModal({{ $group['expense']->id }})" class="px-2 py-0.5 text-xs font-medium text-blue-700 bg-blue-100 rounded-full hover:bg-blue-200">
                                        {{ $group['expense']->modifications->count() }} cambios
                                    </button>
                                </div>
                                @endif
                                <div class="flex gap-1 pt-2">
                                    @can('budgets.modify')
                                    <button wire:click="openModificationModal({{ $group['expense']->id }})" class="flex-1 px-3 py-1.5 text-xs font-medium text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                                        Modificar
                                    </button>
                                    @endcan
                                    @can('budgets.edit')
                                    <button wire:click="editBudget({{ $group['expense']->id }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    @endcan
                                </div>
                            </div>
                            @else
                            <p class="text-sm text-gray-400 text-center py-4">Sin presupuesto de gasto</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <p class="mt-2 text-gray-500">No se encontraron presupuestos</p>
                <p class="text-sm text-gray-400">Crea tu primer presupuesto para comenzar</p>
            </div>
            @endforelse
        </div>

        {{-- Paginación --}}
        @if($this->groupedBudgets->hasPages())
        <div class="mt-6">
            {{ $this->groupedBudgets->links() }}
        </div>
        @endif
    </div>


    {{-- Modal Crear/Editar Presupuesto --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto"
         x-data="{
            useMultiple: @entangle('useMultipleSources'),
            totalBudget: @entangle('initial_amount'),
            sourceAmounts: @entangle('fundingSourceAmounts'),
            
            get totalAssigned() {
                if (!this.sourceAmounts || typeof this.sourceAmounts !== 'object') return 0;
                return Object.values(this.sourceAmounts).reduce((sum, val) => {
                    const num = parseFloat(val) || 0;
                    return sum + num;
                }, 0);
            },
            
            get remaining() {
                const budget = parseFloat(this.totalBudget) || 0;
                return budget - this.totalAssigned;
            },
            
            get isBalanced() {
                const budget = parseFloat(this.totalBudget) || 0;
                return budget > 0 && Math.abs(this.remaining) < 0.01;
            },
            
            get hasExcess() {
                return this.remaining < -0.01;
            },
            
            get hasBudget() {
                return parseFloat(this.totalBudget) > 0;
            },
            
            formatNumber(num) {
                return new Intl.NumberFormat('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
            }
         }">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-lg">
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white">{{ $isEditing ? 'Editar Presupuesto' : 'Nueva Línea Presupuestal' }}</h3>
                            <p class="text-blue-100 text-sm mt-1">Presupuesto por rubro y fuente de financiación</p>
                        </div>
                        <button type="button" wire:click="closeModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <form wire:submit="save" class="px-6 py-5 space-y-5">
                    {{-- Rubro Presupuestal --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rubro Presupuestal *</label>
                        <x-searchable-select
                            wire:model.live="budget_item_id"
                            :options="$budgetItems"
                            placeholder="Seleccione un rubro..."
                            searchPlaceholder="Buscar por código o nombre..."
                        />
                        @error('budget_item_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Monto y Año (PRIMERO) --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto Presupuestado *</label>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">$</span>
                                <input type="number" 
                                       x-model="totalBudget"
                                       step="0.01" 
                                       min="0" 
                                       class="flex-1 rounded-r-xl border-gray-300" 
                                       placeholder="0.00">
                            </div>
                            @error('initial_amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Año Fiscal *</label>
                            <input type="number" wire:model="fiscal_year" min="2020" max="2100" class="w-full rounded-xl border-gray-300">
                            @error('fiscal_year') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Fuente de Financiación (solo si hay rubro seleccionado) --}}
                    @if($budget_item_id && count($fundingSources) > 0)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fuente de Financiación *</label>
                        @if(!$isEditing && count($fundingSources) > 1)
                        <div class="mb-3">
                            <label class="inline-flex items-center">
                                <input type="checkbox" x-model="useMultiple" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-600">Distribuir en múltiples fuentes</span>
                            </label>
                        </div>
                        @endif

                        <template x-if="useMultiple && !{{ $isEditing ? 'true' : 'false' }}">
                            {{-- Múltiples fuentes con validación en tiempo real --}}
                            <div>
                                {{-- Alerta si no hay presupuesto definido --}}
                                <template x-if="!hasBudget">
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-3 mb-3">
                                        <p class="text-sm text-yellow-700">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            Ingrese primero el monto presupuestado
                                        </p>
                                    </div>
                                </template>

                                <div class="space-y-3 bg-gray-50 rounded-xl p-4" :class="{ 'opacity-50': !hasBudget }">
                                    @foreach($fundingSources as $source)
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1">
                                            <span class="text-sm font-medium text-gray-700">{{ $source['full_name'] }}</span>
                                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $source['type'] === 'sgp' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">{{ $source['type_name'] }}</span>
                                        </div>
                                        <div class="w-40">
                                            <input type="number" 
                                                   x-model="sourceAmounts['{{ $source['id'] }}']"
                                                   :disabled="!hasBudget"
                                                   step="0.01" 
                                                   min="0" 
                                                   class="w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-200 disabled:cursor-not-allowed" 
                                                   :class="{ 'border-red-300 bg-red-50': hasExcess }"
                                                   placeholder="0.00">
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                
                                {{-- Resumen en tiempo real --}}
                                <template x-if="hasBudget">
                                    <div class="mt-3 rounded-xl p-4 border transition-all duration-200"
                                         :class="isBalanced ? 'bg-green-50 border-green-300' : (hasExcess ? 'bg-red-50 border-red-300' : 'bg-blue-50 border-blue-200')">
                                        <div class="flex items-center gap-2 mb-2">
                                            <template x-if="isBalanced">
                                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </template>
                                            <template x-if="hasExcess">
                                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </template>
                                            <template x-if="!isBalanced && !hasExcess">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </template>
                                            <span class="text-sm font-semibold" :class="isBalanced ? 'text-green-700' : (hasExcess ? 'text-red-700' : 'text-blue-700')">
                                                <span x-text="isBalanced ? '✓ Distribución completa' : (hasExcess ? '✗ Excede el presupuesto' : 'Distribución pendiente')"></span>
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2 text-sm">
                                            <div class="text-center p-2 bg-white/50 rounded-lg">
                                                <p class="text-gray-500 text-xs">Presupuesto</p>
                                                <p class="font-bold text-gray-900">$<span x-text="formatNumber(parseFloat(totalBudget) || 0)"></span></p>
                                            </div>
                                            <div class="text-center p-2 bg-white/50 rounded-lg">
                                                <p class="text-gray-500 text-xs">Asignado</p>
                                                <p class="font-bold" :class="hasExcess ? 'text-red-600' : 'text-gray-900'">$<span x-text="formatNumber(totalAssigned)"></span></p>
                                            </div>
                                            <div class="text-center p-2 bg-white/50 rounded-lg">
                                                <p class="text-gray-500 text-xs">Disponible</p>
                                                <p class="font-bold" :class="remaining < 0 ? 'text-red-600' : (remaining == 0 ? 'text-green-600' : 'text-orange-600')">
                                                    $<span x-text="formatNumber(Math.abs(remaining))"></span>
                                                    <span x-show="remaining < 0" class="text-xs">(exceso)</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                
                                @error('fundingSourceAmounts') <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> @enderror
                            </div>
                        </template>

                        <template x-if="!useMultiple || {{ $isEditing ? 'true' : 'false' }}">
                            {{-- Una sola fuente --}}
                            <div>
                                <select wire:model="selectedFundingSourceId" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Seleccione una fuente...</option>
                                    @foreach($fundingSources as $source)
                                    <option value="{{ $source['id'] }}">{{ $source['full_name'] }} ({{ $source['type_name'] }})</option>
                                    @endforeach
                                </select>
                                @error('selectedFundingSourceId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </template>
                    </div>
                    @elseif($budget_item_id && count($fundingSources) == 0)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                        <p class="text-sm text-yellow-700">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            No hay fuentes de financiación activas para este rubro. <a href="{{ route('funding-sources.index') }}" class="underline font-medium">Crear una fuente</a>
                        </p>
                    </div>
                    @endif

                    {{-- Descripción --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea wire:model="description" rows="2" class="w-full rounded-xl border-gray-300" placeholder="Descripción opcional..."></textarea>
                    </div>

                    {{-- Nota informativa --}}
                    @if(!$isEditing)
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                        <p class="text-xs text-gray-500">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Se crearán automáticamente dos líneas presupuestales: una de <strong>Ingreso</strong> y una de <strong>Gasto</strong> con el mismo monto.
                        </p>
                    </div>
                    @endif

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium">{{ $isEditing ? 'Actualizar' : 'Crear' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif


    {{-- Modal Modificación --}}
    @if($showModificationModal && $modificationBudget)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModificationModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-lg">
                <div class="bg-gradient-to-r from-purple-600 to-purple-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white">Modificar Presupuesto</h3>
                            <p class="text-purple-100 text-sm mt-1">Adición o reducción</p>
                        </div>
                        <button type="button" wire:click="closeModificationModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <form wire:submit="saveModification" class="px-6 py-5 space-y-5">
                    <div class="bg-purple-50 rounded-xl p-4 border border-purple-100">
                        <p class="text-sm text-gray-600">{{ $modificationBudget->budgetItem->code }} - {{ $modificationBudget->budgetItem->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">Fuente: {{ $modificationBudget->fundingSource->name ?? 'N/A' }}</p>
                        <p class="text-xl font-bold text-gray-900 mt-2">Saldo actual: ${{ number_format($modificationBudget->current_amount, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Modificación *</label>
                        <div class="flex gap-4">
                            <label class="inline-flex items-center"><input type="radio" wire:model="modification_type" value="addition" class="text-green-600"><span class="ml-2 text-sm">Adición</span></label>
                            <label class="inline-flex items-center"><input type="radio" wire:model="modification_type" value="reduction" class="text-orange-600"><span class="ml-2 text-sm">Reducción</span></label>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                            <input type="number" wire:model="modification_amount" step="0.01" min="0.01" class="w-full rounded-xl border-gray-300" placeholder="0.00">
                            @error('modification_amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nº Documento</label>
                            <input type="text" wire:model="modification_document_number" class="w-full rounded-xl border-gray-300" placeholder="Ej: MOD-001">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Razón / Justificación *</label>
                        <textarea wire:model="modification_reason" rows="3" class="w-full rounded-xl border-gray-300" placeholder="Describa la razón de esta modificación..."></textarea>
                        @error('modification_reason') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" wire:click="closeModificationModal" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-medium">Registrar Modificación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Historial --}}
    @if($showHistoryModal && $historyBudget)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeHistoryModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-3xl">
                <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white">Historial de Modificaciones</h3>
                            <p class="text-blue-100 text-sm mt-1">{{ $historyBudget->budgetItem->code }} - {{ $historyBudget->budgetItem->name }}</p>
                        </div>
                        <button type="button" wire:click="closeHistoryModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <div class="bg-gray-50 rounded-xl p-4 mb-4">
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div><span class="text-gray-500">Inicial:</span> <span class="font-semibold">${{ number_format($historyBudget->initial_amount, 2, ',', '.') }}</span></div>
                            <div><span class="text-gray-500">Actual:</span> <span class="font-semibold text-blue-600">${{ number_format($historyBudget->current_amount, 2, ',', '.') }}</span></div>
                            <div><span class="text-gray-500">Diferencia:</span> @php $diff = $historyBudget->current_amount - $historyBudget->initial_amount; @endphp<span class="font-semibold {{ $diff >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $diff >= 0 ? '+' : '' }}${{ number_format($diff, 2, ',', '.') }}</span></div>
                        </div>
                    </div>
                    @if($historyBudget->modifications->count() > 0)
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">#</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Tipo</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Monto</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">Nuevo Saldo</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Razón</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($historyBudget->modifications as $mod)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $mod->formatted_number }}</td>
                                <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $mod->type_color }}">{{ $mod->type_name }}</span></td>
                                <td class="px-4 py-3 text-right {{ $mod->type === 'addition' ? 'text-green-600' : 'text-orange-600' }}">{{ $mod->type === 'addition' ? '+' : '-' }}${{ number_format($mod->amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-medium">${{ number_format($mod->new_amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 max-w-xs truncate" title="{{ $mod->reason }}">{{ Str::limit($mod->reason, 35) }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $mod->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="text-center text-gray-500 py-8">No hay modificaciones registradas</p>
                    @endif
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button type="button" wire:click="closeHistoryModal" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-xl font-medium">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Eliminar --}}
    @if($showDeleteModal && $itemToDelete)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeDeleteModal"></div>
            <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-md">
                <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white">Eliminar Presupuesto</h3>
                        <button type="button" wire:click="closeDeleteModal" class="text-white/80 hover:text-white p-2 hover:bg-white/10 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <p class="text-gray-600">¿Está seguro de eliminar el presupuesto del rubro <strong class="text-gray-900">{{ $itemToDelete->budgetItem->name }}</strong>?</p>
                    <p class="text-sm text-gray-500 mt-2">Esta acción eliminará todas las modificaciones asociadas.</p>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeDeleteModal" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-xl font-medium">Cancelar</button>
                    <button type="button" wire:click="deleteBudget" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

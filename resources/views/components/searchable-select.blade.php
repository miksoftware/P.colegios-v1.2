@props([
    'options' => [],
    'value' => '',
    'placeholder' => 'Seleccione...',
    'searchPlaceholder' => 'Buscar...',
    'displayKey' => 'name',
    'valueKey' => 'id',
    'disabled' => false,
])

<div 
    x-data="{
        open: false,
        search: '',
        selected: @entangle($attributes->wire('model')),
        options: {{ json_encode($options) }},
        displayKey: '{{ $displayKey }}',
        valueKey: '{{ $valueKey }}',
        get filteredOptions() {
            if (!this.search) return this.options;
            const searchLower = this.search.toLowerCase();
            return this.options.filter(opt => {
                const display = typeof opt === 'object' ? opt[this.displayKey] : opt;
                return display.toLowerCase().includes(searchLower);
            });
        },
        get selectedOption() {
            if (!this.selected) return null;
            return this.options.find(opt => {
                const val = typeof opt === 'object' ? opt[this.valueKey] : opt;
                return val == this.selected;
            });
        },
        get displayText() {
            if (!this.selectedOption) return '{{ $placeholder }}';
            return typeof this.selectedOption === 'object' 
                ? this.selectedOption[this.displayKey] 
                : this.selectedOption;
        },
        selectOption(opt) {
            this.selected = typeof opt === 'object' ? opt[this.valueKey] : opt;
            this.open = false;
            this.search = '';
        },
        getOptionValue(opt) {
            return typeof opt === 'object' ? opt[this.valueKey] : opt;
        },
        getOptionDisplay(opt) {
            return typeof opt === 'object' ? opt[this.displayKey] : opt;
        }
    }"
    @click.away="open = false"
    class="relative"
>
    <!-- Selected Display -->
    <button
        type="button"
        @click="open = !open"
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => 'w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white px-4 py-2.5 text-left flex items-center justify-between ' . ($disabled ? 'bg-gray-100 cursor-not-allowed' : 'cursor-pointer hover:border-gray-400')]) }}
    >
        <span 
            class="block truncate"
            :class="selected ? 'text-gray-900' : 'text-gray-500'"
            x-text="displayText"
        ></span>
        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <!-- Dropdown -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute z-50 mt-1 w-full bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden"
        style="display: none;"
    >
        <!-- Search Input -->
        <div class="p-2 border-b border-gray-100">
            <div class="relative">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    x-model="search"
                    @click.stop
                    class="w-full pl-10 pr-4 py-2 text-sm border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                    placeholder="{{ $searchPlaceholder }}"
                >
            </div>
        </div>

        <!-- Options List -->
        <ul class="max-h-60 overflow-y-auto py-1">
            <template x-if="filteredOptions.length === 0">
                <li class="px-4 py-3 text-sm text-gray-500 text-center">
                    No se encontraron resultados
                </li>
            </template>
            <template x-for="option in filteredOptions" :key="getOptionValue(option)">
                <li
                    @click="selectOption(option)"
                    class="px-4 py-2.5 text-sm cursor-pointer hover:bg-blue-50 hover:text-blue-600 transition-colors flex items-center justify-between"
                    :class="{'bg-blue-50 text-blue-600': selected == getOptionValue(option)}"
                >
                    <span x-text="getOptionDisplay(option)" class="truncate"></span>
                    <svg x-show="selected == getOptionValue(option)" class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </li>
            </template>
        </ul>
    </div>
</div>

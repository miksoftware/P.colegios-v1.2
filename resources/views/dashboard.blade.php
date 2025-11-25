<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @php
                        $selectedSchool = \App\Models\School::find(session('selected_school_id'));
                    @endphp
                    
                    @if($selectedSchool)
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-2xl font-bold text-white">{{ $selectedSchool->name }}</h3>
                                <p class="text-gray-400 mt-1">Bienvenido al sistema de presupuesto escolar</p>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                                <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                                    <p class="text-sm text-gray-400">NIT</p>
                                    <p class="text-white font-semibold">{{ $selectedSchool->nit }}</p>
                                </div>
                                <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                                    <p class="text-sm text-gray-400">Municipio</p>
                                    <p class="text-white font-semibold">{{ $selectedSchool->municipality }}</p>
                                </div>
                                <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                                    <p class="text-sm text-gray-400">Rector(a)</p>
                                    <p class="text-white font-semibold">{{ $selectedSchool->rector_name }}</p>
                                </div>
                                <div class="bg-gray-900 rounded-lg p-4 border border-gray-700">
                                    <p class="text-sm text-gray-400">Vigencia Actual</p>
                                    <p class="text-white font-semibold">{{ $selectedSchool->current_validity }}</p>
                                </div>
                            </div>
                            
                            @if(auth()->user()->is_admin)
                                <div class="mt-6">
                                    <a href="{{ route('school.select') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                        Cambiar de Colegio
                                    </a>
                                </div>
                            @endif
                        </div>
                    @else
                        <p>{{ __("You're logged in!") }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Dashboard
        </h2>
    </x-slot>

    @php
        $selectedSchool = \App\Models\School::find(session('selected_school_id'));
    @endphp

    @if($selectedSchool)
        <!-- Welcome Section -->
        <div class="mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-blue-500 rounded-2xl shadow-xl shadow-blue-500/20 p-8 text-white">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">{{ $selectedSchool->name }}</h1>
                        <p class="text-blue-100 text-lg">Bienvenido al sistema de presupuesto escolar</p>
                    </div>
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('school.select') }}" class="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            Cambiar Colegio
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- NIT Card -->
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-1">NIT</p>
                <p class="text-2xl font-bold text-gray-900">{{ $selectedSchool->nit }}</p>
            </div>

            <!-- Municipality Card -->
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-1">Municipio</p>
                <p class="text-2xl font-bold text-gray-900">{{ $selectedSchool->municipality }}</p>
            </div>

            <!-- Rector Card -->
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-1">Rector(a)</p>
                <p class="text-lg font-bold text-gray-900">{{ $selectedSchool->rector_name }}</p>
            </div>

            <!-- Year Card -->
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-1">Vigencia Actual</p>
                <p class="text-2xl font-bold text-gray-900">{{ $selectedSchool->current_validity }}</p>
            </div>
        </div>

        <!-- Additional Info Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Contact Info -->
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Información de Contacto
                </h3>
                <div class="space-y-3">
                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500">Email</p>
                            <p class="text-sm font-medium text-gray-900">{{ $selectedSchool->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500">Teléfono</p>
                            <p class="text-sm font-medium text-gray-900">{{ $selectedSchool->phone }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-xl">
                        <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500">Dirección</p>
                            <p class="text-sm font-medium text-gray-900">{{ $selectedSchool->address }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget Info -->
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Información Presupuestal
                </h3>
                <div class="space-y-3">
                    <div class="p-3 bg-gradient-to-r from-blue-50 to-teal-50 rounded-xl border border-blue-100">
                        <p class="text-xs text-gray-600 mb-1">Acuerdo de Presupuesto</p>
                        <p class="text-sm font-semibold text-gray-900">N° {{ $selectedSchool->budget_agreement_number }}</p>
                        <p class="text-xs text-gray-500 mt-1">Aprobado: {{ \Carbon\Carbon::parse($selectedSchool->budget_approval_date)->format('d/m/Y') }}</p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-xl">
                        <p class="text-xs text-gray-600 mb-1">Código DANE</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $selectedSchool->dane_code }}</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay colegio seleccionado</h3>
            <p class="text-gray-500">Por favor, selecciona un colegio para continuar.</p>
        </div>
    @endif
</x-app-layout>

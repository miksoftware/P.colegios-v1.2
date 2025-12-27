<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        
        <style>
            body {
                font-family: 'Inter', sans-serif;
            }
            .sidebar-blur {
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            }
        </style>
    </head>
    <body class="antialiased bg-gradient-to-br from-blue-50 via-gray-50 to-white">
        <div class="min-h-screen">
            <!-- Sidebar -->
            <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white/80 sidebar-blur border-r border-gray-200 transform transition-transform duration-300 ease-in-out lg:translate-x-0 -translate-x-full">
                <div class="flex flex-col h-full">
                    <!-- Logo -->
                    <div class="flex items-center gap-3 px-6 py-6 border-b border-gray-200">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-500 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-6 h-6 object-contain">
                        </div>
                        <div>
                            <h1 class="text-sm font-bold text-gray-900">Presupuesto</h1>
                            <p class="text-xs text-gray-500">Escolar</p>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto" x-data="{ registroOpen: {{ request()->routeIs('school.manage') || request()->routeIs('school.info') || request()->routeIs('users.index') || request()->routeIs('roles.index') || request()->routeIs('accounting.accounts') || request()->routeIs('activity.logs') || request()->routeIs('suppliers.index') ? 'true' : 'false' }} }">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold {{ request()->routeIs('dashboard') ? 'text-white bg-gradient-to-r from-blue-600 to-blue-500 rounded-xl shadow-lg shadow-blue-500/30' : 'text-gray-700 hover:bg-gray-100 rounded-xl' }} transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Dashboard
                        </a>
                        
                        <!-- Registro de Información - Expandable -->
                        <div>
                            <button 
                                @click="registroOpen = !registroOpen"
                                class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium {{ request()->routeIs('school.manage') || request()->routeIs('school.info') || request()->routeIs('users.index') || request()->routeIs('roles.index') || request()->routeIs('accounting.accounts') || request()->routeIs('activity.logs') || request()->routeIs('suppliers.index') ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-100' }} rounded-xl transition-all"
                            >
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span>Registro de Información</span>
                                </div>
                                <svg 
                                    class="w-4 h-4 transition-transform duration-200"
                                    :class="{'rotate-180': registroOpen}"
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            
                            <!-- Submenu -->
                            <div 
                                x-show="registroOpen" 
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform -translate-y-2"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 transform translate-y-0"
                                x-transition:leave-end="opacity-0 transform -translate-y-2"
                                class="mt-1 ml-4 space-y-1"
                            >
                                @can('users.view')
                                    <a href="{{ route('users.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('users.index') ? 'bg-blue-50 text-blue-600 font-medium' : '' }}">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        Usuarios
                                    </a>
                                @endcan

                                {{-- Admin: Abre modal de selección de colegios --}}
                                @role('Admin')
                                    <button 
                                        @click="$dispatch('open-school-modal')" 
                                        class="w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors"
                                    >
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        Colegios
                                    </button>
                                @else
                                    {{-- Usuario normal: Va a la vista de su colegio --}}
                                    @can('school_info.view')
                                        <a href="{{ route('school.info') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('school.info') ? 'bg-blue-50 text-blue-600 font-medium' : '' }}">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                            Mi Colegio
                                        </a>
                                    @endcan
                                @endrole

                                @can('roles.view')
                                    <a href="{{ route('roles.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('roles.index') ? 'bg-blue-50 text-blue-600 font-medium' : '' }}">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                        Roles y Permisos
                                    </a>
                                @endcan

                                @can('accounting_accounts.view')
                                    <a href="{{ route('accounting.accounts') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('accounting.accounts') ? 'bg-blue-50 text-blue-600 font-medium' : '' }}">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        Cuentas Contables
                                    </a>
                                @endcan

                                @can('activity_logs.view')
                                    <a href="{{ route('activity.logs') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('activity.logs') ? 'bg-blue-50 text-blue-600 font-medium' : '' }}">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Registro de Actividad
                                    </a>
                                @endcan

                                @can('suppliers.view')
                                    <a href="{{ route('suppliers.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('suppliers.index') ? 'bg-blue-50 text-blue-600 font-medium' : '' }}">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                        </svg>
                                        Proveedores
                                    </a>
                                @endcan
                            </div>
                        </div>
                        
                        <!-- Presupuesto - Expandable -->
                        <div x-data="{ presupuestoOpen: {{ request()->routeIs('budget.items') || request()->routeIs('budgets.index') || request()->routeIs('funding-sources.index') || request()->routeIs('budget-transfers.index') ? 'true' : 'false' }} }">
                            <button 
                                @click="presupuestoOpen = !presupuestoOpen"
                                class="w-full flex items-center justify-between gap-3 px-4 py-3 text-sm font-medium {{ request()->routeIs('budget.items') || request()->routeIs('budgets.index') || request()->routeIs('funding-sources.index') || request()->routeIs('budget-transfers.index') ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-100' }} rounded-xl transition-all"
                            >
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Presupuesto</span>
                                </div>
                                <svg 
                                    class="w-4 h-4 transition-transform duration-200"
                                    :class="{'rotate-180': presupuestoOpen}"
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            
                            <!-- Submenu Presupuesto -->
                            <div 
                                x-show="presupuestoOpen" 
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform -translate-y-2"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 transform translate-y-0"
                                x-transition:leave-end="opacity-0 transform -translate-y-2"
                                class="mt-1 ml-4 space-y-1"
                            >
                                @can('budget_items.view')
                                    <a href="{{ route('budget.items') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('budget.items') ? 'bg-blue-50 text-blue-600 font-medium' : '' }}">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        Rubros
                                    </a>
                                @endcan
                                @can('budgets.view')
                                    <a href="{{ route('budgets.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('budgets.index') ? 'bg-blue-50 text-blue-600 font-medium' : '' }}">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Presupuesto Inicial
                                    </a>
                                @endcan
                                @can('funding_sources.view')
                                    <a href="{{ route('funding-sources.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('funding-sources.index') ? 'bg-blue-50 text-blue-600 font-medium' : '' }}">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        Fuentes de Financiación
                                    </a>
                                @endcan
                                @can('budget_transfers.view')
                                    <a href="{{ route('budget-transfers.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('budget-transfers.index') ? 'bg-blue-50 text-blue-600 font-medium' : '' }}">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                        </svg>
                                        Créditos y Contracréditos
                                    </a>
                                @endcan
                            </div>
                        </div>
                        
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-xl transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Documentos
                        </a>
                        
                        <a href="#" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-xl transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Reportes
                        </a>
                    </nav>

                    <!-- User Profile -->
                    <div class="px-4 py-4 border-t border-gray-200">
                        <livewire:layout.navigation-logout />
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="lg:pl-64">
                <!-- Top Header -->
                <header class="sticky top-0 z-40 bg-white/80 sidebar-blur border-b border-gray-200">
                    <div class="px-4 sm:px-6 lg:px-8 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <button id="sidebar-toggle" class="lg:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                    </svg>
                                </button>
                                @if (isset($header))
                                    {{ $header }}
                                @endif
                            </div>
                            
                            <div class="flex items-center gap-3">
                                @php
                                    $selectedSchool = \App\Models\School::find(session('selected_school_id'));
                                @endphp
                                @if($selectedSchool)
                                    <div class="hidden sm:flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-50 to-teal-50 rounded-xl border border-blue-100">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        <span class="text-sm font-semibold text-gray-900">{{ $selectedSchool->name }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Sidebar Toggle Script -->
        <script>
            document.getElementById('sidebar-toggle')?.addEventListener('click', function() {
                const sidebar = document.getElementById('sidebar');
                sidebar.classList.toggle('-translate-x-full');
            });
        </script>
        @livewireScripts
        <x-toast-notification />

        {{-- Modal de selección de colegios para Admin --}}
        @role('Admin')
            <livewire:school-select />
        @endrole

        <!-- Global Logout Loading Overlay -->
        <div 
            x-data="{ loggingOut: false }" 
            @logout-started.window="loggingOut = true"
            x-show="loggingOut"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm"
            style="display: none;"
        >
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <div class="flex flex-col items-center">
                    <div class="relative w-16 h-16 mb-4">
                        <div class="absolute inset-0 border-4 border-blue-200 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-transparent border-t-blue-600 rounded-full animate-spin"></div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Cerrando Sesión...</h3>
                    <p class="text-sm text-gray-500 mt-2">Hasta pronto</p>
                </div>
            </div>
        </div>
    </body>
</html>

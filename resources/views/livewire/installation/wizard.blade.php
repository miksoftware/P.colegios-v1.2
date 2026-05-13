<div class="bg-white rounded-3xl shadow-2xl overflow-hidden">

    {{-- Header --}}
    <div class="bg-gradient-to-r from-blue-700 to-blue-500 px-8 py-6 text-white">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold">Asistente de Instalación</h1>
                <p class="text-blue-100 text-sm">Sistema de Gestión Escolar</p>
            </div>
        </div>

        {{-- Step indicators --}}
        <div class="flex items-center gap-1">
            @foreach ($steps as $step => $label)
                <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                    <div class="flex items-center gap-1.5">
                        <div @class([
                            'w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all',
                            'bg-white text-blue-600' => $currentStep === $step,
                            'bg-green-400 text-white' => $currentStep > $step,
                            'bg-white/30 text-white/70' => $currentStep < $step,
                        ])>
                            @if ($currentStep > $step)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $step }}
                            @endif
                        </div>
                        <span @class([
                            'text-xs font-medium hidden sm:block',
                            'text-white' => $currentStep === $step,
                            'text-green-300' => $currentStep > $step,
                            'text-white/60' => $currentStep < $step,
                        ])>{{ $label }}</span>
                    </div>
                    @if (!$loop->last)
                        <div @class([
                            'flex-1 h-px mx-2',
                            'bg-green-400' => $currentStep > $step,
                            'bg-white/30' => $currentStep <= $step,
                        ])></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Body --}}
    <div class="p-8">

        {{-- ════════════ STEP 1 – DATABASE ════════════ --}}
        @if ($currentStep === 1)
            <h2 class="text-lg font-bold text-gray-900 mb-1">Configuración de Base de Datos</h2>
            <p class="text-sm text-gray-500 mb-6">Ingresa los datos de conexión a tu base de datos MySQL/MariaDB.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Host</label>
                    <input wire:model="db_host" type="text" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="127.0.0.1">
                    @error('db_host') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Puerto</label>
                    <input wire:model="db_port" type="number" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="3306">
                    @error('db_port') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre de la Base de Datos</label>
                    <input wire:model="db_database" type="text" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="colegios_db">
                    @error('db_database') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Usuario</label>
                    <input wire:model="db_username" type="text" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="root">
                    @error('db_username') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Contraseña de BD</label>
                    <input wire:model="db_password" type="password" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="••••••••">
                </div>
            </div>

            {{-- Connection test result --}}
            @if ($connectionTested === true)
                <div class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-xl px-4 py-3 mb-4">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-green-700 font-medium">{{ $connectionMessage }}</p>
                </div>
            @elseif ($connectionTested === false)
                <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-4">
                    <p class="text-sm text-red-700 font-medium break-words">{{ $connectionMessage }}</p>
                </div>
            @endif

            @error('db_connection')
                <p class="text-red-500 text-sm mb-4">{{ $message }}</p>
            @enderror

            <div class="flex gap-3">
                <button wire:click="testConnection" wire:loading.attr="disabled"
                    class="flex items-center gap-2 px-5 py-2.5 border-2 border-blue-500 text-blue-600 rounded-xl text-sm font-semibold hover:bg-blue-50 transition-colors disabled:opacity-50">
                    <span wire:loading.remove wire:target="testConnection">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </span>
                    <span wire:loading wire:target="testConnection">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="testConnection">Probar Conexión</span>
                    <span wire:loading wire:target="testConnection">Probando...</span>
                </button>

                <button wire:click="saveDbConfig" wire:loading.attr="disabled"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-2.5 text-sm font-semibold transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="saveDbConfig">Guardar y Continuar →</span>
                    <span wire:loading wire:target="saveDbConfig" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Guardando...
                    </span>
                </button>
            </div>
        @endif

        {{-- ════════════ STEP 2 – MIGRATIONS ════════════ --}}
        @if ($currentStep === 2)
            <h2 class="text-lg font-bold text-gray-900 mb-1">Estructura de la Base de Datos</h2>
            <p class="text-sm text-gray-500 mb-6">Ejecuta las migraciones para crear todas las tablas necesarias.</p>

            @if (!$migrationsRun)
                <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-4 mb-6">
                    <p class="text-sm text-blue-700">Esto creará todas las tablas de la base de datos. Si ya existen, se actualizarán de forma segura.</p>
                </div>

                <button wire:click="runMigrations" wire:loading.attr="disabled"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-3 font-semibold text-sm transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="runMigrations">
                        <svg class="w-5 h-5 inline -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582 4-8 4"/>
                        </svg>
                        Ejecutar Migraciones
                    </span>
                    <span wire:loading wire:target="runMigrations" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Ejecutando migraciones...
                    </span>
                </button>
            @else
                <div class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-xl px-4 py-3 mb-4">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-green-700 font-medium">Migraciones ejecutadas correctamente.</p>
                </div>

                @if ($migrationsOutput)
                    <details class="mb-6">
                        <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600">Ver salida</summary>
                        <pre class="mt-2 bg-gray-50 rounded-xl p-4 text-xs text-gray-600 overflow-auto max-h-40 whitespace-pre-wrap">{{ $migrationsOutput }}</pre>
                    </details>
                @endif

                <div class="flex gap-3">
                    <button wire:click="$set('currentStep', 1)" class="px-5 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-colors">
                        ← Atrás
                    </button>
                    <button wire:click="goToSeeders" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-2.5 font-semibold text-sm transition-colors">
                        Continuar →
                    </button>
                </div>
            @endif
        @endif

        {{-- ════════════ STEP 3 – SEEDERS ════════════ --}}
        @if ($currentStep === 3)
            <h2 class="text-lg font-bold text-gray-900 mb-1">Datos Iniciales del Sistema</h2>
            <p class="text-sm text-gray-500 mb-6">Carga los datos globales necesarios para el funcionamiento del sistema.</p>

            @if (empty($seederResults))
                <div class="space-y-2 mb-6">
                    @php
                        $seederLabels = [
                            'Departamentos', 'Municipios', 'Módulos y permisos base',
                            'Permisos de presupuesto', 'Permisos de rubros', 'Permisos de traslados',
                            'Permisos de modificaciones', 'Permisos de fuentes de financiación',
                            'Permisos de ingresos', 'Permisos de gastos', 'Permisos de códigos de gasto',
                            'Permisos precontractuales', 'Permisos contractuales', 'Permisos postcontractuales',
                            'Permisos de bancos', 'Permisos de reportes', 'Permisos de noticias',
                            'Permisos de retenciones', 'Permisos cuentas inventario', 'Permisos ítems inventario',
                            'Permisos entradas inventario', 'Permisos bajas inventario',
                            'Cuentas contables', 'Cuentas contables de gastos', 'Rubros y fuentes de financiación',
                            'Códigos de gasto', 'Cuentas contables de inventario', 'Configuración de retenciones',
                        ];
                    @endphp
                    @foreach ($seederLabels as $label)
                        <div class="flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-lg">
                            <div class="w-4 h-4 rounded-full bg-gray-200 shrink-0"></div>
                            <span class="text-sm text-gray-500">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>

                <button wire:click="runSeeders" wire:loading.attr="disabled"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-3 font-semibold text-sm transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="runSeeders">
                        <svg class="w-5 h-5 inline -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Cargar Datos Iniciales
                    </span>
                    <span wire:loading wire:target="runSeeders" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Cargando datos... (puede tardar unos segundos)
                    </span>
                </button>
            @else
                <div class="space-y-1.5 mb-6 max-h-72 overflow-y-auto pr-1">
                    @foreach ($seederResults as $result)
                        <div @class([
                            'flex items-start gap-2 px-4 py-2 rounded-lg',
                            'bg-green-50' => $result['status'] === 'ok',
                            'bg-red-50' => $result['status'] === 'error',
                        ])>
                            @if ($result['status'] === 'ok')
                                <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            @endif
                            <div>
                                <span @class([
                                    'text-sm font-medium',
                                    'text-green-700' => $result['status'] === 'ok',
                                    'text-red-700' => $result['status'] === 'error',
                                ])>{{ $result['label'] }}</span>
                                @if ($result['error'])
                                    <p class="text-xs text-red-600 mt-0.5 break-words">{{ $result['error'] }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex gap-3">
                    <button wire:click="$set('currentStep', 2)" class="px-5 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-colors">
                        ← Atrás
                    </button>
                    @if ($seedersRun)
                        <button wire:click="goToAdmin" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-2.5 font-semibold text-sm transition-colors">
                            Continuar →
                        </button>
                    @else
                        <button wire:click="runSeeders" wire:loading.attr="disabled"
                            class="flex-1 border-2 border-orange-400 text-orange-600 rounded-xl py-2.5 font-semibold text-sm hover:bg-orange-50 transition-colors disabled:opacity-50">
                            Reintentar
                        </button>
                    @endif
                </div>
            @endif
        @endif

        {{-- ════════════ STEP 4 – ADMIN USER ════════════ --}}
        @if ($currentStep === 4)
            <h2 class="text-lg font-bold text-gray-900 mb-1">Crear Usuario Administrador</h2>
            <p class="text-sm text-gray-500 mb-6">Este usuario tendrá acceso total al sistema y <strong>no podrá ser eliminado</strong>.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre</label>
                    <input wire:model="admin_name" type="text" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Administrador">
                    @error('admin_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Apellido</label>
                    <input wire:model="admin_surname" type="text" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="del Sistema">
                    @error('admin_surname') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Correo Electrónico</label>
                    <input wire:model="admin_email" type="email" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="admin@miempresa.com">
                    @error('admin_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Contraseña</label>
                    <input wire:model="admin_password" type="password" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Mínimo 8 caracteres">
                    @error('admin_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Confirmar Contraseña</label>
                    <input wire:model="admin_password_confirmation" type="password" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Repite la contraseña">
                    @error('admin_password_confirmation') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-6 flex gap-2">
                <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-sm text-amber-700">Guarda las credenciales en un lugar seguro. Este usuario no podrá ser eliminado del sistema.</p>
            </div>

            <div class="flex gap-3">
                <button wire:click="$set('currentStep', 3)" class="px-5 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-colors">
                    ← Atrás
                </button>
                <button wire:click="createAdminUser" wire:loading.attr="disabled"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-2.5 font-semibold text-sm transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="createAdminUser">Finalizar Instalación</span>
                    <span wire:loading wire:target="createAdminUser" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Creando usuario...
                    </span>
                </button>
            </div>
        @endif

        {{-- ════════════ STEP 5 – DONE ════════════ --}}
        @if ($currentStep === 5)
            <div class="text-center py-6">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">¡Instalación Completada!</h2>
                <p class="text-gray-500 mb-2">El sistema ha sido configurado exitosamente.</p>
                <p class="text-sm text-gray-400 mb-8">Ya puedes iniciar sesión con las credenciales del administrador que creaste.</p>

                <div class="bg-blue-50 border border-blue-200 rounded-xl px-6 py-4 text-left mb-8">
                    <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide mb-2">Resumen</p>
                    <div class="space-y-1 text-sm text-blue-800">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Base de datos configurada
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Tablas creadas correctamente
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Datos globales cargados
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Usuario administrador creado: <strong>{{ $admin_email }}</strong>
                        </div>
                    </div>
                </div>

                <a href="{{ route('login') }}"
                    class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-semibold text-sm transition-colors">
                    Ir al Login →
                </a>
            </div>
        @endif

    </div>

    <p class="text-center text-xs text-gray-400 pb-4">
        © {{ date('Y') }} Sistema de Gestión Escolar
    </p>
</div>

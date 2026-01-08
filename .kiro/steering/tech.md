---
inclusion: always
---

# Tech Stack

## Stack Principal

### Backend
| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| Laravel | 12.x | Framework PHP |
| PHP | 8.2+ | Lenguaje |
| MySQL | 8.x | Base de datos |
| Livewire | 3.6 | Componentes reactivos |
| Volt | - | Componentes Livewire en archivos blade |

### Frontend
| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| Tailwind CSS | 3.x | Framework CSS |
| Alpine.js | 3.x | Interactividad (via Livewire) |
| Vite | 6.x | Bundler de assets |
| Inter Font | - | Tipografía principal |

### Paquetes Clave
| Paquete | Propósito |
|---------|-----------|
| `spatie/laravel-permission` | Roles y permisos |
| `usernotnull/tall-toasts` | Notificaciones toast |
| `@tailwindcss/forms` | Estilos de formularios |
| `laravel/breeze` | Scaffolding de autenticación |

---

## Configuración de Desarrollo

### Requisitos
- PHP >= 8.2
- Composer >= 2.x
- Node.js >= 18.x
- MySQL >= 8.x

### Instalación
```bash
# Clonar repositorio
git clone [repo-url]
cd P.colegios-v1.2

# Instalar dependencias
composer install
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Configurar base de datos en .env
# DB_DATABASE=colegios
# DB_USERNAME=root
# DB_PASSWORD=

# Ejecutar migraciones y seeders
php artisan migrate --seed

# O usar comando combinado
composer run setup
```

### Comandos de Desarrollo
```bash
# Servidor de desarrollo completo (server + queue + pail + vite)
composer run dev

# Solo servidor Laravel
php artisan serve

# Solo Vite (desarrollo de assets)
npm run dev

# Build de producción
npm run build

# Limpiar cache de permisos (después de cambios)
php artisan permission:cache-reset
```

### Comandos de Testing
```bash
# Ejecutar tests
composer run test

# Con coverage
php artisan test --coverage

# Tests específicos
php artisan test --filter=TestName
```

### Calidad de Código
```bash
# Formatear código PHP
vendor/bin/pint

# Análisis estático (si instalado)
vendor/bin/phpstan analyse
```

---

## Entorno Windows

El proyecto se ejecuta en Windows con shell cmd/PowerShell. Usar `;` para separar comandos.

### Notas Importantes para Windows
```bash
# Si los cambios en vistas no aparecen, limpiar cache
php artisan view:clear

# Para correr varias tareas, usar composer scripts
composer run dev
```

---

## Componentes UI

### Searchable Select
```blade
{{-- Componente de select con búsqueda usando Alpine.js --}}
<x-searchable-select
    wire:model="campo"
    :options="$opciones"
    placeholder="Seleccionar..."
    searchPlaceholder="Buscar..."
/>

{{-- Formato de opciones --}}
@php
$opciones = [
    ['id' => 1, 'name' => 'Opción 1'],
    ['id' => 2, 'name' => 'Opción 2'],
];
@endphp
```

### Modal Inline Pattern
Para compatibilidad con Livewire, usar modales inline con `@if`:
```blade
@if($showModal)
<div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-start justify-center min-h-screen px-4 pt-4 pb-20 sm:p-0">
        {{-- Overlay --}}
        <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>
        
        {{-- Modal Content --}}
        <div class="relative bg-white rounded-2xl overflow-hidden shadow-xl sm:my-8 w-full max-w-lg">
            {{-- Header --}}
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-bold">Título del Modal</h3>
            </div>
            
            {{-- Body --}}
            <div class="p-6">
                {{-- Contenido --}}
            </div>
            
            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3">
                <button type="button" wire:click="closeModal" class="px-4 py-2 text-gray-700">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl">
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>
@endif
```

### Input Group para Moneda
```blade
<div class="flex">
    <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
        $
    </span>
    <input 
        type="number" 
        wire:model="amount"
        step="0.01"
        class="flex-1 rounded-r-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
    />
</div>
```

### Toast Notifications
```php
// En componente Livewire
$this->dispatch('toast', message: 'Mensaje exitoso', type: 'success');
$this->dispatch('toast', message: 'Error ocurrido', type: 'error');
$this->dispatch('toast', message: 'Advertencia', type: 'warning');
```

### Estilos de Componentes Comunes
```blade
{{-- Input estándar --}}
<input 
    type="text" 
    wire:model="campo"
    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
/>

{{-- Select estándar --}}
<select 
    wire:model="campo"
    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
>
    <option value="">Seleccionar...</option>
    @foreach($opciones as $opcion)
        <option value="{{ $opcion->id }}">{{ $opcion->name }}</option>
    @endforeach
</select>

{{-- Botón primario --}}
<button class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors">
    Guardar
</button>

{{-- Botón secundario --}}
<button class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl transition-colors">
    Cancelar
</button>

{{-- Badge de estado --}}
<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
    Activo
</span>

{{-- Error de validación --}}
@error('campo')
    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
@enderror
```

---

## Patrones de Componentes Livewire

### Componente Full-Page
```php
<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

class MiComponente extends Component
{
    use WithPagination;

    public $schoolId;
    public $search = '';
    public $showModal = false;
    public $isEditing = false;
    
    // Campos de formulario
    public $nombre = '';
    public $is_active = true;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    protected function rules()
    {
        return [
            'nombre' => 'required|string|max:255',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre es obligatorio.',
    ];

    public function mount()
    {
        // Verificar permisos
        abort_if(!auth()->user()->can('modulo.view'), 403);
        
        // Obtener colegio de sesión
        $this->schoolId = session('selected_school_id');
        
        // Validar colegio requerido
        if (!$this->schoolId) {
            session()->flash('error', 'Seleccione un colegio.');
            $this->redirect(route('dashboard'));
            return;
        }
    }

    // Propiedad computada para consultas
    public function getItemsProperty()
    {
        return MiModelo::forSchool($this->schoolId)
            ->when($this->search, fn($q) => $q->search($this->search))
            ->orderBy('name')
            ->paginate(15);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        if (!auth()->user()->can('modulo.create')) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }
        
        $this->resetForm();
        $this->showModal = true;
    }

    public function save()
    {
        $permission = $this->isEditing ? 'modulo.edit' : 'modulo.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'Sin permisos.', type: 'error');
            return;
        }

        $this->validate();
        
        MiModelo::create([
            'school_id' => $this->schoolId,
            'nombre' => $this->nombre,
            'is_active' => $this->is_active,
        ]);

        $this->dispatch('toast', message: 'Creado exitosamente.', type: 'success');
        $this->closeModal();
    }

    public function resetForm()
    {
        $this->nombre = '';
        $this->is_active = true;
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.mi-componente');
    }
}
```

### Vista de Componente Livewire
```blade
<div>
    {{-- Header con título y botón crear --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Título</h1>
        
        @can('modulo.create')
            <button 
                wire:click="openCreateModal"
                class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Crear Nuevo
            </button>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <input 
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Buscar..."
                    class="w-full rounded-xl border-gray-300"
                />
            </div>
        </div>
    </div>

    {{-- Tabla de resultados --}}
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nombre
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Estado
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($this->items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $item->nombre }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            @can('modulo.edit')
                                <button 
                                    wire:click="edit({{ $item->id }})"
                                    class="text-blue-600 hover:text-blue-800"
                                >
                                    Editar
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                            No hay registros.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        {{-- Paginación --}}
        <div class="px-6 py-4 border-t">
            {{ $this->items->links() }}
        </div>
    </div>

    {{-- Modal Crear/Editar --}}
    @if($showModal)
        {{-- ... modal content ... --}}
    @endif
</div>
```

---

## Creación de Nuevos Módulos

### Checklist Completo
1. ☐ **Modelo** en `app/Models/` con traits y relaciones
2. ☐ **Migración** con FKs e índices
3. ☐ **Seeder de Permisos** para el módulo
4. ☐ **Componente Livewire** en `app/Livewire/`
5. ☐ **Vista Blade** en `resources/views/livewire/`
6. ☐ **Ruta** en `routes/web.php` con middleware
7. ☐ **Menú** en `resources/views/layouts/app.blade.php`
8. ☐ **Ejecutar seeder** y limpiar cache de permisos

### 1. Crear Modelo
```php
<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NuevoModelo extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function getActivityModule(): string
    {
        return 'nuevo_modulo';
    }

    protected function getLogDescription(): string
    {
        return $this->name;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}
```

### 2. Crear Migración
```bash
php artisan make:migration create_nuevo_modelos_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nuevo_modelos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nuevo_modelos');
    }
};
```

### 3. Crear Permission Seeder
```php
<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class NuevoModuloPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear módulo
        $module = Module::firstOrCreate(
            ['name' => 'nuevo_modulo'],
            [
                'display_name' => 'Nuevo Módulo',
                'icon' => 'cube',
                'order' => 50,
            ]
        );

        // Definir permisos
        $permissions = [
            'nuevo_modulo.view' => 'Ver registros',
            'nuevo_modulo.create' => 'Crear registros',
            'nuevo_modulo.edit' => 'Editar registros',
            'nuevo_modulo.delete' => 'Eliminar registros',
        ];

        // Crear permisos
        foreach ($permissions as $name => $displayName) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['display_name' => $displayName, 'module_id' => $module->id]
            );
        }

        // Asignar al Admin
        $adminRole = \Spatie\Permission\Models\Role::findByName('Admin');
        $adminRole->givePermissionTo(array_keys($permissions));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
```

### 4. Agregar Ruta
```php
// routes/web.php
Route::get('nuevo-modulo', App\Livewire\NuevoModuloManagement::class)
    ->middleware(['auth', 'verified', 'can:nuevo_modulo.view', \App\Http\Middleware\EnsureSchoolSelected::class])
    ->name('nuevo-modulo.index');
```

### 5. Agregar al Menú
```blade
{{-- resources/views/layouts/app.blade.php --}}
{{-- Dentro de la sección correspondiente del sidebar --}}
@can('nuevo_modulo.view')
    <a href="{{ route('nuevo-modulo.index') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-colors {{ request()->routeIs('nuevo-modulo.index') ? 'bg-blue-50 text-blue-600 font-medium' : '' }}">
        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        Nuevo Módulo
    </a>
@endcan
```

### 6. Ejecutar Seeders y Limpiar Cache
```bash
php artisan migrate
php artisan db:seed --class=NuevoModuloPermissionSeeder
php artisan permission:cache-reset
```

---

## Configuración de Producción

### Optimización
```bash
# Cache de configuración
php artisan config:cache

# Cache de rutas
php artisan route:cache

# Cache de vistas
php artisan view:cache

# Cache de eventos
php artisan event:cache

# Build de assets
npm run build
```

### Variables de Entorno Importantes
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=colegios_production
DB_USERNAME=usuario_seguro
DB_PASSWORD=password_seguro

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Permisos de Archivos (Linux)
```bash
# Permisos de storage
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## Troubleshooting

### Cambios en Vistas No Se Reflejan
```bash
php artisan view:clear
```

### Permisos No Funcionan
```bash
php artisan permission:cache-reset
```

### Error de Sesión Expirada
```bash
php artisan config:clear
php artisan cache:clear
```

### Error 419 (CSRF Token Mismatch)
Verificar que el token CSRF esté incluido en el layout:
```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### Livewire No Actualiza
Verificar que el componente tenga `wire:key` único en loops:
```blade
@foreach($items as $item)
    <div wire:key="item-{{ $item->id }}">
        ...
    </div>
@endforeach
```

<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\School;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

class ActivityLogViewer extends Component
{
    use WithPagination;

    public $search = '';
    public $filterSchool = '';
    public $filterUser = '';
    public $filterModule = '';
    public $filterAction = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    
    public $showDetailModal = false;
    public $selectedLog = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterSchool' => ['except' => ''],
        'filterUser' => ['except' => ''],
        'filterModule' => ['except' => ''],
        'filterAction' => ['except' => ''],
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('activity_logs.view'), 403, 'No tienes permisos para ver el registro de actividad.');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function getLogsProperty()
    {
        return ActivityLog::query()
            ->with(['user', 'school'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('description', 'like', "%{$this->search}%")
                      ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->filterSchool, fn($q) => $q->where('school_id', $this->filterSchool))
            ->when($this->filterUser, fn($q) => $q->where('user_id', $this->filterUser))
            ->when($this->filterModule, fn($q) => $q->where('module', $this->filterModule))
            ->when($this->filterAction, fn($q) => $q->where('action', $this->filterAction))
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn($q) => $q->whereDate('created_at', '<=', $this->filterDateTo))
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    public function getSchoolsProperty()
    {
        return School::orderBy('name')->get(['id', 'name']);
    }

    public function getUsersProperty()
    {
        return User::orderBy('name')->get(['id', 'name']);
    }

    public function getModulesProperty()
    {
        return ActivityLog::distinct()->pluck('module')->filter()->sort()->values();
    }

    public function showDetails($logId)
    {
        $this->selectedLog = ActivityLog::with(['user', 'school'])->find($logId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedLog = null;
    }

    public function clearFilters()
    {
        $this->reset([
            'search', 'filterSchool', 'filterUser', 'filterModule', 
            'filterAction', 'filterDateFrom', 'filterDateTo'
        ]);
    }

    /**
     * Obtener etiqueta legible para un campo
     */
    public function getFieldLabel(string $field): string
    {
        $labels = [
            // Campos comunes
            'name' => 'Nombre',
            'email' => 'Correo electrónico',
            'phone' => 'Teléfono',
            'address' => 'Dirección',
            'description' => 'Descripción',
            'is_active' => 'Estado',
            'status' => 'Estado',
            
            // Cuentas contables
            'code' => 'Código',
            'level' => 'Nivel',
            'parent_id' => 'Cuenta padre',
            'nature' => 'Naturaleza',
            'allows_movement' => 'Permite movimientos',
            
            // Colegios
            'nit' => 'NIT',
            'dane_code' => 'Código DANE',
            'rector_name' => 'Nombre del Rector',
            'resolution' => 'Resolución',
            
            // Usuarios
            'document_type' => 'Tipo de documento',
            'document_number' => 'Número de documento',
            'position' => 'Cargo',
            'email_verified_at' => 'Verificación de correo',
            
            // Roles
            'guard_name' => 'Guard',
        ];

        return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Formatear el valor de un campo para mostrar
     */
    public function formatFieldValue(string $field, $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        // Campos booleanos
        if (in_array($field, ['is_active', 'allows_movement', 'status'])) {
            return $value ? 'Sí' : 'No';
        }

        // Naturaleza contable
        if ($field === 'nature') {
            return $value === 'D' ? 'Débito' : 'Crédito';
        }

        // Nivel de cuenta contable
        if ($field === 'level') {
            $levels = [
                1 => 'Clase',
                2 => 'Grupo',
                3 => 'Cuenta',
                4 => 'Subcuenta',
                5 => 'Auxiliar',
            ];
            return $levels[$value] ?? $value;
        }

        // Tipo de documento
        if ($field === 'document_type') {
            $types = [
                'CC' => 'Cédula de Ciudadanía',
                'CE' => 'Cédula de Extranjería',
                'TI' => 'Tarjeta de Identidad',
                'PA' => 'Pasaporte',
                'NIT' => 'NIT',
            ];
            return $types[$value] ?? $value;
        }

        // IDs de relaciones
        if (str_ends_with($field, '_id')) {
            return "ID: {$value}";
        }

        // Fechas
        if (str_contains($field, '_at') && is_string($value)) {
            try {
                return \Carbon\Carbon::parse($value)->format('d/m/Y H:i');
            } catch (\Exception $e) {
                return $value;
            }
        }

        // Arrays
        if (is_array($value)) {
            return implode(', ', $value);
        }

        return (string) $value;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.activity-log-viewer');
    }
}

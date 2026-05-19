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
    public $schoolId;
    public $isAdmin = false;

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
        
        $this->schoolId = session('selected_school_id');
        $this->isAdmin = auth()->user()->hasRole('Admin');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function getLogsProperty()
    {
        return ActivityLog::query()
            ->with(['user', 'school'])
            ->when(!$this->isAdmin && $this->schoolId, fn($q) => $q->where('school_id', $this->schoolId))
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
        if ($this->isAdmin) {
            return School::orderBy('name')->get(['id', 'name']);
        }
        // Non-admin: only show the current school
        return School::where('id', $this->schoolId)->get(['id', 'name']);
    }

    public function getUsersProperty()
    {
        if ($this->isAdmin) {
            return User::orderBy('name')->get(['id', 'name']);
        }
        // Non-admin: only show users belonging to the current school
        $school = School::find($this->schoolId);
        return $school ? $school->users()->orderBy('name')->get(['users.id', 'users.name']) : collect();
    }

    public function getModulesProperty()
    {
        $query = ActivityLog::query();
        if (!$this->isAdmin && $this->schoolId) {
            $query->where('school_id', $this->schoolId);
        }
        return $query->distinct()->pluck('module')->filter()->sort()->values();
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
            'name'                    => 'Nombre',
            'email'                   => 'Correo electrónico',
            'phone'                   => 'Teléfono',
            'address'                 => 'Dirección',
            'description'             => 'Descripción',
            'is_active'               => 'Estado',
            'status'                  => 'Estado',
            'notes'                   => 'Notas',
            'observations'            => 'Observaciones',
            'date'                    => 'Fecha',
            'type'                    => 'Tipo',

            // Cuentas contables
            'code'                    => 'Código',
            'level'                   => 'Nivel',
            'parent_id'               => 'Cuenta padre',
            'nature'                  => 'Naturaleza',
            'allows_movement'         => 'Permite movimientos',

            // Colegios
            'nit'                     => 'NIT',
            'dane_code'               => 'Código DANE',
            'rector_name'             => 'Nombre del Rector',
            'resolution'              => 'Resolución',

            // Usuarios
            'document_type'           => 'Tipo de documento',
            'document_number'         => 'Número de documento',
            'position'                => 'Cargo',
            'email_verified_at'       => 'Verificación de correo',

            // Roles
            'guard_name'              => 'Guard',

            // Presupuesto
            'school_id'               => 'Colegio',
            'fiscal_year'             => 'Año Fiscal',
            'budget_item_id'          => 'Rubro Presupuestal',
            'current_amount'          => 'Monto Actual',
            'initial_amount'          => 'Monto Inicial',
            'funding_source_id'       => 'Fuente de Financiación',
            'accounting_account_id'   => 'Cuenta Contable',
            'budget_id'               => 'Presupuesto',
            'modification_number'     => 'Número de Modificación',
            'amount'                  => 'Monto',
            'previous_amount'         => 'Monto Anterior',
            'new_amount'              => 'Monto Nuevo',
            'reason'                  => 'Razón / Justificación',
            'document_date'           => 'Fecha de Documento',
            'cancelled_at'            => 'Anulado el',
            'cancelled_by'            => 'Anulado por',
            'cancelled_reason'        => 'Razón de Anulación',
            'created_by'              => 'Creado por',
            'total_amount'            => 'Monto Total',

            // Gastos
            'expense_code_id'         => 'Código de Gasto',
            'expense_distribution_id' => 'Distribución de Gasto',

            // Fuentes de financiación
            'source_type'             => 'Tipo de Fuente',

            // Contratos / Proveedores
            'supplier_id'             => 'Proveedor',
            'contract_number'         => 'Número de Contrato',
            'user_id'                 => 'Usuario',
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
        if (in_array($field, ['is_active', 'allows_movement'])) {
            return $value ? 'Sí' : 'No';
        }

        // Campo status / type
        if ($field === 'type') {
            $types = [
                'income'    => 'Ingreso',
                'expense'   => 'Gasto',
                'addition'  => 'Adición',
                'reduction' => 'Reducción',
            ];
            return $types[$value] ?? $value;
        }

        if ($field === 'status') {
            $statuses = [
                'active'   => 'Activo',
                'inactive' => 'Inactivo',
                'pending'  => 'Pendiente',
                '1'        => 'Activo',
                '0'        => 'Inactivo',
            ];
            return $statuses[(string)$value] ?? $value;
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
                'CC'  => 'Cédula de Ciudadanía',
                'CE'  => 'Cédula de Extranjería',
                'TI'  => 'Tarjeta de Identidad',
                'PA'  => 'Pasaporte',
                'NIT' => 'NIT',
            ];
            return $types[$value] ?? $value;
        }

        // Montos
        if (in_array($field, ['amount', 'initial_amount', 'current_amount', 'previous_amount', 'new_amount', 'total_amount'])) {
            return '$ ' . number_format((float) $value, 2, ',', '.');
        }

        // Resolución de IDs de relaciones
        if ($field === 'budget_item_id') {
            $item = \App\Models\BudgetItem::find($value);
            return $item ? "{$item->code} - {$item->name}" : "ID: {$value}";
        }

        if ($field === 'funding_source_id') {
            $source = \App\Models\FundingSource::find($value);
            return $source ? "{$source->code} - {$source->name}" : "ID: {$value}";
        }

        if ($field === 'accounting_account_id') {
            $account = \App\Models\AccountingAccount::find($value);
            return $account ? "{$account->code} - {$account->name}" : "ID: {$value}";
        }

        if ($field === 'school_id') {
            $school = \App\Models\School::find($value);
            return $school ? $school->name : "ID: {$value}";
        }

        if (in_array($field, ['user_id', 'created_by', 'updated_by', 'cancelled_by'])) {
            $user = \App\Models\User::find($value);
            return $user ? $user->name : "ID: {$value}";
        }

        if ($field === 'budget_id') {
            $budget = \App\Models\Budget::with(['budgetItem', 'fundingSource'])->find($value);
            if ($budget) {
                $item   = $budget->budgetItem?->code ?? '?';
                $source = $budget->fundingSource?->code ?? '?';
                return "{$item} [{$source}] {$budget->fiscal_year}";
            }
            return "ID: {$value}";
        }

        if ($field === 'expense_code_id') {
            $code = \App\Models\ExpenseCode::find($value);
            return $code ? "{$code->code} - {$code->name}" : "ID: {$value}";
        }

        if ($field === 'supplier_id') {
            $supplier = \App\Models\Supplier::find($value);
            return $supplier ? $supplier->name : "ID: {$value}";
        }

        if ($field === 'parent_id') {
            $account = \App\Models\AccountingAccount::find($value);
            return $account ? "{$account->code} - {$account->name}" : "ID: {$value}";
        }

        // Cualquier otro _id genérico
        if (str_ends_with($field, '_id')) {
            return "ID: {$value}";
        }

        // Fechas con timestamp
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

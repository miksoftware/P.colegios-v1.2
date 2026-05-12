<?php

namespace App\Livewire;

use App\Models\RetentionConfig;
use App\Models\School;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class RetentionConfigManagement extends Component
{
    use WithPagination;

    public $schoolId;

    // Filtros
    public $search = '';
    public $filterYear = '';
    public $filterCategory = '';
    public $filterStatus = '';
    public $perPage = 20;

    // Form
    public $showModal = false;
    public $isEditing = false;
    public $configId = null;
    public $fiscal_year;
    public $concept = '';
    public $display_name = '';
    public $category = 'retefuente';
    public $rate_not_declares = 0;
    public $rate_declares = 0;
    public $rate = 0;
    public $min_base = 0;
    public $accounting_code = '';
    public $is_active = true;
    public $notes = '';

    // Eliminación
    public $showDeleteModal = false;
    public $configToDelete = null;

    // Copiar de vigencia anterior
    public $showCopyYearModal = false;
    public $copyFromYear = '';
    public $copyToYear = '';

    // Copiar a otros colegios (Admin)
    public $showCopyToSchoolsModal = false;
    public $copyYearSource = '';
    public $copySchoolIds = [];

    protected $queryString = [
        'search'         => ['except' => ''],
        'filterYear'     => ['except' => ''],
        'filterCategory' => ['except' => ''],
        'filterStatus'   => ['except' => ''],
    ];

    protected function rules()
    {
        $uniqueRule = 'unique:retention_configs,concept';
        if ($this->configId) {
            $uniqueRule .= ',' . $this->configId . ',id,school_id,' . $this->schoolId . ',fiscal_year,' . $this->fiscal_year;
        } else {
            $uniqueRule .= ',NULL,id,school_id,' . $this->schoolId . ',fiscal_year,' . $this->fiscal_year;
        }

        $rules = [
            'fiscal_year'     => 'required|integer|min:2020|max:2100',
            'concept'         => ['required', 'string', 'max:50', $uniqueRule],
            'display_name'    => 'required|string|max:150',
            'category'        => 'required|in:retefuente,reteiva,estampilla,ica',
            'min_base'        => 'required|numeric|min:0',
            'accounting_code' => 'nullable|string|max:150',
            'is_active'       => 'boolean',
            'notes'           => 'nullable|string',
        ];

        if ($this->category === 'retefuente') {
            $rules['rate_not_declares'] = 'required|numeric|min:0|max:100';
            $rules['rate_declares']     = 'required|numeric|min:0|max:100';
            $rules['rate']              = 'nullable|numeric|min:0|max:100';
        } else {
            $rules['rate']              = 'required|numeric|min:0|max:100';
            $rules['rate_not_declares'] = 'nullable|numeric|min:0|max:100';
            $rules['rate_declares']     = 'nullable|numeric|min:0|max:100';
        }

        return $rules;
    }

    protected $messages = [
        'fiscal_year.required'       => 'La vigencia es obligatoria.',
        'concept.required'           => 'El concepto es obligatorio.',
        'concept.unique'             => 'Ya existe una configuración con este concepto para el colegio y vigencia.',
        'display_name.required'      => 'El nombre para mostrar es obligatorio.',
        'category.required'          => 'La categoría es obligatoria.',
        'rate_not_declares.required' => 'La tarifa (no declara) es obligatoria para retefuente.',
        'rate_declares.required'     => 'La tarifa (declara) es obligatoria para retefuente.',
        'rate.required'              => 'La tarifa es obligatoria.',
        'min_base.required'          => 'La base mínima es obligatoria.',
    ];

    public function mount()
    {
        abort_if(!auth()->user()->can('retention_configs.view'), 403);

        $this->schoolId = session('selected_school_id');

        if (!$this->schoolId) {
            session()->flash('error', 'Debes seleccionar un colegio primero.');
            $this->redirect(route('dashboard'), navigate: true);
            return;
        }

        $this->fiscal_year = (int) now()->year;
        $this->filterYear = (string) $this->fiscal_year;
        $this->copyToYear = (string) $this->fiscal_year;
    }

    public function updatingSearch()         { $this->resetPage(); }
    public function updatingFilterYear()     { $this->resetPage(); }
    public function updatingFilterCategory() { $this->resetPage(); }
    public function updatingFilterStatus()   { $this->resetPage(); }

    public function updatedConcept($value)
    {
        if (isset(RetentionConfig::CONCEPTS[$value])) {
            $def = RetentionConfig::CONCEPTS[$value];
            $this->display_name = $def['display_name'];
            $this->category     = $def['category'];
        }
    }

    // ── Computed ──────────────────────────────────────────

    public function getConfigsProperty()
    {
        return RetentionConfig::forSchool($this->schoolId)
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->filterYear !== '', fn($q) => $q->where('fiscal_year', (int) $this->filterYear))
            ->when($this->filterCategory !== '', fn($q) => $q->where('category', $this->filterCategory))
            ->when($this->filterStatus !== '', fn($q) => $q->where('is_active', $this->filterStatus === '1'))
            ->orderBy('fiscal_year', 'desc')
            ->orderBy('category')
            ->orderBy('display_name')
            ->paginate($this->perPage);
    }

    public function getAvailableYearsProperty()
    {
        return RetentionConfig::forSchool($this->schoolId)
            ->select('fiscal_year')
            ->distinct()
            ->orderBy('fiscal_year', 'desc')
            ->pluck('fiscal_year')
            ->toArray();
    }

    public function getSummaryProperty()
    {
        $base = RetentionConfig::forSchool($this->schoolId);

        return [
            'total'      => (clone $base)->count(),
            'active'     => (clone $base)->where('is_active', true)->count(),
            'years'      => (clone $base)->distinct('fiscal_year')->count('fiscal_year'),
            'retefuente' => (clone $base)->where('category', 'retefuente')->count(),
        ];
    }

    public function getAvailableConceptsProperty()
    {
        // Para edición: mostrar todos. Para creación: excluir los que ya existen
        // en el mismo año fiscal para no duplicar.
        $taken = [];
        if (!$this->isEditing && $this->fiscal_year) {
            $taken = RetentionConfig::forSchool($this->schoolId)
                ->where('fiscal_year', $this->fiscal_year)
                ->pluck('concept')
                ->toArray();
        }

        $options = [];
        foreach (RetentionConfig::CONCEPTS as $key => $def) {
            if (in_array($key, $taken)) {
                continue;
            }
            $options[] = [
                'id'   => $key,
                'name' => $def['display_name'] . ' (' . RetentionConfig::CATEGORIES[$def['category']] . ')',
            ];
        }
        return $options;
    }

    public function getAllSchoolsProperty()
    {
        return School::orderBy('name')->get();
    }

    // ── CRUD ──────────────────────────────────────────────

    public function openCreateModal()
    {
        if (!auth()->user()->can('retention_configs.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear configuraciones.', type: 'error');
            return;
        }
        $this->resetForm();
        $this->fiscal_year = (int) ($this->filterYear ?: now()->year);
        $this->showModal = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->can('retention_configs.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para editar configuraciones.', type: 'error');
            return;
        }

        $config = RetentionConfig::forSchool($this->schoolId)->findOrFail($id);

        $this->configId          = $config->id;
        $this->fiscal_year       = $config->fiscal_year;
        $this->concept           = $config->concept;
        $this->display_name      = $config->display_name;
        $this->category          = $config->category;
        $this->rate_not_declares = (float) $config->rate_not_declares;
        $this->rate_declares     = (float) $config->rate_declares;
        $this->rate              = (float) $config->rate;
        $this->min_base          = (float) $config->min_base;
        $this->accounting_code   = $config->accounting_code ?? '';
        $this->is_active         = $config->is_active;
        $this->notes             = $config->notes ?? '';
        $this->isEditing         = true;
        $this->showModal         = true;
    }

    public function save()
    {
        $permission = $this->isEditing ? 'retention_configs.edit' : 'retention_configs.create';
        if (!auth()->user()->can($permission)) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }

        $this->validate();

        $data = [
            'school_id'         => $this->schoolId,
            'fiscal_year'       => (int) $this->fiscal_year,
            'concept'           => $this->concept,
            'display_name'      => trim($this->display_name),
            'category'          => $this->category,
            'min_base'          => (float) $this->min_base,
            'accounting_code'   => $this->accounting_code ?: null,
            'is_active'         => (bool) $this->is_active,
            'notes'             => $this->notes ?: null,
            'rate_not_declares' => $this->category === 'retefuente' ? (float) $this->rate_not_declares : null,
            'rate_declares'     => $this->category === 'retefuente' ? (float) $this->rate_declares : null,
            'rate'              => $this->category !== 'retefuente' ? (float) $this->rate : null,
        ];

        if ($this->isEditing) {
            $config = RetentionConfig::forSchool($this->schoolId)->findOrFail($this->configId);
            $config->update($data);
            $this->dispatch('toast', message: 'Configuración actualizada exitosamente.', type: 'success');
        } else {
            RetentionConfig::create($data);
            $this->dispatch('toast', message: 'Configuración creada exitosamente.', type: 'success');
        }

        $this->closeModal();
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->can('retention_configs.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar configuraciones.', type: 'error');
            return;
        }
        $this->configToDelete = RetentionConfig::forSchool($this->schoolId)->findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if (!auth()->user()->can('retention_configs.delete')) {
            $this->dispatch('toast', message: 'No tienes permisos para eliminar configuraciones.', type: 'error');
            return;
        }

        if ($this->configToDelete) {
            $name = $this->configToDelete->display_name;
            $year = $this->configToDelete->fiscal_year;
            $this->configToDelete->delete();
            $this->dispatch('toast', message: "Configuración '{$name}' ({$year}) eliminada.", type: 'success');
        }

        $this->closeDeleteModal();
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->can('retention_configs.edit')) {
            $this->dispatch('toast', message: 'No tienes permisos para cambiar el estado.', type: 'error');
            return;
        }

        $config = RetentionConfig::forSchool($this->schoolId)->findOrFail($id);
        $config->update(['is_active' => !$config->is_active]);
        $status = $config->is_active ? 'activada' : 'desactivada';
        $this->dispatch('toast', message: "Configuración {$status}.", type: 'success');
    }

    // ── Copiar de vigencia anterior (mismo colegio) ───────

    public function openCopyYearModal()
    {
        if (!auth()->user()->can('retention_configs.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para crear configuraciones.', type: 'error');
            return;
        }
        $this->copyFromYear = (string) ($this->availableYears[0] ?? now()->year);
        $this->copyToYear   = (string) (now()->year);
        $this->showCopyYearModal = true;
    }

    public function copyConfigsFromYear()
    {
        if (!auth()->user()->can('retention_configs.create')) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }

        $this->validate([
            'copyFromYear' => 'required|integer|min:2020|max:2100',
            'copyToYear'   => 'required|integer|min:2020|max:2100|different:copyFromYear',
        ], [
            'copyToYear.different' => 'La vigencia destino debe ser distinta de la origen.',
        ]);

        $source = RetentionConfig::forSchool($this->schoolId)
            ->where('fiscal_year', (int) $this->copyFromYear)
            ->get();

        if ($source->isEmpty()) {
            $this->dispatch('toast', message: "No hay configuraciones en la vigencia {$this->copyFromYear}.", type: 'warning');
            return;
        }

        $copied = 0;
        $skipped = 0;

        foreach ($source as $row) {
            $exists = RetentionConfig::where('school_id', $this->schoolId)
                ->where('fiscal_year', (int) $this->copyToYear)
                ->where('concept', $row->concept)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            RetentionConfig::create([
                'school_id'         => $this->schoolId,
                'fiscal_year'       => (int) $this->copyToYear,
                'concept'           => $row->concept,
                'display_name'      => $row->display_name,
                'category'          => $row->category,
                'rate_not_declares' => $row->rate_not_declares,
                'rate_declares'     => $row->rate_declares,
                'rate'              => $row->rate,
                'min_base'          => $row->min_base,
                'accounting_code'   => $row->accounting_code,
                'is_active'         => $row->is_active,
                'notes'             => $row->notes,
            ]);
            $copied++;
        }

        $this->dispatch(
            'toast',
            message: "Copiadas {$copied} configuración(es) a la vigencia {$this->copyToYear}." . ($skipped > 0 ? " ({$skipped} ya existían y se omitieron)" : ''),
            type: 'success',
        );

        $this->closeCopyYearModal();
        $this->filterYear = $this->copyToYear;
    }

    public function closeCopyYearModal()
    {
        $this->showCopyYearModal = false;
        $this->copyFromYear = '';
    }

    // ── Copiar a otros colegios (Admin) ───────────────────

    public function openCopyToSchoolsModal()
    {
        if (!auth()->user()->isAdmin()) {
            $this->dispatch('toast', message: 'Solo el administrador puede copiar a otros colegios.', type: 'error');
            return;
        }
        if (!auth()->user()->can('retention_configs.copy')) {
            $this->dispatch('toast', message: 'No tienes permisos para copiar configuraciones.', type: 'error');
            return;
        }

        $this->copyYearSource = (string) ($this->filterYear ?: now()->year);
        $this->copySchoolIds = [];
        $this->showCopyToSchoolsModal = true;
    }

    public function toggleCopySchool($schoolId)
    {
        $schoolId = (int) $schoolId;
        if (in_array($schoolId, $this->copySchoolIds)) {
            $this->copySchoolIds = array_values(array_diff($this->copySchoolIds, [$schoolId]));
        } else {
            $this->copySchoolIds[] = $schoolId;
        }
    }

    public function copyToSchools()
    {
        if (!auth()->user()->isAdmin() || !auth()->user()->can('retention_configs.copy')) {
            $this->dispatch('toast', message: 'No tienes permisos para esta acción.', type: 'error');
            return;
        }

        $this->validate([
            'copyYearSource' => 'required|integer|min:2020|max:2100',
            'copySchoolIds'  => 'required|array|min:1',
            'copySchoolIds.*'=> 'integer|exists:schools,id',
        ], [
            'copySchoolIds.required' => 'Debes seleccionar al menos un colegio destino.',
            'copySchoolIds.min'      => 'Debes seleccionar al menos un colegio destino.',
        ]);

        $source = RetentionConfig::forSchool($this->schoolId)
            ->where('fiscal_year', (int) $this->copyYearSource)
            ->get();

        if ($source->isEmpty()) {
            $this->dispatch('toast', message: "No hay configuraciones en la vigencia {$this->copyYearSource}.", type: 'warning');
            return;
        }

        $copied = 0;
        $skipped = 0;

        foreach ($this->copySchoolIds as $targetSchoolId) {
            $targetSchoolId = (int) $targetSchoolId;
            if ($targetSchoolId === (int) $this->schoolId) {
                continue;
            }

            foreach ($source as $row) {
                $exists = RetentionConfig::where('school_id', $targetSchoolId)
                    ->where('fiscal_year', (int) $this->copyYearSource)
                    ->where('concept', $row->concept)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                RetentionConfig::create([
                    'school_id'         => $targetSchoolId,
                    'fiscal_year'       => (int) $this->copyYearSource,
                    'concept'           => $row->concept,
                    'display_name'      => $row->display_name,
                    'category'          => $row->category,
                    'rate_not_declares' => $row->rate_not_declares,
                    'rate_declares'     => $row->rate_declares,
                    'rate'              => $row->rate,
                    'min_base'          => $row->min_base,
                    'accounting_code'   => $row->accounting_code,
                    'is_active'         => $row->is_active,
                    'notes'             => $row->notes,
                ]);
                $copied++;
            }
        }

        $this->dispatch(
            'toast',
            message: "Copiadas {$copied} configuración(es) a " . count($this->copySchoolIds) . " colegio(s)." . ($skipped > 0 ? " ({$skipped} ya existían y se omitieron)" : ''),
            type: 'success',
        );

        $this->closeCopyToSchoolsModal();
    }

    public function closeCopyToSchoolsModal()
    {
        $this->showCopyToSchoolsModal = false;
        $this->copySchoolIds = [];
    }

    // ── Helpers ───────────────────────────────────────────

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->configToDelete = null;
    }

    public function resetForm()
    {
        $this->configId          = null;
        $this->fiscal_year       = (int) now()->year;
        $this->concept           = '';
        $this->display_name      = '';
        $this->category          = 'retefuente';
        $this->rate_not_declares = 0;
        $this->rate_declares     = 0;
        $this->rate              = 0;
        $this->min_base          = 0;
        $this->accounting_code   = '';
        $this->is_active         = true;
        $this->notes             = '';
        $this->isEditing         = false;
        $this->resetValidation();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterCategory', 'filterStatus']);
        $this->filterYear = (string) now()->year;
        $this->resetPage();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.retention-config-management');
    }
}

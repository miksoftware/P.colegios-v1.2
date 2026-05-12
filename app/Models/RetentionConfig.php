<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetentionConfig extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'fiscal_year',
        'concept',
        'display_name',
        'category',
        'rate_not_declares',
        'rate_declares',
        'rate',
        'min_base',
        'accounting_code',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'rate_not_declares' => 'decimal:2',
        'rate_declares' => 'decimal:2',
        'rate' => 'decimal:2',
        'min_base' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ── Constants ─────────────────────────────────────────────

    /**
     * Conceptos soportados por el sistema.
     * [concept => ['display_name', 'category']]
     */
    const CONCEPTS = [
        'compras' => [
            'display_name' => 'Retefuente Compras',
            'category' => 'retefuente',
        ],
        'servicios' => [
            'display_name' => 'Retefuente Servicios',
            'category' => 'retefuente',
        ],
        'honorarios' => [
            'display_name' => 'Retefuente Honorarios',
            'category' => 'retefuente',
        ],
        'arrendamiento_sitios_web' => [
            'display_name' => 'Retefuente Arrendamiento Sitios Web',
            'category' => 'retefuente',
        ],
        'arrendamiento_inmuebles' => [
            'display_name' => 'Retefuente Arrendamiento Bienes Inmuebles',
            'category' => 'retefuente',
        ],
        'transporte_pasajeros' => [
            'display_name' => 'Retefuente Servicio de Transporte de Pasajeros',
            'category' => 'retefuente',
        ],
        'reteiva' => [
            'display_name' => 'ReteIVA',
            'category' => 'reteiva',
        ],
        'estampilla_procultura' => [
            'display_name' => 'Estampilla Procultura',
            'category' => 'estampilla',
        ],
        'estampilla_produlto_mayor' => [
            'display_name' => 'Estampilla Produlto Mayor',
            'category' => 'estampilla',
        ],
        'retencion_ica' => [
            'display_name' => 'Retención ICA',
            'category' => 'ica',
        ],
    ];

    const CATEGORIES = [
        'retefuente' => 'Retención en la Fuente',
        'reteiva'    => 'Retención de IVA',
        'estampilla' => 'Estampilla',
        'ica'        => 'ICA',
    ];

    const CATEGORY_COLORS = [
        'retefuente' => 'bg-red-100 text-red-700',
        'reteiva'    => 'bg-orange-100 text-orange-700',
        'estampilla' => 'bg-amber-100 text-amber-700',
        'ica'        => 'bg-purple-100 text-purple-700',
    ];

    // ── Activity Log ──────────────────────────────────────────

    protected static function getActivityModule(): string
    {
        return 'retention_configs';
    }

    protected function getLogDescription(): string
    {
        return ($this->display_name ?? $this->concept) . ' (vigencia ' . $this->fiscal_year . ')';
    }

    // ── Relationships ─────────────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // ── Accessors ─────────────────────────────────────────────

    public function getConceptNameAttribute(): string
    {
        return self::CONCEPTS[$this->concept]['display_name'] ?? $this->display_name ?? $this->concept;
    }

    public function getCategoryNameAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getCategoryColorAttribute(): string
    {
        return self::CATEGORY_COLORS[$this->category] ?? 'bg-gray-100 text-gray-700';
    }

    public function getIsRetefuenteAttribute(): bool
    {
        return $this->category === 'retefuente';
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForYear($query, int $fiscalYear)
    {
        return $query->where('fiscal_year', $fiscalYear);
    }

    public function scopeForConcept($query, string $concept)
    {
        return $query->where('concept', $concept);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('concept', 'like', "%{$search}%")
              ->orWhere('display_name', 'like', "%{$search}%")
              ->orWhere('accounting_code', 'like', "%{$search}%");
        });
    }

    // ── Static helpers ────────────────────────────────────────

    /**
     * Obtiene la configuración efectiva para un colegio, año y concepto.
     */
    public static function getForSchoolYearConcept(int $schoolId, int $fiscalYear, string $concept): ?self
    {
        return static::forSchool($schoolId)
            ->forYear($fiscalYear)
            ->forConcept($concept)
            ->active()
            ->first();
    }

    /**
     * Retorna las tarifas retefuente [no_declara, declara] para un concepto.
     */
    public static function getRetentionRates(int $schoolId, int $fiscalYear, string $concept): array
    {
        $config = static::getForSchoolYearConcept($schoolId, $fiscalYear, $concept);

        if (!$config) {
            return [0.0, 0.0];
        }

        return [
            (float) $config->rate_not_declares,
            (float) $config->rate_declares,
        ];
    }

    /**
     * Retorna la tarifa para un concepto (declara o no).
     */
    public static function getRetentionRate(int $schoolId, int $fiscalYear, string $concept, bool $declaresRent): float
    {
        [$notDeclares, $declares] = static::getRetentionRates($schoolId, $fiscalYear, $concept);

        return $declaresRent ? $declares : $notDeclares;
    }

    /**
     * Retorna la base mínima para aplicar retención de un concepto.
     */
    public static function getMinBase(int $schoolId, int $fiscalYear, string $concept): float
    {
        $config = static::getForSchoolYearConcept($schoolId, $fiscalYear, $concept);

        return $config ? (float) $config->min_base : 0.0;
    }

    /**
     * Verifica si un subtotal supera la base mínima de un concepto.
     */
    public static function meetsThreshold(int $schoolId, int $fiscalYear, string $concept, float $subtotal): bool
    {
        return $subtotal >= static::getMinBase($schoolId, $fiscalYear, $concept);
    }
}

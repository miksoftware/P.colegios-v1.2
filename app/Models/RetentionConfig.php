<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RetentionConfig extends Model
{
    use LogsActivity;

    protected static array $conceptCatalogCache = [];

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
        'applicability_rules',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'rate_not_declares' => 'decimal:2',
        'rate_declares' => 'decimal:2',
        'rate' => 'decimal:2',
        'min_base' => 'decimal:2',
        'is_active' => 'boolean',
        'applicability_rules' => 'array',
    ];

    // ── Constants ─────────────────────────────────────────────

    /**
     * Conceptos base sugeridos por el sistema.
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
        'impuesto_contribucion_especial' => [
            'display_name' => 'Impuesto de contribución especial',
            'category' => 'retefuente',
        ],
        'retencion_contrato_obra' => [
            'display_name' => 'Retención contrato de obra',
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
        'estampilla_prodeporte' => [
            'display_name' => 'Estampilla Prodeporte',
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

    const APPLICABILITY_PERSON_TYPES = [
        'natural' => 'Persona Natural',
        'juridica' => 'Persona Juridica',
    ];

    const APPLICABILITY_PAYMENT_TYPES = [
        'contract' => 'Con Contrato',
        'direct' => 'Pago Directo',
        'accounts_payable' => 'Cuentas por Pagar',
    ];

    const SERVICE_CONTRACT_MODES = [
        'any' => 'Cualquier contrato',
        'only_service' => 'Solo prestacion de servicios',
        'except_service' => 'Excepto prestacion de servicios',
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
        return static::getConceptDisplayName(
            $this->concept,
            $this->school_id,
            $this->fiscal_year,
            $this->display_name
        );
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

    public function getNormalizedApplicabilityRulesAttribute(): array
    {
        return static::normalizeApplicabilityRules($this->applicability_rules);
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

    public static function flushConceptCatalogCache(): void
    {
        static::$conceptCatalogCache = [];
    }

    public static function normalizeConceptKey(?string $value): string
    {
        $normalized = Str::of((string) $value)
            ->trim()
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();

        return substr($normalized, 0, 50);
    }

    public static function getConceptCatalog(?int $schoolId = null, ?int $fiscalYear = null): Collection
    {
        $cacheKey = implode(':', [$schoolId ?? 'all', $fiscalYear ?? 'all']);

        if (isset(static::$conceptCatalogCache[$cacheKey])) {
            return collect(static::$conceptCatalogCache[$cacheKey]);
        }

        $defaults = collect(static::CONCEPTS)
            ->map(fn(array $definition, string $concept) => [
                'concept' => $concept,
                'display_name' => $definition['display_name'],
                'category' => $definition['category'],
                'is_predefined' => true,
            ])
            ->keyBy('concept');

        $databaseConcepts = static::query()
            ->select('concept', 'display_name', 'category', 'fiscal_year', 'id')
            ->when($schoolId, fn($query) => $query->where('school_id', $schoolId))
            ->when($fiscalYear, fn($query) => $query->where('fiscal_year', $fiscalYear))
            ->orderByDesc('fiscal_year')
            ->orderByDesc('id')
            ->get()
            ->unique('concept')
            ->map(fn(self $config) => [
                'concept' => $config->concept,
                'display_name' => $config->display_name,
                'category' => $config->category,
                'is_predefined' => isset(static::CONCEPTS[$config->concept]),
            ])
            ->keyBy('concept');

        $catalog = $defaults
            ->merge($databaseConcepts)
            ->sortBy(fn(array $item) => ($item['category'] ?? '') . '|' . ($item['display_name'] ?? '') . '|' . ($item['concept'] ?? ''))
            ->values();

        static::$conceptCatalogCache[$cacheKey] = $catalog->all();

        return $catalog;
    }

    public static function getConceptDefinition(string $concept, ?int $schoolId = null, ?int $fiscalYear = null): ?array
    {
        $scopes = [
            [$schoolId, $fiscalYear],
            [$schoolId, null],
            [null, $fiscalYear],
            [null, null],
        ];

        foreach ($scopes as [$scopeSchoolId, $scopeFiscalYear]) {
            $definition = static::getConceptCatalog($scopeSchoolId, $scopeFiscalYear)
                ->firstWhere('concept', $concept);

            if ($definition) {
                return $definition;
            }
        }

        return null;
    }

    public static function getConceptDisplayName(?string $concept, ?int $schoolId = null, ?int $fiscalYear = null, ?string $fallback = null): string
    {
        if (!$concept) {
            return $fallback ?: 'Sin retencion';
        }

        $definition = static::getConceptDefinition($concept, $schoolId, $fiscalYear);

        return $definition['display_name'] ?? $fallback ?? $concept;
    }

    public static function normalizeApplicabilityRules($rules): array
    {
        $rules = is_array($rules) ? $rules : [];
        $excludeRules = is_array($rules['exclude_rules'] ?? null) ? $rules['exclude_rules'] : [];

        $normalized = [];

        foreach ($excludeRules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $personTypes = array_values(array_intersect(
                array_keys(static::APPLICABILITY_PERSON_TYPES),
                array_filter((array) ($rule['person_types'] ?? []))
            ));

            $paymentTypes = array_values(array_intersect(
                array_keys(static::APPLICABILITY_PAYMENT_TYPES),
                array_filter((array) ($rule['payment_types'] ?? []))
            ));

            $serviceContractMode = $rule['service_contract_mode'] ?? 'any';
            if (!array_key_exists($serviceContractMode, static::SERVICE_CONTRACT_MODES)) {
                $serviceContractMode = 'any';
            }

            if (empty($personTypes) && empty($paymentTypes) && $serviceContractMode === 'any') {
                continue;
            }

            $normalized[] = [
                'person_types' => $personTypes,
                'payment_types' => $paymentTypes,
                'service_contract_mode' => $serviceContractMode,
            ];
        }

        return ['exclude_rules' => $normalized];
    }

    public function appliesToContext(array $context = []): bool
    {
        foreach (($this->normalized_applicability_rules['exclude_rules'] ?? []) as $rule) {
            if ($this->ruleMatchesContext($rule, $context)) {
                return false;
            }
        }

        return true;
    }

    protected function ruleMatchesContext(array $rule, array $context): bool
    {
        $personType = $context['person_type'] ?? null;
        if (!empty($rule['person_types']) && !in_array($personType, $rule['person_types'], true)) {
            return false;
        }

        $paymentType = $context['payment_type'] ?? null;
        if (!empty($rule['payment_types']) && !in_array($paymentType, $rule['payment_types'], true)) {
            return false;
        }

        $isServiceContract = (bool) ($context['is_service_contract'] ?? false);
        return match ($rule['service_contract_mode'] ?? 'any') {
            'only_service' => $isServiceContract,
            'except_service' => !$isServiceContract,
            default => true,
        };
    }
}

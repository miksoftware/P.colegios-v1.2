<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\RpFundingSource;

class Cdp extends Model
{
    use LogsActivity;

    protected $table = 'cdps';

    protected $fillable = [
        'school_id',
        'convocatoria_id',
        'convocatoria_distribution_id',
        'cdp_number',
        'fiscal_year',
        'budget_item_id',
        'total_amount',
        'status',
        'created_by',
    ];

    protected $casts = [
        'cdp_number' => 'integer',
        'fiscal_year' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    const STATUSES = [
        'active' => 'Activo',
        'used' => 'Utilizado',
        'cancelled' => 'Anulado',
    ];

    const STATUS_COLORS = [
        'active' => 'bg-green-100 text-green-700',
        'used' => 'bg-blue-100 text-blue-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];

    protected static function getActivityModule(): string
    {
        return 'precontractual';
    }

    protected function getLogDescription(): string
    {
        return 'CDP #' . str_pad($this->cdp_number, 3, '0', STR_PAD_LEFT);
    }

    // Relaciones

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function convocatoria(): BelongsTo
    {
        return $this->belongsTo(Convocatoria::class);
    }

    public function convocatoriaDistribution(): BelongsTo
    {
        return $this->belongsTo(ConvocatoriaDistribution::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fundingSources(): HasMany
    {
        return $this->hasMany(CdpFundingSource::class);
    }

    public function contractRp(): HasOne
    {
        return $this->hasOne(ContractRp::class);
    }

    // Accessors

    public function getFormattedNumberAttribute(): string
    {
        return str_pad($this->cdp_number, 3, '0', STR_PAD_LEFT);
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-gray-100 text-gray-700';
    }

    /**
     * Saldo disponible del rubro/distribución antes de crear este CDP (Saldo anterior)
     */
    public function getPriorAvailableBalanceAttribute(): float
    {
        $convDist = $this->convocatoriaDistribution;
        $convocatoria = $this->convocatoria;

        if ($convDist && $convDist->expenseDistribution) {
            $expDist = $convDist->expenseDistribution;
            $currentConvId = $convocatoria?->id ?? $this->convocatoria_id;

            $convDistributions = $expDist->convocatoriaDistributions()
                ->with('convocatoria.contract.rps.cdp', 'convocatoria.contract.rps.fundingSources')
                ->get();

            $paymentLines = $expDist->paymentOrderLines()
                ->with('paymentOrder.contract')
                ->get();

            $directPaid = (float) $paymentLines
                ->filter(fn($line) => $line->paymentOrder
                    && $line->paymentOrder->payment_type === 'direct'
                    && in_array($line->paymentOrder->status, ['draft', 'approved', 'paid'])
                )
                ->sum('total');

            $validPaymentLines = $paymentLines->filter(
                fn($line) => $line->paymentOrder && in_array($line->paymentOrder->status, ['draft', 'approved', 'paid'])
            );

            $priorLocked = 0;

            foreach ($convDistributions as $cd) {
                if ($cd->convocatoria && $cd->convocatoria->status === 'cancelled') {
                    continue;
                }

                if ($currentConvId && $cd->convocatoria_id >= $currentConvId) {
                    continue;
                }

                $contract = $cd->convocatoria->contract ?? null;
                if ($contract && $contract->status === 'annulled') {
                    continue;
                }

                $paidForThis = $validPaymentLines
                    ->filter(fn($line) => $line->paymentOrder->contract && $line->paymentOrder->contract->convocatoria_id == $cd->convocatoria_id)
                    ->sum('total');

                $committedAmount = (float) $cd->amount;
                if ($contract && $contract->status !== 'annulled') {
                    $nonAdditionRps = collect($contract->rps ?? [])
                        ->where('status', 'active')
                        ->where('is_addition', false);

                    if ($nonAdditionRps->isNotEmpty()) {
                        $rpsForDist = $nonAdditionRps->filter(
                            fn($rp) => $rp->cdp && (int) $rp->cdp->convocatoria_distribution_id === (int) $cd->id
                        );

                        if ($rpsForDist->isNotEmpty()) {
                            $committedAmount = (float) $rpsForDist->sum('total_amount');
                        } else {
                            $rpsForConv = $nonAdditionRps->filter(
                                fn($rp) => $rp->cdp
                                    && (int) $rp->cdp->convocatoria_id === (int) $cd->convocatoria_id
                                    && is_null($rp->cdp->convocatoria_distribution_id)
                            );

                            if ($rpsForConv->isNotEmpty()) {
                                $totalConvAmt = (float) ConvocatoriaDistribution::where('convocatoria_id', $cd->convocatoria_id)->sum('amount');
                                $proportion = $totalConvAmt > 0 ? (float) $cd->amount / $totalConvAmt : 1.0;
                                $committedAmount = (float) $rpsForConv->sum('total_amount') * $proportion;
                            }
                        }
                    }
                }

                $baseLocked = max((float) $paidForThis, $committedAmount);
                $priorLocked += $baseLocked;

                if ($contract && $contract->status !== 'annulled') {
                    $additionRps = $contract->rps ?? collect();
                    foreach ($additionRps as $rp) {
                        if ($rp->status !== 'active' || !$rp->is_addition) {
                            continue;
                        }
                        foreach ($rp->fundingSources ?? [] as $rpFs) {
                            if ($rpFs->budget_id == $expDist->budget_id) {
                                $priorLocked += (float) $rpFs->amount;
                            }
                        }
                    }
                }
            }

            return max(0, (float) $expDist->amount - $priorLocked - $directPaid);
        }

        $savedBalance = (float) $this->fundingSources->sum('available_balance_at_creation');
        return $savedBalance > 0 ? $savedBalance : (float) $this->total_amount;
    }

    // Scopes

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where('cdp_number', 'like', "%{$search}%");
    }

    // Métodos estáticos

    public static function getNextCdpNumber(int $schoolId, int $fiscalYear): int
    {
        $maxNumber = static::where('school_id', $schoolId)
            ->where('fiscal_year', $fiscalYear)
            ->max('cdp_number');

        return $maxNumber ? $maxNumber + 1 : 1;
    }

    /**
     * Calcula el total reservado/comprometido para una fuente de financiación específica
     * en un año fiscal dado.
     *
     * - CDPs activos: se cuenta el monto completo del CDP (reserva)
     * - CDPs utilizados: se cuenta solo lo realmente comprometido en RPs (contratos)
     * - CDPs anulados: se excluyen
     */
    public static function getTotalReservedForFundingSource(int $fundingSourceId, int $fiscalYear, ?int $schoolId = null): float
    {
        // Monto reservado por CDPs activos (aún no tienen contrato)
        $activeReserved = (float) CdpFundingSource::whereHas('cdp', function ($q) use ($fiscalYear, $schoolId) {
            $q->where('fiscal_year', $fiscalYear)
              ->where('status', 'active');
            if ($schoolId) {
                $q->where('school_id', $schoolId);
            }
        })
        ->where('funding_source_id', $fundingSourceId)
        ->sum('amount');

        // Monto comprometido por RPs de CDPs utilizados (ya tienen contrato)
        $usedCommitted = (float) RpFundingSource::whereHas('contractRp', function ($q) use ($fiscalYear, $schoolId) {
            $q->where('fiscal_year', $fiscalYear)
              ->where('status', '!=', 'cancelled')
              ->whereHas('cdp', function ($cdpQ) use ($schoolId) {
                  $cdpQ->where('status', 'used');
                  if ($schoolId) {
                      $cdpQ->where('school_id', $schoolId);
                  }
              });
        })
        ->where('funding_source_id', $fundingSourceId)
        ->sum('amount');

        return $activeReserved + $usedCommitted;
    }

    /**
     * Calcula el total reservado/comprometido para un presupuesto específico
     * en un año fiscal dado.
     *
     * - CDPs activos: se cuenta el monto completo del CDP (reserva)
     * - CDPs utilizados: se cuenta solo lo realmente comprometido en RPs (contratos)
     * - CDPs anulados: se excluyen
     */
    public static function getTotalReservedForBudget(int $budgetId, int $fiscalYear, ?int $schoolId = null): float
    {
        // Monto reservado por CDPs activos (aún no tienen contrato)
        $activeReserved = (float) CdpFundingSource::whereHas('cdp', function ($q) use ($fiscalYear, $schoolId) {
            $q->where('fiscal_year', $fiscalYear)
              ->where('status', 'active');
            if ($schoolId) {
                $q->where('school_id', $schoolId);
            }
        })
        ->where('budget_id', $budgetId)
        ->sum('amount');

        // Monto comprometido por RPs de CDPs utilizados (ya tienen contrato)
        $usedCommitted = (float) RpFundingSource::whereHas('contractRp', function ($q) use ($fiscalYear, $schoolId) {
            $q->where('fiscal_year', $fiscalYear)
              ->where('status', '!=', 'cancelled')
              ->whereHas('cdp', function ($cdpQ) use ($schoolId) {
                  $cdpQ->where('status', 'used');
                  if ($schoolId) {
                      $cdpQ->where('school_id', $schoolId);
                  }
              });
        })
        ->where('budget_id', $budgetId)
        ->sum('amount');

        return $activeReserved + $usedCommitted;
    }

}

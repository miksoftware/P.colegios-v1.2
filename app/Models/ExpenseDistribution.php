<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseDistribution extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'budget_id',
        'expense_code_id',
        'amount',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function getActivityModule(): string
    {
        return 'expenses';
    }

    protected function getLogDescription(): string
    {
        return $this->expenseCode?->name ?? 'Distribución #' . $this->id;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function expenseCode(): BelongsTo
    {
        return $this->belongsTo(ExpenseCode::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ExpenseExecution::class);
    }

    /**
     * Convocatorias generadas desde esta distribución
     */
    public function convocatorias(): HasMany
    {
        return $this->hasMany(Convocatoria::class);
    }
    /**
     * Distribuciones de convocatoria que usan esta distribución de gasto
     */
    public function convocatoriaDistributions(): HasMany
    {
        return $this->hasMany(ConvocatoriaDistribution::class);
    }

    /**
     * Líneas de órdenes de pago que usan esta distribución de gasto
     */
    public function paymentOrderLines(): HasMany
    {
        return $this->hasMany(PaymentOrderExpenseLine::class);
    }

    // Monto total ejecutado (ejecuciones directas legacy)
    public function getTotalExecutedAttribute(): float
    {
        return (float) $this->executions()->sum('amount');
    }

    /**
     * Monto total pagado a través de órdenes de pago (no anuladas).
     */
    public function getTotalPaidAttribute(): float
    {
        if ($this->relationLoaded('paymentOrderLines')) {
            return (float) $this->paymentOrderLines
                ->filter(fn($line) => $line->paymentOrder && in_array($line->paymentOrder->status, ['draft', 'approved', 'paid']))
                ->sum('total');
        }

        return (float) $this->paymentOrderLines()
            ->whereHas('paymentOrder', fn($q) => $q->whereIn('status', ['draft', 'approved', 'paid']))
            ->sum('total');
    }

    // Monto comprometido en convocatorias no canceladas
    public function getTotalCommittedAttribute(): float
    {
        if ($this->relationLoaded('convocatoriaDistributions')) {
            return (float) $this->convocatoriaDistributions
                ->filter(fn($cd) => $cd->convocatoria && $cd->convocatoria->status !== 'cancelled')
                ->sum('amount');
        }

        return (float) $this->convocatoriaDistributions()
            ->whereHas('convocatoria', fn($q) => $q->where('status', '!=', 'cancelled'))
            ->sum('amount');
    }

    /**
     * Monto realmente bloqueado por convocatorias.
     * Para cada convocatoria no cancelada:
     * - Si la convocatoria tiene contrato anulado → liberado (0)
     * - Si la convocatoria tiene contrato con pagos para esta distribución → bloqueo = lo pagado (mínimo el compromiso)
     * - Si la convocatoria tiene contrato (no anulado) → bloqueo = compromiso original
     * - Si la convocatoria NO tiene contrato → bloqueo = compromiso original (aún reservado)
     * Además cuenta las adiciones de recursos (RPs de adición) de contratos vinculados.
     */
    public function getTotalLockedAttribute(): float
    {
        $locked = 0;

        // Usar relaciones cargadas si están disponibles
        $convDistributions = $this->relationLoaded('convocatoriaDistributions')
            ? $this->convocatoriaDistributions
            : $this->convocatoriaDistributions()->with('convocatoria.contract.rps.cdp', 'convocatoria.contract.rps.fundingSources')->get();

        $paymentLines = $this->relationLoaded('paymentOrderLines')
            ? $this->paymentOrderLines
            : $this->paymentOrderLines()->with('paymentOrder.contract')->get();

        // Filtrar líneas de pago válidas (no anuladas)
        $validPaymentLines = $paymentLines->filter(
            fn($line) => $line->paymentOrder && in_array($line->paymentOrder->status, ['draft', 'approved', 'paid'])
        );

        foreach ($convDistributions as $cd) {
            // Ignorar convocatorias canceladas
            if ($cd->convocatoria && $cd->convocatoria->status === 'cancelled') {
                continue;
            }

            $contract = $cd->convocatoria->contract ?? null;

            // Si el contrato está anulado, liberar
            if ($contract && $contract->status === 'annulled') {
                continue;
            }

            // Buscar pagos de esta distribución vinculados a esta convocatoria
            $paidForThis = $validPaymentLines
                ->filter(fn($line) => $line->paymentOrder->contract && $line->paymentOrder->contract->convocatoria_id == $cd->convocatoria_id)
                ->sum('total');

            // Si el contrato tiene RPs activos (no de adición) vinculados a esta distribución,
            // usar el monto del RP en lugar del estimado de la convocatoria.
            // Esto libera la diferencia si el contrato se firmó por menos del estimado.
            $committedAmount = (float) $cd->amount; // fallback: estimado convocatoria
            if ($contract && $contract->status !== 'annulled') {
                $rpsForDist = collect($contract->rps ?? [])
                    ->where('status', 'active')
                    ->where('is_addition', false)
                    ->filter(fn($rp) => $rp->cdp && $rp->cdp->convocatoria_distribution_id == $cd->id);

                if ($rpsForDist->isNotEmpty()) {
                    $committedAmount = (float) $rpsForDist->sum('total_amount');
                }
            }

            // Bloqueo base = el mayor entre lo pagado y el monto comprometido (RP o estimado)
            $baseLocked = max((float) $paidForThis, $committedAmount);
            $locked += $baseLocked;

            // Sumar adiciones de recursos del contrato (RPs de adición activos)
            // que apuntan al mismo budget_id de esta distribución
            if ($contract && $contract->status !== 'annulled') {
                $additionRps = $contract->rps ?? collect();
                foreach ($additionRps as $rp) {
                    if ($rp->status !== 'active' || !$rp->is_addition) {
                        continue;
                    }
                    // Verificar que las fuentes del RP apuntan al mismo budget
                    foreach ($rp->fundingSources ?? [] as $rpFs) {
                        if ($rpFs->budget_id == $this->budget_id) {
                            $locked += (float) $rpFs->amount;
                        }
                    }
                }
            }
        }

        return $locked;
    }

    /**
     * Saldo disponible para nuevas convocatorias.
     * = monto distribuido - ejecutado directo - monto realmente bloqueado - pagos directos
     * Los pagos directos (payment_type = 'direct') no pasan por convocatoria/contrato,
     * por lo que deben descontarse aquí explícitamente.
     */
    public function getAvailableBalanceAttribute(): float
    {
        // Pagos directos sobre este rubro (no anulados), sin convocatoria de por medio
        if ($this->relationLoaded('paymentOrderLines')) {
            $directPaid = (float) $this->paymentOrderLines
                ->filter(fn($line) => $line->paymentOrder
                    && $line->paymentOrder->payment_type === 'direct'
                    && in_array($line->paymentOrder->status, ['draft', 'approved', 'paid'])
                )
                ->sum('total');
        } else {
            $directPaid = (float) $this->paymentOrderLines()
                ->whereHas('paymentOrder', fn($q) => $q
                    ->where('payment_type', 'direct')
                    ->whereIn('status', ['draft', 'approved', 'paid'])
                )
                ->sum('total');
        }

        return (float) $this->amount - $this->total_executed - $this->total_locked - $directPaid;
    }

    // Porcentaje ejecutado
    public function getExecutionPercentageAttribute(): float
    {
        if ($this->amount <= 0) {
            return 0;
        }
        return round(($this->total_executed / $this->amount) * 100, 2);
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForBudget($query, int $budgetId)
    {
        return $query->where('budget_id', $budgetId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryDischarge extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'consecutive',
        'date',
        'resolution_number',
        'total_value',
        'observations',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'total_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->consecutive) {
                $maxConsecutive = self::where('school_id', $model->school_id)->max('consecutive');
                $model->consecutive = $maxConsecutive ? $maxConsecutive + 1 : 1;
            }
        });
    }

    protected static function getActivityModule(): string
    {
        return 'inventories';
    }

    protected function getLogDescription(): string
    {
        return 'Resolución de Baja N° ' . $this->consecutive;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('consecutive', 'like', "%{$search}%")
              ->orWhere('resolution_number', 'like', "%{$search}%")
              ->orWhere('observations', 'like', "%{$search}%");
        });
    }

    /**
     * Recalcular el valor total basado en los items dados de baja
     */
    public function recalculateTotal()
    {
        $this->total_value = $this->items()->sum('initial_value');
        $this->save();
    }
}

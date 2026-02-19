<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    use LogsActivity;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Relationships ──────────────────────────────────

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function activeAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class)->where('is_active', true);
    }

    // ─── Accessors ──────────────────────────────────────

    public function getAccountsCountAttribute(): int
    {
        return $this->accounts()->count();
    }

    public function getActiveAccountsCountAttribute(): int
    {
        return $this->activeAccounts()->count();
    }

    // ─── Scopes ─────────────────────────────────────────

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
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }
}

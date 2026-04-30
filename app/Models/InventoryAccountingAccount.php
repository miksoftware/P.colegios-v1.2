<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class InventoryAccountingAccount extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'depreciation_years',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'depreciation_years' => 'integer',
    ];

    protected static function getActivityModule(): string
    {
        return 'inventories';
    }

    protected function getLogDescription(): string
    {
        return $this->name . ' (' . $this->code . ')';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%");
        });
    }
}

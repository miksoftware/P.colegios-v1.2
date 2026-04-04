<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class ExpenseCode extends Model
{
    use LogsActivity;

    protected $fillable = [
        'sifse_code',
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function getActivityModule(): string
    {
        return 'expense_codes';
    }

    protected function getLogDescription(): string
    {
        return "{$this->sifse_code} - {$this->code} - {$this->name}";
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('sifse_code', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%");
        });
    }
}

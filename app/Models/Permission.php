<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'guard_name',
        'module_id',
    ];

    /**
     * Get the module that owns the permission.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'icon',
        'order',
    ];

    /**
     * Get the permissions for the module.
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeederLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'seeder',
        'batch',
        'executed_at',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    /**
     * Verificar si un seeder ya fue ejecutado.
     */
    public static function hasRun(string $seederClass): bool
    {
        return static::where('seeder', $seederClass)->exists();
    }

    /**
     * Registrar que un seeder fue ejecutado.
     */
    public static function log(string $seederClass, int $batch): void
    {
        static::create([
            'seeder' => $seederClass,
            'batch' => $batch,
            'executed_at' => now(),
        ]);
    }

    /**
     * Obtener el siguiente número de batch.
     */
    public static function nextBatch(): int
    {
        return (static::max('batch') ?? 0) + 1;
    }
}

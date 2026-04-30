<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_type',
        'original_filename',
        'for_all_schools',
        'is_published',
        'created_by',
    ];

    protected $casts = [
        'for_all_schools' => 'boolean',
        'is_published' => 'boolean',
    ];

    // ─────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'news_school');
    }

    // ─────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return Storage::url($this->file_path);
    }

    public function getIsImageAttribute(): bool
    {
        return $this->file_type === 'image';
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->file_type === 'pdf';
    }

    // ─────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope news visible to a given school:
     * either for_all_schools = true OR the school is in the pivot.
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where(function ($q) use ($schoolId) {
            $q->where('for_all_schools', true)
              ->orWhereHas('schools', fn ($sq) => $sq->where('schools.id', $schoolId));
        });
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}

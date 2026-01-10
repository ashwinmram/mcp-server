<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Lesson extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'source_project',
        'type',
        'category',
        'tags',
        'metadata',
        'content',
        'content_hash',
        'is_generic',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'metadata' => 'array',
            'is_generic' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include generic lessons.
     */
    public function scopeGeneric(Builder $query): Builder
    {
        return $query->where('is_generic', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to filter by tags.
     */
    public function scopeByTags(Builder $query, array $tags): Builder
    {
        return $query->where(function (Builder $q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    /**
     * Scope a query to filter by source project.
     */
    public function scopeBySourceProject(Builder $query, string $sourceProject): Builder
    {
        return $query->where('source_project', $sourceProject);
    }

    /**
     * Generate a content hash for the lesson content.
     */
    public static function generateContentHash(string $content): string
    {
        return hash('sha256', $content);
    }

    /**
     * Find a lesson by content hash and source project.
     */
    public static function findByContentHash(string $contentHash, string $sourceProject): ?self
    {
        return static::where('content_hash', $contentHash)
            ->where('source_project', $sourceProject)
            ->first();
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Lesson $lesson) {
            if (empty($lesson->content_hash) && ! empty($lesson->content)) {
                $lesson->content_hash = static::generateContentHash($lesson->content);
            }
        });

        static::updating(function (Lesson $lesson) {
            if ($lesson->isDirty('content')) {
                $lesson->content_hash = static::generateContentHash($lesson->content);
            }
        });
    }
}

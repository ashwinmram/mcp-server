<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'source_projects',
        'type',
        'category',
        'subcategory',
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
            'source_projects' => 'array',
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
     * Scope a query to filter by subcategory.
     */
    public function scopeBySubcategory(Builder $query, string $subcategory): Builder
    {
        return $query->where('subcategory', $subcategory);
    }

    /**
     * Scope a query to filter by category or subcategory.
     * This maintains backward compatibility - querying "lessons-learned"
     * will return all lessons in that category regardless of subcategory.
     */
    public function scopeByCategoryOrSubcategory(Builder $query, string $categoryOrSubcategory): Builder
    {
        return $query->where(function (Builder $q) use ($categoryOrSubcategory) {
            $q->where('category', $categoryOrSubcategory)
                ->orWhere('subcategory', $categoryOrSubcategory);
        });
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
     * Find a lesson by content hash across all projects.
     * Returns the oldest lesson with this content hash.
     */
    public static function findByContentHashAcrossProjects(string $contentHash): ?self
    {
        return static::where('content_hash', $contentHash)
            ->orderBy('created_at', 'asc')
            ->first();
    }

    /**
     * Find all lessons with the same content hash (duplicates).
     */
    public static function findDuplicatesByContentHash(string $contentHash): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('content_hash', $contentHash)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Merge tags from multiple lessons, removing duplicates.
     */
    public static function mergeTags(array ...$tagsArrays): array
    {
        $merged = [];
        foreach ($tagsArrays as $tags) {
            if (is_array($tags)) {
                $merged = array_merge($merged, $tags);
            }
        }

        return array_values(array_unique($merged));
    }

    /**
     * Merge metadata from multiple lessons.
     * For conflicting keys, newer values overwrite older ones.
     * Arrays are merged to preserve all values.
     */
    public static function mergeMetadata(array ...$metadataArrays): array
    {
        $merged = [];
        foreach ($metadataArrays as $metadata) {
            if (is_array($metadata) && ! empty($metadata)) {
                foreach ($metadata as $key => $value) {
                    if (isset($merged[$key]) && is_array($merged[$key]) && is_array($value)) {
                        // Both are arrays, merge them
                        $merged[$key] = array_merge($merged[$key], $value);
                    } else {
                        // Overwrite with newer value
                        $merged[$key] = $value;
                    }
                }
            }
        }

        return $merged;
    }

    /**
     * Flatten metadata arrays that were merged recursively.
     */
    public static function flattenMetadata(array $metadata): array
    {
        $flattened = [];
        foreach ($metadata as $key => $value) {
            if (is_array($value) && isset($value[0]) && is_array($value[0])) {
                // This was merged recursively, flatten it
                $flattened[$key] = $value;
            } else {
                $flattened[$key] = $value;
            }
        }

        return $flattened;
    }

    /**
     * Merge source projects arrays, removing duplicates.
     */
    public static function mergeSourceProjects(array ...$sourceProjectsArrays): array
    {
        $merged = [];
        foreach ($sourceProjectsArrays as $sourceProjects) {
            if (is_array($sourceProjects)) {
                $merged = array_merge($merged, $sourceProjects);
            }
        }

        return array_values(array_unique($merged));
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

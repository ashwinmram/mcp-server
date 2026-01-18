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
        'title',
        'summary',
        'tags',
        'metadata',
        'content',
        'content_hash',
        'is_generic',
        'relevance_score',
        'deprecated_at',
        'superseded_by_lesson_id',
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
            'relevance_score' => 'float',
            'deprecated_at' => 'datetime',
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
     * Get all related lessons for this lesson.
     */
    public function relatedLessons(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Lesson::class,
            'lesson_relationships',
            'lesson_id',
            'related_lesson_id'
        )
            ->withPivot('relationship_type', 'relevance_score')
            ->withTimestamps();
    }

    /**
     * Get lessons that are related to this lesson (reverse relationship).
     */
    public function relatedFromLessons(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Lesson::class,
            'lesson_relationships',
            'related_lesson_id',
            'lesson_id'
        )
            ->withPivot('relationship_type', 'relevance_score')
            ->withTimestamps();
    }

    /**
     * Get related lessons filtered by relationship type.
     */
    public function getRelatedLessonsByType(string $relationshipType, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->relatedLessons()
            ->wherePivot('relationship_type', $relationshipType)
            ->orderByPivot('relevance_score', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all related lessons (all types combined).
     */
    public function getAllRelatedLessons(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->relatedLessons()
            ->orderByPivot('relevance_score', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Find related lessons based on category and tag similarity.
     */
    public function findSimilarLessons(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::query()
            ->generic()
            ->where('id', '!=', $this->id);

        // Same category
        if ($this->category) {
            $query->where('category', $this->category);
        }

        // Overlapping tags
        if (! empty($this->tags) && is_array($this->tags)) {
            $query->byTags($this->tags);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get all usages for this lesson.
     */
    public function usages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LessonUsage::class);
    }

    /**
     * Get the lesson that supersedes this lesson.
     */
    public function supersededBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'superseded_by_lesson_id');
    }

    /**
     * Get lessons that are superseded by this lesson.
     */
    public function supersedes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lesson::class, 'superseded_by_lesson_id');
    }

    /**
     * Scope a query to exclude deprecated lessons.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deprecated_at');
    }

    /**
     * Scope a query to only include deprecated lessons.
     */
    public function scopeDeprecated(Builder $query): Builder
    {
        return $query->whereNotNull('deprecated_at');
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

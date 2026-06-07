<?php

namespace App\Mcp\Support;

use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class LessonQueryFilters
{
    /**
     * @param  Builder<Lesson>  $query
     */
    public static function applyCategoryFilter(
        Builder $query,
        string $category,
        bool $isProjectDetails,
        ?string $project = null,
    ): void {
        $baseQuery = $isProjectDetails
            ? Lesson::query()->projectDetails()->bySourceProject($project)
            : Lesson::query()->generic();

        $isSubcategory = str_contains($category, '-') &&
            $category !== 'lessons-learned' &&
            (clone $baseQuery)->bySubcategory($category)->exists();

        if ($isSubcategory) {
            $query->bySubcategory($category);
        } else {
            $query->byCategory($category);
        }
    }

    /**
     * @param  Builder<Lesson>  $query
     */
    public static function applyDateRange(
        Builder $query,
        ?string $since,
        ?string $until,
        ?int $days,
        string $column = 'created_at',
    ): void {
        if ($days !== null && $days > 0) {
            $query->where($column, '>=', now()->subDays($days));

            return;
        }

        if ($since) {
            $query->where($column, '>=', Carbon::parse($since));
        }

        if ($until) {
            $query->where($column, '<=', Carbon::parse($until));
        }
    }

    /**
     * @param  Builder<Lesson>  $query
     */
    public static function applySourceProjectFilter(Builder $query, string $sourceProject): void
    {
        $query->where(function (Builder $q) use ($sourceProject) {
            $q->where('source_project', $sourceProject)
                ->orWhereJsonContains('source_projects', $sourceProject);
        });
    }

    /**
     * @param  Builder<Lesson>  $query
     */
    public static function applyTagsFilter(Builder $query, ?array $tags): void
    {
        if ($tags && is_array($tags)) {
            $query->byTags($tags);
        }
    }

    /**
     * @param  Builder<Lesson>  $query
     */
    public static function applyFulltextSearch(Builder $query, string $searchQuery): bool
    {
        $fulltextQuery = clone $query;
        $fulltextQuery->whereRaw('MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$searchQuery]);
        $fulltextCount = $fulltextQuery->count();

        if ($fulltextCount > 0) {
            $query->whereRaw('MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$searchQuery]);

            return true;
        }

        $query->where('content', 'like', '%'.$searchQuery.'%');

        return false;
    }

    /**
     * @param  Builder<Lesson>  $query
     */
    public static function isUsingFulltext(Builder $query): bool
    {
        return str_contains($query->toSql(), 'MATCH');
    }

    /**
     * @param  Builder<Lesson>  $query
     */
    public static function applyOrderBy(
        Builder $query,
        ?string $orderBy,
        bool $usingFulltext,
        ?string $searchQuery,
        bool $hasRelevanceScore,
        string $defaultOrderBy = 'relevance',
        string $dateColumn = 'created_at',
    ): string {
        $resolved = $orderBy ?? $defaultOrderBy;

        if ($resolved === 'relevance') {
            if ($usingFulltext && $searchQuery) {
                if ($hasRelevanceScore) {
                    $query->selectRaw('*, MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance', [$searchQuery])
                        ->orderByRaw('(MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) * 0.7) + (COALESCE(relevance_score, 0) * 0.3) DESC', [$searchQuery])
                        ->orderBy($dateColumn, 'desc');
                } else {
                    $query->selectRaw('*, MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance', [$searchQuery])
                        ->orderByRaw('MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) DESC', [$searchQuery])
                        ->orderBy($dateColumn, 'desc');
                }
            } elseif ($hasRelevanceScore) {
                $query->orderBy('relevance_score', 'desc')
                    ->orderBy($dateColumn, 'desc');
            } else {
                $query->orderBy($dateColumn, 'desc');
                $resolved = $dateColumn;
            }

            return $resolved;
        }

        if ($resolved === 'updated_at') {
            $query->orderBy('updated_at', 'desc');

            return 'updated_at';
        }

        $query->orderBy('created_at', 'desc');

        return 'created_at';
    }

    public static function hasRelevanceScoreColumn(): bool
    {
        return Schema::hasColumn('lessons', 'relevance_score');
    }
}

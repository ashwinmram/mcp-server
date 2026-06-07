<?php

namespace App\Mcp\Tools;

use App\Models\Lesson;
use App\Models\LessonUsage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetProjectDetailsCategoryStatistics extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get statistics about project detail categories for the current project including counts, relevance scores, and usage patterns.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $project = app('mcp.project');
        $category = $request->get('category');
        $includeTopLessons = (bool) $request->get('include_top_lessons', true);
        $topLessonsLimit = (int) ($request->get('top_lessons_limit', 5));

        $hasRelevanceScore = Schema::hasColumn('lessons', 'relevance_score');
        $hasUsageTracking = Schema::hasTable('lesson_usages');

        if ($category) {
            return $this->getCategoryStats($project, $category, $includeTopLessons, $topLessonsLimit, $hasRelevanceScore, $hasUsageTracking);
        }

        return $this->getAllCategoriesStats($project, $includeTopLessons, $topLessonsLimit, $hasRelevanceScore, $hasUsageTracking);
    }

    protected function getCategoryStats(
        string $project,
        string $category,
        bool $includeTopLessons,
        int $topLessonsLimit,
        bool $hasRelevanceScore,
        bool $hasUsageTracking
    ): Response {
        $isSubcategory = str_contains($category, '-') &&
            Lesson::query()->projectDetails()->bySourceProject($project)->bySubcategory($category)->exists();

        $query = Lesson::query()->projectDetails()->bySourceProject($project)->active();

        if ($isSubcategory) {
            $query->bySubcategory($category);
        } else {
            $query->byCategory($category);
        }

        $totalLessons = $query->count();

        if ($totalLessons === 0) {
            return Response::error("Category '{$category}' not found or has no project details");
        }

        $stats = [
            'project' => $project,
            'category' => $category,
            'is_subcategory' => $isSubcategory,
            'total_details' => $totalLessons,
        ];

        if ($hasRelevanceScore) {
            $relevanceStats = $query->clone()
                ->selectRaw('
                    AVG(relevance_score) as avg_relevance_score,
                    MAX(relevance_score) as max_relevance_score,
                    MIN(relevance_score) as min_relevance_score
                ')
                ->first();

            $stats['relevance_score'] = [
                'average' => round((float) $relevanceStats->avg_relevance_score, 4),
                'maximum' => round((float) $relevanceStats->max_relevance_score, 4),
                'minimum' => round((float) $relevanceStats->min_relevance_score, 4),
            ];
        }

        if ($hasUsageTracking) {
            $lessonIds = $query->clone()->pluck('id');
            $usageStats = LessonUsage::whereIn('lesson_id', $lessonIds)
                ->selectRaw('
                    COUNT(*) as total_usages,
                    COUNT(DISTINCT lesson_id) as lessons_with_usage,
                    SUM(CASE WHEN was_helpful = 1 THEN 1 ELSE 0 END) as helpful_count,
                    SUM(CASE WHEN was_helpful = 0 THEN 1 ELSE 0 END) as not_helpful_count
                ')
                ->first();

            $totalUsages = (int) $usageStats->total_usages;
            $helpfulCount = (int) $usageStats->helpful_count;
            $helpfulnessRate = $totalUsages > 0 ? round(($helpfulCount / $totalUsages) * 100, 2) : 0.0;

            $stats['usage'] = [
                'total_usages' => $totalUsages,
                'lessons_with_usage' => (int) $usageStats->lessons_with_usage,
                'helpfulness_rate' => $helpfulnessRate,
                'helpful_count' => $helpfulCount,
                'not_helpful_count' => (int) $usageStats->not_helpful_count,
            ];
        }

        if ($includeTopLessons) {
            $topLessonsQuery = $query->clone();

            if ($hasRelevanceScore) {
                $topLessonsQuery->orderBy('relevance_score', 'desc');
            }

            $topLessons = $topLessonsQuery->orderBy('updated_at', 'desc')
                ->limit($topLessonsLimit)
                ->get(['id', 'title', 'category', 'subcategory', 'relevance_score']);

            $stats['top_details'] = $topLessons->map(function (Lesson $lesson) {
                return [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'category' => $lesson->category,
                    'subcategory' => $lesson->subcategory,
                    'relevance_score' => $lesson->relevance_score ?? null,
                ];
            })->toArray();
        }

        return Response::json($stats);
    }

    protected function getAllCategoriesStats(
        string $project,
        bool $includeTopLessons,
        int $topLessonsLimit,
        bool $hasRelevanceScore,
        bool $hasUsageTracking
    ): Response {
        $categories = Lesson::query()
            ->projectDetails()
            ->bySourceProject($project)
            ->active()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        $categoryStats = [];

        foreach ($categories as $category) {
            $query = Lesson::query()->projectDetails()->bySourceProject($project)->active()->byCategory($category);
            $totalLessons = $query->count();

            $categoryData = [
                'category' => $category,
                'total_details' => $totalLessons,
            ];

            if ($hasRelevanceScore) {
                $relevanceStats = $query->clone()
                    ->selectRaw('AVG(relevance_score) as avg_relevance_score, MAX(relevance_score) as max_relevance_score')
                    ->first();

                $categoryData['avg_relevance_score'] = round((float) $relevanceStats->avg_relevance_score, 4);
                $categoryData['max_relevance_score'] = round((float) $relevanceStats->max_relevance_score, 4);
            }

            if ($hasUsageTracking) {
                $lessonIds = $query->clone()->pluck('id');
                $usageStats = LessonUsage::whereIn('lesson_id', $lessonIds)
                    ->selectRaw('COUNT(*) as total_usages, SUM(CASE WHEN was_helpful = 1 THEN 1 ELSE 0 END) as helpful_count')
                    ->first();

                $totalUsages = (int) $usageStats->total_usages;
                $helpfulCount = (int) $usageStats->helpful_count;
                $helpfulnessRate = $totalUsages > 0 ? round(($helpfulCount / $totalUsages) * 100, 2) : 0.0;

                $categoryData['total_usages'] = $totalUsages;
                $categoryData['helpfulness_rate'] = $helpfulnessRate;
            }

            if ($includeTopLessons) {
                $topLessonQuery = $query->clone();

                if ($hasRelevanceScore) {
                    $topLessonQuery->orderBy('relevance_score', 'desc');
                }

                $topLesson = $topLessonQuery->orderBy('updated_at', 'desc')
                    ->limit(1)
                    ->first(['id', 'title', 'relevance_score']);

                if ($topLesson) {
                    $categoryData['top_detail'] = [
                        'id' => $topLesson->id,
                        'title' => $topLesson->title,
                        'relevance_score' => $topLesson->relevance_score ?? null,
                    ];
                }
            }

            $categoryStats[] = $categoryData;
        }

        if ($hasRelevanceScore) {
            usort($categoryStats, fn ($a, $b) => ($b['avg_relevance_score'] ?? 0) <=> ($a['avg_relevance_score'] ?? 0));
        } else {
            usort($categoryStats, fn ($a, $b) => $b['total_details'] <=> $a['total_details']);
        }

        return Response::json([
            'project' => $project,
            'categories' => $categoryStats,
            'total_categories' => count($categoryStats),
            'ordered_by' => $hasRelevanceScore ? 'avg_relevance_score' : 'total_details',
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string()->nullable()->description('Category to get statistics for (optional — returns all categories if omitted)'),
            'include_top_lessons' => $schema->boolean()->default(true)->description('Whether to include top details in the response'),
            'top_lessons_limit' => $schema->integer()->default(5)->description('Number of top details to include per category'),
        ];
    }
}

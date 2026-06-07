<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\LessonHelpfulnessRecorder;
use App\Mcp\Support\LessonPresenter;
use App\Mcp\Support\LessonQueryFilters;
use App\Models\Lesson;
use App\Models\LessonUsage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SearchLessons extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Search for lessons learned by keyword, category, or tags. Returns matching lessons with their content. Default sort is relevance — use GetRecentLessons for chronological queries or set order_by to created_at.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $query = Lesson::query()->generic();
        $searchQuery = $request->get('query');
        $includeRelated = (bool) $request->get('include_related', false);
        $includeDeprecated = (bool) $request->get('include_deprecated', false);

        if (! $includeDeprecated) {
            $query->active();
        }

        if ($searchQuery) {
            LessonQueryFilters::applyFulltextSearch($query, $searchQuery);
        }

        if ($request->get('category')) {
            LessonQueryFilters::applyCategoryFilter(
                $query,
                $request->get('category'),
                false,
            );
        }

        LessonQueryFilters::applyTagsFilter($query, $request->get('tags'));

        if ($request->get('source_project')) {
            LessonQueryFilters::applySourceProjectFilter($query, $request->get('source_project'));
        }

        LessonQueryFilters::applyDateRange(
            $query,
            $request->get('since'),
            $request->get('until'),
            $request->get('days') !== null ? (int) $request->get('days') : null,
        );

        $limit = (int) ($request->get('limit', 10));
        $hasRelevanceScore = LessonQueryFilters::hasRelevanceScoreColumn();
        $usingFulltext = $searchQuery ? LessonQueryFilters::isUsingFulltext($query) : false;

        $orderedBy = LessonQueryFilters::applyOrderBy(
            $query,
            $request->get('order_by'),
            $usingFulltext,
            $searchQuery,
            $hasRelevanceScore,
            'relevance',
            'created_at',
        );

        $lessons = $query->limit($limit)->get();

        $this->trackUsage($lessons, $searchQuery, $request);

        $results = $lessons->map(
            fn (Lesson $lesson) => LessonPresenter::toGenericArray($lesson, $includeRelated)
        )->toArray();

        return Response::json([
            'results' => $results,
            'count' => count($results),
            'ordered_by' => $orderedBy,
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->nullable()->description('Search keyword to find in lesson content (uses MySQL FULLTEXT search)'),
            'category' => $schema->string()->nullable()->description('Filter lessons by category'),
            'tags' => $schema->array()->nullable()->description('Array of tags to filter by'),
            'limit' => $schema->integer()->default(10)->description('Maximum number of results to return'),
            'include_related' => $schema->boolean()->default(false)->description('Whether to include related lessons in the response'),
            'include_deprecated' => $schema->boolean()->default(false)->description('Whether to include deprecated lessons in the response'),
            'order_by' => $schema->string()->nullable()->description('Sort order: relevance (default), created_at, or updated_at'),
            'since' => $schema->string()->nullable()->description('ISO date — return lessons created on or after this date'),
            'until' => $schema->string()->nullable()->description('ISO date — return lessons created on or before this date'),
            'days' => $schema->integer()->nullable()->description('Shorthand for since = now minus N days'),
            'source_project' => $schema->string()->nullable()->description('Filter to lessons originating from a specific project'),
        ];
    }

    protected function trackUsage($lessons, ?string $searchQuery, Request $request): void
    {
        if (! Schema::hasTable('lesson_usages')) {
            return;
        }

        $queryContext = $this->buildQueryContext($request, $searchQuery);
        $sessionId = LessonHelpfulnessRecorder::getSessionId($request);

        foreach ($lessons as $lesson) {
            try {
                LessonUsage::create([
                    'lesson_id' => $lesson->id,
                    'query_context' => $queryContext,
                    'was_helpful' => null,
                    'session_id' => $sessionId,
                ]);
            } catch (\Exception $e) {
                if (config('app.debug')) {
                    \Log::warning('Failed to track lesson usage', [
                        'lesson_id' => $lesson->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    protected function buildQueryContext(Request $request, ?string $searchQuery): ?string
    {
        $parts = [];

        if ($searchQuery) {
            $parts[] = "query: {$searchQuery}";
        }

        if ($request->get('category')) {
            $parts[] = "category: {$request->get('category')}";
        }

        if ($request->get('tags') && is_array($request->get('tags'))) {
            $parts[] = 'tags: '.implode(', ', $request->get('tags'));
        }

        if ($request->get('source_project')) {
            $parts[] = "source_project: {$request->get('source_project')}";
        }

        return ! empty($parts) ? implode(' | ', $parts) : null;
    }
}

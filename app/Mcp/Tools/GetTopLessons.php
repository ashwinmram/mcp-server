<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\LessonPresenter;
use App\Mcp\Support\LessonQueryFilters;
use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetTopLessons extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get the most relevant generic lessons by relevance score (usage, helpfulness, recency). This is NOT chronological — use GetRecentLessons for latest entries.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $query = Lesson::query()->generic()->active();

        if ($request->get('category')) {
            LessonQueryFilters::applyCategoryFilter(
                $query,
                $request->get('category'),
                false,
            );
        }

        $limit = (int) ($request->get('limit', 10));
        $hasRelevanceScore = LessonQueryFilters::hasRelevanceScoreColumn();

        if ($hasRelevanceScore) {
            $query->orderBy('relevance_score', 'desc');
        }

        $query->orderBy('created_at', 'desc');
        $lessons = $query->limit($limit)->get();

        $results = $lessons->map(
            fn (Lesson $lesson) => LessonPresenter::toTopLessonArray($lesson)
        )->toArray();

        return Response::json([
            'category' => $request->get('category'),
            'lessons' => $results,
            'count' => count($results),
            'ordered_by' => $hasRelevanceScore ? 'relevance_score' : 'created_at',
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string()->nullable()->description('Filter by category or subcategory (optional)'),
            'limit' => $schema->integer()->default(10)->description('Maximum number of lessons to return'),
        ];
    }
}

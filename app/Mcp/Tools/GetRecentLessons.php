<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\LessonPresenter;
use App\Mcp\Support\LessonQueryFilters;
use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetRecentLessons extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get the most recently created generic lessons in chronological order. Use this for "latest lesson" queries — not SearchLessons (relevance-ranked) or GetTopLessons (most valuable).
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $query = Lesson::query()->generic();
        $includeDeprecated = (bool) $request->get('include_deprecated', false);

        if (! $includeDeprecated) {
            $query->active();
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

        $orderBy = $request->get('order_by', 'created_at');
        $dateColumn = $orderBy === 'updated_at' ? 'updated_at' : 'created_at';

        LessonQueryFilters::applyDateRange(
            $query,
            $request->get('since'),
            $request->get('until'),
            $request->get('days') !== null ? (int) $request->get('days') : null,
            $dateColumn,
        );

        $limit = (int) ($request->get('limit', 10));

        $query->orderBy($dateColumn, 'desc');
        $lessons = $query->limit($limit)->get();

        $results = $lessons->map(fn (Lesson $lesson) => LessonPresenter::toGenericArray($lesson))->toArray();

        return Response::json([
            'results' => $results,
            'count' => count($results),
            'ordered_by' => $dateColumn,
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->default(10)->description('Maximum number of results to return'),
            'since' => $schema->string()->nullable()->description('ISO date — return lessons created/updated on or after this date'),
            'until' => $schema->string()->nullable()->description('ISO date — return lessons created/updated on or before this date'),
            'days' => $schema->integer()->nullable()->description('Shorthand for since = now minus N days'),
            'category' => $schema->string()->nullable()->description('Filter by category or subcategory'),
            'tags' => $schema->array()->nullable()->description('Array of tags to filter by'),
            'source_project' => $schema->string()->nullable()->description('Filter to lessons originating from a specific project'),
            'order_by' => $schema->string()->default('created_at')->description('Sort column: created_at (default) or updated_at'),
            'include_deprecated' => $schema->boolean()->default(false)->description('Whether to include deprecated lessons'),
        ];
    }
}

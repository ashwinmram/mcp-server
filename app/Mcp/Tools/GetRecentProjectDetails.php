<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\LessonPresenter;
use App\Mcp\Support\LessonQueryFilters;
use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetRecentProjectDetails extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get the most recently created or updated project-specific details for the current project in chronological order. Use for "latest project detail" queries.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $project = app('mcp.project');
        $query = Lesson::query()->projectDetails()->bySourceProject($project);
        $includeDeprecated = (bool) $request->get('include_deprecated', false);

        if (! $includeDeprecated) {
            $query->active();
        }

        if ($request->get('category')) {
            LessonQueryFilters::applyCategoryFilter(
                $query,
                $request->get('category'),
                true,
                $project,
            );
        }

        LessonQueryFilters::applyTagsFilter($query, $request->get('tags'));

        $orderBy = $request->get('order_by', 'updated_at');
        $dateColumn = $orderBy === 'created_at' ? 'created_at' : 'updated_at';

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

        $results = $lessons->map(fn (Lesson $lesson) => LessonPresenter::toProjectDetailArray($lesson))->toArray();

        return Response::json([
            'project' => $project,
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
            'since' => $schema->string()->nullable()->description('ISO date — return details created/updated on or after this date'),
            'until' => $schema->string()->nullable()->description('ISO date — return details created/updated on or before this date'),
            'days' => $schema->integer()->nullable()->description('Shorthand for since = now minus N days'),
            'category' => $schema->string()->nullable()->description('Filter by category or subcategory'),
            'tags' => $schema->array()->nullable()->description('Array of tags to filter by'),
            'order_by' => $schema->string()->default('updated_at')->description('Sort column: updated_at (default) or created_at'),
            'include_deprecated' => $schema->boolean()->default(false)->description('Whether to include deprecated entries'),
        ];
    }
}

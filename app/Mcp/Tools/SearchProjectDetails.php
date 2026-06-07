<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\LessonPresenter;
use App\Mcp\Support\LessonQueryFilters;
use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SearchProjectDetails extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Search project-specific implementation details by keyword, category, or tags. Returns matching details for the current project only. Use GetRecentProjectDetails for chronological queries.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $project = app('mcp.project');
        $query = Lesson::query()
            ->projectDetails()
            ->bySourceProject($project);

        $searchQuery = $request->get('query');
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
                true,
                $project,
            );
        }

        LessonQueryFilters::applyTagsFilter($query, $request->get('tags'));

        LessonQueryFilters::applyDateRange(
            $query,
            $request->get('since'),
            $request->get('until'),
            $request->get('days') !== null ? (int) $request->get('days') : null,
            'updated_at',
        );

        $limit = (int) ($request->get('limit', 10));
        $usingFulltext = $searchQuery ? LessonQueryFilters::isUsingFulltext($query) : false;
        $defaultOrderBy = $searchQuery ? 'relevance' : 'updated_at';

        $orderedBy = LessonQueryFilters::applyOrderBy(
            $query,
            $request->get('order_by'),
            $usingFulltext,
            $searchQuery,
            false,
            $defaultOrderBy,
            'updated_at',
        );

        $lessons = $query->limit($limit)->get();

        $results = $lessons->map(
            fn (Lesson $lesson) => LessonPresenter::toProjectDetailArray($lesson)
        )->toArray();

        return Response::json([
            'project' => $project,
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
            'query' => $schema->string()->nullable()->description('Search keyword to find in project details content'),
            'category' => $schema->string()->nullable()->description('Filter by category'),
            'tags' => $schema->array()->nullable()->description('Array of tags to filter by'),
            'limit' => $schema->integer()->default(10)->description('Maximum number of results to return'),
            'include_deprecated' => $schema->boolean()->default(false)->description('Whether to include deprecated entries'),
            'order_by' => $schema->string()->nullable()->description('Sort order: updated_at (default when browsing), relevance (when searching), or created_at'),
            'since' => $schema->string()->nullable()->description('ISO date — return details updated on or after this date'),
            'until' => $schema->string()->nullable()->description('ISO date — return details updated on or before this date'),
            'days' => $schema->integer()->nullable()->description('Shorthand for since = now minus N days'),
        ];
    }
}

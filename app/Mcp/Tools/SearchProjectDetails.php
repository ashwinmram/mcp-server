<?php

namespace App\Mcp\Tools;

use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SearchProjectDetails extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Search project-specific implementation details by keyword, category, or tags. Returns matching details for the current project only.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
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
            $fulltextQuery = clone $query;
            $fulltextQuery->whereRaw('MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$searchQuery]);
            $fulltextCount = $fulltextQuery->count();

            if ($fulltextCount > 0) {
                $query->whereRaw('MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$searchQuery]);
            } else {
                $query->where('content', 'like', '%'.$searchQuery.'%');
            }
        }

        if ($request->get('category')) {
            $category = $request->get('category');
            $isSubcategory = str_contains($category, '-') &&
                Lesson::query()->projectDetails()->bySourceProject($project)->bySubcategory($category)->exists();

            if ($isSubcategory) {
                $query->bySubcategory($category);
            } else {
                $query->byCategory($category);
            }
        }

        if ($request->get('tags') && is_array($request->get('tags'))) {
            $query->byTags($request->get('tags'));
        }

        $limit = (int) ($request->get('limit', 10));

        if ($searchQuery) {
            $queryString = $query->toSql();
            $usingFulltext = str_contains($queryString, 'MATCH');

            if ($usingFulltext) {
                $query->selectRaw('*, MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance', [$searchQuery])
                    ->orderByRaw('MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) DESC', [$searchQuery])
                    ->orderBy('updated_at', 'desc');
            } else {
                $query->orderBy('updated_at', 'desc');
            }
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        $lessons = $query->limit($limit)->get();

        $results = $lessons->map(function (Lesson $lesson) {
            return [
                'id' => $lesson->id,
                'type' => $lesson->type,
                'category' => $lesson->category,
                'subcategory' => $lesson->subcategory,
                'title' => $lesson->title,
                'summary' => $lesson->summary,
                'tags' => $lesson->tags,
                'content' => $lesson->content,
                'source_project' => $lesson->source_project,
                'created_at' => $lesson->created_at->toIso8601String(),
                'updated_at' => $lesson->updated_at->toIso8601String(),
            ];
        })->toArray();

        return Response::json([
            'project' => $project,
            'results' => $results,
            'count' => count($results),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->nullable()->description('Search keyword to find in project details content'),
            'category' => $schema->string()->nullable()->description('Filter by category'),
            'tags' => $schema->array()->nullable()->description('Array of tags to filter by'),
            'limit' => $schema->integer()->default(10)->description('Maximum number of results to return'),
            'include_deprecated' => $schema->boolean()->default(false)->description('Whether to include deprecated entries'),
        ];
    }
}

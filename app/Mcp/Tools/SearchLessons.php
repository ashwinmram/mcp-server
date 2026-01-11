<?php

namespace App\Mcp\Tools;

use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SearchLessons extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Search for lessons learned by keyword, category, or tags. Returns matching lessons with their content.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $query = Lesson::query()->generic();

        // Search by keyword in content
        if ($request->get('query')) {
            $query->where('content', 'like', '%'.$request->get('query').'%');
        }

        // Filter by category or subcategory
        if ($request->get('category')) {
            $category = $request->get('category');
            // Check if it's a subcategory
            $isSubcategory = str_contains($category, '-') &&
                             $category !== 'lessons-learned' &&
                             Lesson::query()->generic()->bySubcategory($category)->exists();

            if ($isSubcategory) {
                $query->bySubcategory($category);
            } else {
                $query->byCategory($category);
            }
        }

        // Filter by tags
        if ($request->get('tags') && is_array($request->get('tags'))) {
            $query->byTags($request->get('tags'));
        }

        $limit = (int) ($request->get('limit', 10));
        $lessons = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $results = $lessons->map(function (Lesson $lesson) {
            return [
                'id' => $lesson->id,
                'type' => $lesson->type,
                'category' => $lesson->category,
                'subcategory' => $lesson->subcategory,
                'tags' => $lesson->tags,
                'content' => $lesson->content,
                'source_project' => $lesson->source_project, // Keep for backward compatibility
                'source_projects' => $lesson->source_projects ?? [$lesson->source_project],
                'created_at' => $lesson->created_at->toIso8601String(),
            ];
        })->toArray();

        return Response::json([
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
            'query' => $schema->string()->nullable()->description('Search keyword to find in lesson content'),
            'category' => $schema->string()->nullable()->description('Filter lessons by category'),
            'tags' => $schema->array()->nullable()->description('Array of tags to filter by'),
            'limit' => $schema->integer()->default(10)->description('Maximum number of results to return'),
        ];
    }
}

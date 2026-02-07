<?php

namespace App\Mcp\Tools;

use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetProjectDetailsByCategory extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get all project-specific implementation details in a specific category for the current project.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $project = app('mcp.project');
        $category = $request->get('category');

        if (empty($category)) {
            return Response::error('Category is required');
        }

        $limit = (int) ($request->get('limit', 10));

        $baseQuery = Lesson::query()->projectDetails()->bySourceProject($project);
        $isSubcategory = str_contains($category, '-') &&
            (clone $baseQuery)->bySubcategory($category)->exists();

        if ($isSubcategory) {
            $lessons = (clone $baseQuery)
                ->bySubcategory($category)
                ->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get();
        } else {
            $lessons = (clone $baseQuery)
                ->byCategory($category)
                ->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get();
        }

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
            'category' => $category,
            'lessons' => $results,
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
            'category' => $schema->string()->required()->description('Category name to filter project details by'),
            'limit' => $schema->integer()->default(10)->description('Maximum number of entries to return'),
        ];
    }
}

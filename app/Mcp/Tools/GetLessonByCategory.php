<?php

namespace App\Mcp\Tools;

use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetLessonByCategory extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get all lessons in a specific category. Useful for finding lessons related to a particular topic like validation, routing, security, etc.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $category = $request->get('category');

        if (empty($category)) {
            return Response::error('Category is required');
        }

        $limit = (int) ($request->get('limit', 10));

        $lessons = Lesson::query()
            ->generic()
            ->byCategory($category)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $results = $lessons->map(function (Lesson $lesson) {
            return [
                'id' => $lesson->id,
                'type' => $lesson->type,
                'category' => $lesson->category,
                'tags' => $lesson->tags,
                'content' => $lesson->content,
                'source_project' => $lesson->source_project, // Keep for backward compatibility
                'source_projects' => $lesson->source_projects ?? [$lesson->source_project],
                'created_at' => $lesson->created_at->toIso8601String(),
            ];
        })->toArray();

        return Response::json([
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
            'category' => $schema->string()->required()->description('Category name to filter lessons by (e.g., validation, routing, security)'),
            'limit' => $schema->integer()->default(10)->description('Maximum number of lessons to return'),
        ];
    }
}

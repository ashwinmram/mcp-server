<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\LessonPresenter;
use App\Mcp\Support\LessonQueryFilters;
use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetLessonByCategory extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get all lessons in a specific category. Useful for finding lessons related to a particular topic like validation, routing, security, etc.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $category = $request->get('category');

        if (empty($category)) {
            return Response::error('Category is required');
        }

        $limit = (int) ($request->get('limit', 10));
        $query = Lesson::query()->generic();

        LessonQueryFilters::applyCategoryFilter($query, $category, false);

        $lessons = $query
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $results = $lessons->map(
            fn (Lesson $lesson) => LessonPresenter::toGenericArray($lesson)
        )->toArray();

        return Response::json([
            'category' => $category,
            'lessons' => $results,
            'count' => count($results),
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string()->required()->description('Category name to filter lessons by (e.g., validation, routing, security)'),
            'limit' => $schema->integer()->default(10)->description('Maximum number of lessons to return'),
        ];
    }
}

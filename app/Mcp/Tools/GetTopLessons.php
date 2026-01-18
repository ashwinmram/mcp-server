<?php

namespace App\Mcp\Tools;

use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetTopLessons extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get lessons with the highest relevance scores. Optionally filter by category. This surfaces the most valuable lessons based on usage frequency, helpfulness rate, and recency. Perfect for finding the best lessons in a category or overall.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $query = Lesson::query()->generic()->active();

        // Filter by category if provided
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

        $limit = (int) ($request->get('limit', 10));

        // Check if relevance_score column exists (Phase 3 feature)
        $hasRelevanceScore = Schema::hasColumn('lessons', 'relevance_score');

        // Order by relevance score (highest first) if available, then by date
        if ($hasRelevanceScore) {
            $query->orderBy('relevance_score', 'desc');
        }

        $query->orderBy('created_at', 'desc');
        $lessons = $query->limit($limit)->get();

        $results = $lessons->map(function (Lesson $lesson) {
            $result = [
                'id' => $lesson->id,
                'type' => $lesson->type,
                'category' => $lesson->category,
                'subcategory' => $lesson->subcategory,
                'title' => $lesson->title,
                'summary' => $lesson->summary,
                'tags' => $lesson->tags,
                'content' => $lesson->content,
                'source_project' => $lesson->source_project,
                'source_projects' => $lesson->source_projects ?? [$lesson->source_project],
                'created_at' => $lesson->created_at->toIso8601String(),
            ];

            // Include relevance score if available
            if (Schema::hasColumn('lessons', 'relevance_score')) {
                $result['relevance_score'] = $lesson->relevance_score ?? 0.0;
            }

            return $result;
        })->toArray();

        return Response::json([
            'category' => $request->get('category'),
            'lessons' => $results,
            'count' => count($results),
            'ordered_by' => $hasRelevanceScore ? 'relevance_score' : 'created_at',
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
            'category' => $schema->string()->nullable()->description('Filter by category or subcategory (optional)'),
            'limit' => $schema->integer()->default(10)->description('Maximum number of lessons to return'),
        ];
    }
}

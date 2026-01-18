<?php

namespace App\Mcp\Tools;

use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class FindRelatedLessons extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Find related lessons for a specific lesson. Returns lessons that are related through the lesson_relationships table, including relationship type and relevance score.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $lessonId = $request->get('lesson_id');
        $relationshipType = $request->get('relationship_type');
        $limit = (int) ($request->get('limit', 10));

        if (empty($lessonId)) {
            return Response::error('lesson_id is required');
        }

        $lesson = Lesson::find($lessonId);

        if (! $lesson) {
            return Response::error('Lesson not found');
        }

        // Get related lessons
        if ($relationshipType) {
            $relatedLessons = $lesson->getRelatedLessonsByType($relationshipType, $limit);
        } else {
            $relatedLessons = $lesson->getAllRelatedLessons($limit);
        }

        $results = $relatedLessons->map(function (Lesson $related) {
            return [
                'id' => $related->id,
                'type' => $related->type,
                'category' => $related->category,
                'subcategory' => $related->subcategory,
                'title' => $related->title,
                'summary' => $related->summary,
                'tags' => $related->tags,
                'content' => $related->content,
                'relationship_type' => $related->pivot->relationship_type ?? 'related',
                'relevance_score' => $related->pivot->relevance_score ?? null,
                'source_project' => $related->source_project,
                'source_projects' => $related->source_projects ?? [$related->source_project],
                'created_at' => $related->created_at->toIso8601String(),
            ];
        })->toArray();

        return Response::json([
            'lesson_id' => $lessonId,
            'relationship_type' => $relationshipType,
            'related_lessons' => $results,
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
            'lesson_id' => $schema->string()->required()->description('UUID of the lesson to find related lessons for'),
            'relationship_type' => $schema->string()->nullable()->description('Filter by relationship type: prerequisite, related, alternative, or supersedes'),
            'limit' => $schema->integer()->default(10)->description('Maximum number of related lessons to return'),
        ];
    }
}

<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\LessonPresenter;
use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class FindRelatedProjectDetails extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Find related project details for a specific detail entry in the current project. Returns lessons related through the lesson_relationships table.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $project = app('mcp.project');
        $lessonId = $request->get('lesson_id');
        $relationshipType = $request->get('relationship_type');
        $limit = (int) ($request->get('limit', 10));

        if (empty($lessonId)) {
            return Response::error('lesson_id is required');
        }

        $lesson = Lesson::query()
            ->projectDetails()
            ->bySourceProject($project)
            ->where('id', $lessonId)
            ->first();

        if (! $lesson) {
            return Response::error('Project detail not found');
        }

        if ($relationshipType) {
            $relatedLessons = $lesson->getRelatedLessonsByType($relationshipType, $limit);
        } else {
            $relatedLessons = $lesson->getAllRelatedLessons($limit);
        }

        $relatedLessons = $relatedLessons->filter(function (Lesson $related) use ($project) {
            return ! $related->is_generic && $related->source_project === $project;
        });

        $results = $relatedLessons->map(fn (Lesson $related) => LessonPresenter::toRelatedArray($related))->values()->toArray();

        return Response::json([
            'project' => $project,
            'lesson_id' => $lessonId,
            'relationship_type' => $relationshipType,
            'related_details' => $results,
            'count' => count($results),
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'lesson_id' => $schema->string()->required()->description('UUID of the project detail to find related entries for'),
            'relationship_type' => $schema->string()->nullable()->description('Filter by relationship type: prerequisite, related, alternative, or supersedes'),
            'limit' => $schema->integer()->default(10)->description('Maximum number of related details to return'),
        ];
    }
}

<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\LessonHelpfulnessRecorder;
use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class MarkProjectDetailHelpful extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Mark a project detail as helpful or not helpful. Provides explicit feedback that improves relevance scoring for project-specific knowledge.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $project = app('mcp.project');
        $lessonId = $request->get('lesson_id');
        $wasHelpful = (bool) $request->get('was_helpful', true);

        if (empty($lessonId)) {
            return Response::error('lesson_id is required');
        }

        if (! Schema::hasTable('lesson_usages')) {
            return Response::error('Usage tracking is not available. Please run migrations first.');
        }

        $lesson = Lesson::query()
            ->projectDetails()
            ->bySourceProject($project)
            ->where('id', $lessonId)
            ->first();

        if (! $lesson) {
            return Response::error('Project detail not found');
        }

        try {
            $result = LessonHelpfulnessRecorder::record($lessonId, $wasHelpful, $request);

            if ($result === null) {
                return Response::error('Usage tracking is not available. Please run migrations first.');
            }

            return Response::json(array_merge($result, ['project' => $project]));
        } catch (\Exception $e) {
            return Response::error('Failed to mark project detail: '.$e->getMessage());
        }
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'lesson_id' => $schema->string()->required()->description('UUID of the project detail to mark'),
            'was_helpful' => $schema->boolean()->default(true)->description('Whether the detail was helpful (default: true)'),
        ];
    }
}

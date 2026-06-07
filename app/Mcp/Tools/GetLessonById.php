<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\LessonPresenter;
use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetLessonById extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Fetch a single generic lesson by UUID. Use after seeing a truncated snippet in lessons://overview or lessons://recent.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $lessonId = $request->get('lesson_id');
        $includeDeprecated = (bool) $request->get('include_deprecated', false);

        if (empty($lessonId)) {
            return Response::error('lesson_id is required');
        }

        $query = Lesson::query()->generic()->where('id', $lessonId);

        if (! $includeDeprecated) {
            $query->active();
        }

        $lesson = $query->first();

        if (! $lesson) {
            return Response::error('Lesson not found');
        }

        return Response::json([
            'lesson' => LessonPresenter::toGenericArray($lesson),
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'lesson_id' => $schema->string()->required()->description('UUID of the generic lesson to fetch'),
            'include_deprecated' => $schema->boolean()->default(false)->description('Whether to include deprecated lessons'),
        ];
    }
}

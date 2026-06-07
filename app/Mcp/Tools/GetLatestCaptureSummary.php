<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\LessonPresenter;
use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetLatestCaptureSummary extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Returns the latest generic lesson and (when project context is available) the latest project detail for that project. Includes captured_together when both were pushed in the same batch.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $project = app()->bound('mcp.project')
            ? app('mcp.project')
            : $request->get('source_project');

        $latestGeneric = Lesson::query()
            ->generic()
            ->active()
            ->orderBy('created_at', 'desc')
            ->first();

        $latestProjectDetail = null;

        if ($project) {
            $latestProjectDetail = Lesson::query()
                ->projectDetails()
                ->bySourceProject($project)
                ->active()
                ->orderBy('created_at', 'desc')
                ->first();
        }

        $capturedTogether = false;

        if ($latestGeneric && $latestProjectDetail) {
            $capturedTogether = abs(
                $latestGeneric->created_at->diffInSeconds($latestProjectDetail->created_at)
            ) <= 1;
        }

        return Response::json([
            'source_project' => $project,
            'latest_generic' => $latestGeneric
                ? LessonPresenter::toSummaryArray($latestGeneric)
                : null,
            'latest_project_detail' => $latestProjectDetail
                ? LessonPresenter::toSummaryArray($latestProjectDetail)
                : null,
            'captured_together' => $capturedTogether,
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'source_project' => $schema->string()->nullable()->description('Project slug for latest project detail (required on Lessons server when no mcp.project binding; auto on Project Details server)'),
        ];
    }
}

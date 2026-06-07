<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\LessonPresenter;
use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetProjectDetailsOverview extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Get an overview of project-specific implementation details for the current project including counts by category and recent entries.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $project = app('mcp.project');

        $baseQuery = fn () => Lesson::query()
            ->projectDetails()
            ->bySourceProject($project)
            ->active();

        $total = $baseQuery()->count();

        $byCategory = $baseQuery()
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->category => $row->count])
            ->toArray();

        $recentDetails = $baseQuery()
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $latestUpdated = $recentDetails->first()?->updated_at;

        return Response::json([
            'project' => $project,
            'total_entries' => $total,
            'by_category' => $byCategory,
            'recent_entries' => $recentDetails->map(
                fn (Lesson $lesson) => LessonPresenter::toSummaryArray($lesson)
            )->toArray(),
            'latest_updated_at' => $latestUpdated?->toIso8601String(),
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}

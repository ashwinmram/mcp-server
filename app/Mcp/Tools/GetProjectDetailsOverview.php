<?php

namespace App\Mcp\Tools;

use App\Models\Lesson;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetProjectDetailsOverview extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get a short overview of project-specific implementation details for the current project (counts by category).
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $project = app('mcp.project');

        $total = Lesson::query()
            ->projectDetails()
            ->bySourceProject($project)
            ->active()
            ->count();

        $byCategory = Lesson::query()
            ->projectDetails()
            ->bySourceProject($project)
            ->active()
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->category => $row->count])
            ->toArray();

        return Response::json([
            'project' => $project,
            'total_entries' => $total,
            'by_category' => $byCategory,
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [];
    }
}

<?php

namespace App\Mcp\Prompts;

use App\Models\Lesson;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;

class ProjectDetailsOverview extends Prompt
{
    /**
     * The prompt's description.
     */
    protected string $description = <<<'MARKDOWN'
        Quick summary of project-specific implementation details for the current project. Use at session start to see what knowledge is available without opening the overview resource.
    MARKDOWN;

    /**
     * Handle the prompt request.
     */
    public function handle(Request $request): Response
    {
        $project = $request->get('project') ?? app('mcp.project');

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
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'type', 'category', 'created_at']);

        $content = "## Project Details Overview\n\n";
        $content .= "Project: **{$project}**\n\n";
        $content .= "Total project details: {$total}\n\n";

        if (! empty($byCategory)) {
            $content .= "### By Category\n\n";
            foreach ($byCategory as $category => $count) {
                $content .= "- {$category} ({$count})\n";
            }
            $content .= "\n";
        }

        if ($recentDetails->isNotEmpty()) {
            $content .= "### Recent Entries\n\n";
            foreach ($recentDetails as $detail) {
                $title = $detail->title ?? $detail->type ?? 'Detail';
                $content .= "- [{$detail->type}] ";
                if ($detail->category) {
                    $content .= "({$detail->category}) ";
                }
                $content .= "{$title} - {$detail->created_at->diffForHumans()}\n";
            }
        }

        $content .= "\nUse SearchProjectDetails, GetProjectDetailsByCategory, or GetProjectDetailsOverview for more.";

        return Response::text($content);
    }

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, \Laravel\Mcp\Server\Prompts\Argument>
     */
    public function arguments(): array
    {
        return [];
    }
}

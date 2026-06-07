<?php

namespace App\Mcp\Resources;

use App\Mcp\Support\LessonPresenter;
use App\Models\Lesson;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class ProjectDetailsRecentResource extends Resource
{
    protected string $uri = 'project-details://recent';

    protected string $description = <<<'MARKDOWN'
        The most recently updated project-specific details for the current project in chronological order.
    MARKDOWN;

    protected string $mimeType = 'text/markdown';

    public function handle(Request $request): Response
    {
        $project = app('mcp.project');
        $limit = (int) ($request->get('limit') ?? 5);

        $recentDetails = Lesson::query()
            ->projectDetails()
            ->bySourceProject($project)
            ->active()
            ->orderBy('updated_at', 'desc')
            ->limit(max(1, min($limit, 20)))
            ->get();

        $content = "# Recent Project Details\n\n";
        $content .= "Project: **{$project}** — newest updates first. Use **GetProjectDetailById** with the id for full content.\n\n";

        if ($recentDetails->isEmpty()) {
            $content .= "No project details found.\n";

            return Response::text($content);
        }

        foreach ($recentDetails as $detail) {
            $title = LessonPresenter::displayTitle($detail);
            $content .= "## {$title}\n\n";
            $content .= "- **ID:** `{$detail->id}`\n";
            $content .= "- **Updated:** {$detail->updated_at->diffForHumans()}\n";
            $content .= "- **Created:** {$detail->created_at->diffForHumans()}\n";

            if ($detail->category) {
                $content .= "- **Category:** {$detail->category}\n";
            }

            if (! empty($detail->tags)) {
                $content .= '- **Tags:** '.implode(', ', array_slice($detail->tags, 0, 8))."\n";
            }

            $snippet = mb_substr(trim($detail->summary ?? $detail->content), 0, 200);
            if ($snippet !== '') {
                $content .= "\n{$snippet}";
                if (mb_strlen($detail->summary ?? $detail->content) > 200) {
                    $content .= '...';
                }
                $content .= "\n";
            }

            $content .= "\n---\n\n";
        }

        $content .= "See also: **GetRecentProjectDetails**, **GetLatestCaptureSummary**, **project-details://overview**.\n";

        return Response::text($content);
    }
}

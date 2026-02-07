<?php

namespace App\Mcp\Prompts;

use App\Models\Lesson;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class ProjectDetailsByCategory extends Prompt
{
    /**
     * The prompt's description.
     */
    protected string $description = <<<'MARKDOWN'
        Provides a summary of project-specific details in a specific category. Use when you need everything this project has documented for a topic (e.g. project-implementation, auth).
    MARKDOWN;

    /**
     * Handle the prompt request.
     */
    public function handle(Request $request): Response
    {
        $project = $request->get('project') ?? app('mcp.project');
        $category = $request->get('category');

        if (empty($category)) {
            return Response::text('Please provide a category parameter to see project details for that category.');
        }

        $details = Lesson::query()
            ->projectDetails()
            ->bySourceProject($project)
            ->active()
            ->byCategory($category)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($details->isEmpty()) {
            return Response::text("No project details found in category '{$category}'. Use GetProjectDetailsOverview to see available categories for this project.");
        }

        $content = "## Project Details in Category: {$category}\n\n";
        $content .= "Project: **{$project}**\n\n";
        $content .= "Total details: {$details->count()}\n\n";

        $content .= "### Details\n\n";
        foreach ($details->take(10) as $index => $detail) {
            $title = $detail->title ?? $detail->type ?? 'Detail';
            $content .= ($index + 1).". **{$title}**";
            if (! empty($detail->tags)) {
                $tags = implode(', ', array_slice($detail->tags, 0, 3));
                $content .= " - Tags: {$tags}";
            }
            $content .= "\n";
            $preview = $detail->summary ?? $detail->content ?? '';
            $content .= '   '.mb_substr($preview, 0, 200);
            if (mb_strlen($preview) > 200) {
                $content .= '...';
            }
            $content .= "\n\n";
        }

        if ($details->count() > 10) {
            $content .= "\n_Showing 10 of {$details->count()} details. Use GetProjectDetailsByCategory tool to see more._";
        }

        return Response::text($content);
    }

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, \Laravel\Mcp\Server\Prompts\Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                'category',
                'The category name to filter project details by',
                true,
            ),
        ];
    }
}

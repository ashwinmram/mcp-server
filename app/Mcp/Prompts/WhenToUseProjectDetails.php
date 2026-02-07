<?php

namespace App\Mcp\Prompts;

use App\Models\Lesson;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;

class WhenToUseProjectDetails extends Prompt
{
    /**
     * The prompt's description.
     */
    protected string $description = <<<'MARKDOWN'
        Clarifies when to use Project Details vs Lessons Learned and which tools to use. Use when deciding which MCP server or tool to call.
    MARKDOWN;

    /**
     * Handle the prompt request.
     */
    public function handle(Request $request): Response
    {
        $project = $request->get('project') ?? app('mcp.project');

        $total = Lesson::query()
            ->projectDetails()
            ->bySourceProject($project)
            ->active()
            ->count();

        $content = "## When to Use Project Details vs Lessons Learned\n\n";

        $content .= "**Use Project Details** (this server, project: **{$project}**) when:\n";
        $content .= "- \"How is X done in this project?\" / \"Where is Y in this codebase?\"\n";
        $content .= "- File paths, env vars, project conventions\n";
        $content .= "- Implementation details specific to this repo\n\n";

        $content .= "**Use Lessons Learned** (generic server) when:\n";
        $content .= "- General Laravel/Pest/Inertia patterns, testing patterns\n";
        $content .= "- Best practices not tied to one repo\n\n";

        $content .= "**Tool mapping (Project Details):**\n";
        $content .= "- **SearchProjectDetails** - Keyword, category, or tag search within this project\n";
        $content .= "- **GetProjectDetailsByCategory** - Full list of details in a category\n";
        $content .= "- **GetProjectDetailsOverview** - Counts by category (JSON)\n\n";

        $content .= "This project has **{$total}** project detail(s) available.";

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

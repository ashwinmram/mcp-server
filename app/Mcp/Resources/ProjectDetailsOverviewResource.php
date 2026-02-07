<?php

namespace App\Mcp\Resources;

use App\Models\Lesson;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class ProjectDetailsOverviewResource extends Resource
{
    /**
     * The resource's URI.
     */
    protected string $uri = 'project-details://overview';

    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        Overview of project-specific implementation details for the current project. Provides a summary of available categories, tags, and recent details. Load at session start when working in a codebase that has pushed project details.
    MARKDOWN;

    /**
     * The resource's MIME type.
     */
    protected string $mimeType = 'text/markdown';

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $project = app('mcp.project');

        $baseQuery = fn () => Lesson::query()
            ->projectDetails()
            ->bySourceProject($project)
            ->active();

        $totalDetails = $baseQuery()->count();

        $categories = $baseQuery()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        $tags = $baseQuery()
            ->whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        $recentDetails = $baseQuery()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $content = "# Project Details Overview\n\n";
        $content .= "Project: **{$project}**\n\n";
        $content .= 'This document summarizes project-specific implementation details for the current project. ';
        $content .= "Use SearchProjectDetails, GetProjectDetailsByCategory, and GetProjectDetailsOverview to query specific details.\n\n";

        $content .= "## Summary\n\n";
        $content .= "- **Total Details:** {$totalDetails}\n";
        $content .= '- **Categories:** '.$categories->count()."\n";
        $content .= '- **Tags:** '.$tags->count()."\n\n";

        if ($categories->isNotEmpty()) {
            $content .= "## Available Categories\n\n";
            foreach ($categories as $category) {
                $count = $baseQuery()->byCategory($category)->count();
                $content .= "- **{$category}** ({$count} details)\n";

                $subcategories = $baseQuery()
                    ->where('category', $category)
                    ->whereNotNull('subcategory')
                    ->distinct()
                    ->pluck('subcategory')
                    ->sort()
                    ->values();

                if ($subcategories->isNotEmpty()) {
                    foreach ($subcategories as $subcategory) {
                        $subCount = $baseQuery()
                            ->where('category', $category)
                            ->where('subcategory', $subcategory)
                            ->count();
                        $content .= "  - `{$subcategory}` ({$subCount} details)\n";
                    }
                }
            }
            $content .= "\n";
        }

        if ($tags->isNotEmpty()) {
            $content .= "## Tags in This Project\n\n";
            $content .= implode(', ', $tags->take(20)->toArray());
            if ($tags->count() > 20) {
                $content .= ', ...';
            }
            $content .= "\n\n";
        }

        if ($recentDetails->isNotEmpty()) {
            $content .= "## Recent Project Details\n\n";
            foreach ($recentDetails->take(5) as $detail) {
                $content .= '### '.($detail->title ?? ucfirst($detail->type ?? 'Detail'));
                if ($detail->category) {
                    $content .= " ({$detail->category})";
                }
                $content .= "\n\n";

                if (! empty($detail->tags)) {
                    $content .= '**Tags:** '.implode(', ', array_slice($detail->tags, 0, 5));
                    if (count($detail->tags) > 5) {
                        $content .= '...';
                    }
                    $content .= "\n\n";
                }

                $content .= mb_substr($detail->content ?? $detail->summary ?? '', 0, 300);
                if (mb_strlen($detail->content ?? $detail->summary ?? '') > 300) {
                    $content .= '...';
                }
                $content .= "\n\n";
                $content .= "---\n\n";
            }
        }

        $content .= "\n## How to Use\n\n";
        $content .= "**When to use Project Details vs Lessons Learned:**\n\n";
        $content .= "- **Project Details** (this server): \"How is X done in this project?\", \"Where is Y in this codebase?\", file paths, env vars, project conventions.\n";
        $content .= "- **Lessons Learned** (generic): General Laravel/Pest/Inertia patterns, testing patterns, best practices not tied to one repo.\n\n";
        $content .= "**Tools:**\n\n";
        $content .= "- **SearchProjectDetails** - Search by keyword, category, or tags within this project\n";
        $content .= "- **GetProjectDetailsByCategory** - List all details in a category for this project\n";
        $content .= "- **GetProjectDetailsOverview** - Get counts by category (JSON)\n";
        $content .= "- **ProjectDetailsOverview** prompt - Quick summary without opening this resource\n";
        $content .= "- **ProjectDetailsByCategory** prompt - Get details for a specific category\n";
        $content .= "- **WhenToUseProjectDetails** prompt - When to use Project Details vs Lessons Learned\n";

        return Response::text($content);
    }
}

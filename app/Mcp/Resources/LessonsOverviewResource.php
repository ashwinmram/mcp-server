<?php

namespace App\Mcp\Resources;

use App\Models\Lesson;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class LessonsOverviewResource extends Resource
{
    /**
     * The resource's URI.
     */
    protected string $uri = 'lessons://overview';

    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        Overview of all lessons learned from previous coding sessions. This resource provides a summary of available categories, tags, and recent lessons that should be automatically loaded at the start of each AI agent session.
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
        $totalLessons = Lesson::query()->generic()->count();

        $categories = Lesson::query()
            ->generic()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        $tags = Lesson::query()
            ->generic()
            ->whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        $recentLessons = Lesson::query()
            ->generic()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $content = "# Lessons Learned Overview\n\n";
        $content .= "This document provides an overview of lessons learned from previous coding sessions. ";
        $content .= "Use the SearchLessons, GetLessonByCategory, and GetLessonTags tools to query specific lessons.\n\n";

        $content .= "## Summary\n\n";
        $content .= "- **Total Lessons:** {$totalLessons}\n";
        $content .= "- **Categories:** ".$categories->count()."\n";
        $content .= "- **Tags:** ".$tags->count()."\n\n";

        if ($categories->isNotEmpty()) {
            $content .= "## Available Categories\n\n";
            foreach ($categories as $category) {
                $count = Lesson::query()->generic()->byCategory($category)->count();
                $content .= "- **{$category}** ({$count} lessons)\n";
            }
            $content .= "\n";
        }

        if ($tags->isNotEmpty()) {
            $content .= "## Popular Tags\n\n";
            $content .= implode(', ', $tags->take(20)->toArray());
            if ($tags->count() > 20) {
                $content .= ', ...';
            }
            $content .= "\n\n";
        }

        if ($recentLessons->isNotEmpty()) {
            $content .= "## Recent Lessons\n\n";
            foreach ($recentLessons->take(5) as $lesson) {
                $content .= "### ".ucfirst($lesson->type)." Lesson";
                if ($lesson->category) {
                    $content .= " ({$lesson->category})";
                }
                $content .= "\n\n";

                if (! empty($lesson->tags)) {
                    $content .= "**Tags:** ".implode(', ', array_slice($lesson->tags, 0, 5));
                    if (count($lesson->tags) > 5) {
                        $content .= '...';
                    }
                    $content .= "\n\n";
                }

                $content .= mb_substr($lesson->content, 0, 300);
                if (mb_strlen($lesson->content) > 300) {
                    $content .= '...';
                }
                $content .= "\n\n";
                $content .= "---\n\n";
            }
        }

        $content .= "\n## How to Use\n\n";
        $content .= "Use the following MCP tools to query lessons:\n\n";
        $content .= "- **SearchLessons** - Search by keyword, category, or tags\n";
        $content .= "- **GetLessonByCategory** - Get all lessons in a specific category\n";
        $content .= "- **GetLessonTags** - List all available tags\n";
        $content .= "- **LessonsLearnedOverview** prompt - Get an updated overview\n";
        $content .= "- **LessonsByCategory** prompt - Get lessons for a specific category\n";

        return Response::text($content);
    }
}

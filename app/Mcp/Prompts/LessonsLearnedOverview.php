<?php

namespace App\Mcp\Prompts;

use App\Models\Lesson;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class LessonsLearnedOverview extends Prompt
{
    /**
     * The prompt's description.
     */
    protected string $description = <<<'MARKDOWN'
        Provides an overview of available lessons learned from previous coding sessions. This helps AI agents understand what knowledge is available.
    MARKDOWN;

    /**
     * Handle the prompt request.
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
            ->values()
            ->toArray();

        $tags = Lesson::query()
            ->generic()
            ->whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->take(20)
            ->values()
            ->toArray();

        $recentLessons = Lesson::query()
            ->generic()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'type', 'category', 'created_at']);

        $content = "## Lessons Learned Overview\n\n";
        $content .= "Total generic lessons available: {$totalLessons}\n\n";

        if (! empty($categories)) {
            $content .= "### Available Categories\n\n";
            foreach ($categories as $category) {
                $count = Lesson::query()->generic()->byCategory($category)->count();
                $content .= "- {$category} ({$count} lessons)\n";
            }
            $content .= "\n";
        }

        if (! empty($tags)) {
            $content .= "### Popular Tags\n\n";
            $content .= implode(', ', array_slice($tags, 0, 20));
            if (count($tags) > 20) {
                $content .= '...';
            }
            $content .= "\n\n";
        }

        if ($recentLessons->isNotEmpty()) {
            $content .= "### Recent Lessons\n\n";
            foreach ($recentLessons as $lesson) {
                $content .= "- [{$lesson->type}] ";
                if ($lesson->category) {
                    $content .= "({$lesson->category}) ";
                }
                $content .= "- Created: {$lesson->created_at->diffForHumans()}\n";
            }
        }

        $content .= "\n\nUse the SearchLessons tool to find specific lessons, or GetLessonByCategory to browse by category.";

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
            // No arguments needed for overview
        ];
    }
}

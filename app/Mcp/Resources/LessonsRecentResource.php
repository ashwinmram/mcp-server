<?php

namespace App\Mcp\Resources;

use App\Mcp\Support\LessonPresenter;
use App\Models\Lesson;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class LessonsRecentResource extends Resource
{
    protected string $uri = 'lessons://recent';

    protected string $description = <<<'MARKDOWN'
        The most recently created generic lessons in chronological order. Load for "what was captured lately" without relevance ranking.
    MARKDOWN;

    protected string $mimeType = 'text/markdown';

    public function handle(Request $request): Response
    {
        $limit = (int) ($request->get('limit') ?? 5);

        $recentLessons = Lesson::query()
            ->generic()
            ->active()
            ->orderBy('created_at', 'desc')
            ->limit(max(1, min($limit, 20)))
            ->get();

        $content = "# Recent Generic Lessons\n\n";
        $content .= "Chronological list (newest first). Use **GetLessonById** with the id for full content.\n\n";

        if ($recentLessons->isEmpty()) {
            $content .= "No lessons found.\n";

            return Response::text($content);
        }

        foreach ($recentLessons as $lesson) {
            $title = LessonPresenter::displayTitle($lesson);
            $content .= "## {$title}\n\n";
            $content .= "- **ID:** `{$lesson->id}`\n";
            $content .= "- **Created:** {$lesson->created_at->diffForHumans()}\n";

            if ($lesson->category) {
                $content .= "- **Category:** {$lesson->category}\n";
            }

            if (! empty($lesson->tags)) {
                $content .= '- **Tags:** '.implode(', ', array_slice($lesson->tags, 0, 8))."\n";
            }

            $snippet = mb_substr(trim($lesson->summary ?? $lesson->content), 0, 200);
            if ($snippet !== '') {
                $content .= "\n{$snippet}";
                if (mb_strlen($lesson->summary ?? $lesson->content) > 200) {
                    $content .= '...';
                }
                $content .= "\n";
            }

            $content .= "\n---\n\n";
        }

        $content .= "See also: **GetRecentLessons**, **GetLatestCaptureSummary**, **lessons://overview**.\n";

        return Response::text($content);
    }
}

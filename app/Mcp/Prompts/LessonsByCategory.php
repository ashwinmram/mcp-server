<?php

namespace App\Mcp\Prompts;

use App\Models\Lesson;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class LessonsByCategory extends Prompt
{
    /**
     * The prompt's description.
     */
    protected string $description = <<<'MARKDOWN'
        Provides a summary of lessons available in a specific category. This helps AI agents understand what lessons are available for a particular topic.
    MARKDOWN;

    /**
     * Handle the prompt request.
     */
    public function handle(Request $request): Response
    {
        $category = $request->get('category');

        if (empty($category)) {
            return Response::text('Please provide a category parameter to see lessons for that category.');
        }

        $lessons = Lesson::query()
            ->generic()
            ->byCategory($category)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($lessons->isEmpty()) {
            return Response::text("No lessons found in category '{$category}'. Use GetLessonTags to see available categories.");
        }

        $content = "## Lessons in Category: {$category}\n\n";
        $content .= "Total lessons: {$lessons->count()}\n\n";

        $content .= "### Lessons\n\n";
        foreach ($lessons->take(10) as $index => $lesson) {
            $content .= ($index + 1).". **{$lesson->type}**";
            if (! empty($lesson->tags)) {
                $tags = implode(', ', array_slice($lesson->tags, 0, 3));
                $content .= " - Tags: {$tags}";
            }
            $content .= "\n";
            $content .= "   ".mb_substr($lesson->content, 0, 200);
            if (mb_strlen($lesson->content) > 200) {
                $content .= '...';
            }
            $content .= "\n\n";
        }

        if ($lessons->count() > 10) {
            $content .= "\n_Showing 10 of {$lessons->count()} lessons. Use GetLessonByCategory tool to see more._";
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
                name: 'category',
                description: 'The category name to filter lessons by',
                required: true
            ),
        ];
    }
}

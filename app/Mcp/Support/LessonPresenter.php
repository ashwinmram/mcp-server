<?php

namespace App\Mcp\Support;

use App\Models\Lesson;
use Illuminate\Support\Facades\Schema;

class LessonPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function toGenericArray(Lesson $lesson, bool $includeRelated = false): array
    {
        $result = [
            'id' => $lesson->id,
            'type' => $lesson->type,
            'category' => $lesson->category,
            'subcategory' => $lesson->subcategory,
            'title' => $lesson->title,
            'summary' => $lesson->summary,
            'tags' => $lesson->tags,
            'content' => $lesson->content,
            'source_project' => $lesson->source_project,
            'source_projects' => $lesson->source_projects ?? [$lesson->source_project],
            'created_at' => $lesson->created_at->toIso8601String(),
        ];

        if ($includeRelated) {
            $relatedLessons = $lesson->getAllRelatedLessons(5);
            $result['related_lessons'] = $relatedLessons->map(function (Lesson $related) {
                return [
                    'id' => $related->id,
                    'title' => $related->title,
                    'category' => $related->category,
                    'relationship_type' => $related->pivot->relationship_type ?? 'related',
                    'relevance_score' => $related->pivot->relevance_score ?? null,
                ];
            })->toArray();
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public static function toProjectDetailArray(Lesson $lesson): array
    {
        return [
            'id' => $lesson->id,
            'type' => $lesson->type,
            'category' => $lesson->category,
            'subcategory' => $lesson->subcategory,
            'title' => $lesson->title,
            'summary' => $lesson->summary,
            'tags' => $lesson->tags,
            'content' => $lesson->content,
            'source_project' => $lesson->source_project,
            'created_at' => $lesson->created_at->toIso8601String(),
            'updated_at' => $lesson->updated_at->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toSummaryArray(Lesson $lesson): array
    {
        return [
            'id' => $lesson->id,
            'type' => $lesson->type,
            'title' => $lesson->title,
            'category' => $lesson->category,
            'subcategory' => $lesson->subcategory,
            'tags' => $lesson->tags,
            'created_at' => $lesson->created_at->toIso8601String(),
            'updated_at' => $lesson->updated_at->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toRelatedArray(Lesson $related): array
    {
        return [
            'id' => $related->id,
            'type' => $related->type,
            'category' => $related->category,
            'subcategory' => $related->subcategory,
            'title' => $related->title,
            'summary' => $related->summary,
            'tags' => $related->tags,
            'content' => $related->content,
            'relationship_type' => $related->pivot->relationship_type ?? 'related',
            'relevance_score' => $related->pivot->relevance_score ?? null,
            'source_project' => $related->source_project,
            'source_projects' => $related->source_projects ?? [$related->source_project],
            'created_at' => $related->created_at->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toTopLessonArray(Lesson $lesson): array
    {
        $result = self::toGenericArray($lesson);

        if (Schema::hasColumn('lessons', 'relevance_score')) {
            $result['relevance_score'] = $lesson->relevance_score ?? 0.0;
        }

        return $result;
    }

    public static function displayTitle(Lesson $lesson): string
    {
        if ($lesson->title) {
            return $lesson->title;
        }

        $snippet = mb_substr(trim($lesson->content), 0, 80);

        return $snippet !== '' ? $snippet : ($lesson->type ?? 'Lesson');
    }
}

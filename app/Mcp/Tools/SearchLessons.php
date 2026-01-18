<?php

namespace App\Mcp\Tools;

use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SearchLessons extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Search for lessons learned by keyword, category, or tags. Returns matching lessons with their content.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $query = Lesson::query()->generic();
        $searchQuery = $request->get('query');
        $includeRelated = (bool) $request->get('include_related', false);

        // Search by keyword using FULLTEXT search, fallback to LIKE if no results
        if ($searchQuery) {
            // Try FULLTEXT first
            $fulltextQuery = clone $query;
            $fulltextQuery->whereRaw('MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$searchQuery]);
            $fulltextCount = $fulltextQuery->count();

            if ($fulltextCount > 0) {
                // Use FULLTEXT search
                $query->whereRaw('MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$searchQuery]);
            } else {
                // Fallback to LIKE search for better compatibility with small datasets
                $query->where('content', 'like', '%'.$searchQuery.'%');
            }
        }

        // Filter by category or subcategory
        if ($request->get('category')) {
            $category = $request->get('category');
            // Check if it's a subcategory
            $isSubcategory = str_contains($category, '-') &&
                             $category !== 'lessons-learned' &&
                             Lesson::query()->generic()->bySubcategory($category)->exists();

            if ($isSubcategory) {
                $query->bySubcategory($category);
            } else {
                $query->byCategory($category);
            }
        }

        // Filter by tags
        if ($request->get('tags') && is_array($request->get('tags'))) {
            $query->byTags($request->get('tags'));
        }

        $limit = (int) ($request->get('limit', 10));

        // Order by FULLTEXT relevance score if searching with FULLTEXT, otherwise by date
        if ($searchQuery) {
            // Check if we're using FULLTEXT (check if the query has MATCH clause)
            $queryString = $query->toSql();
            $usingFulltext = str_contains($queryString, 'MATCH');

            if ($usingFulltext) {
                // Check if relevance_score column exists (Phase 3 feature)
                $hasRelevanceScore = \Schema::hasColumn('lessons', 'relevance_score');

                if ($hasRelevanceScore) {
                    $query->selectRaw('*, MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance', [$searchQuery])
                        ->orderByRaw('(MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) * 0.7) + (COALESCE(relevance_score, 0) * 0.3) DESC', [$searchQuery])
                        ->orderBy('created_at', 'desc');
                } else {
                    // Phase 1: Just use FULLTEXT relevance
                    $query->selectRaw('*, MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance', [$searchQuery])
                        ->orderByRaw('MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE) DESC', [$searchQuery])
                        ->orderBy('created_at', 'desc');
                }
            } else {
                // Using LIKE fallback, just order by date
                $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $lessons = $query->limit($limit)->get();

        $results = $lessons->map(function (Lesson $lesson) use ($includeRelated) {
            $result = [
                'id' => $lesson->id,
                'type' => $lesson->type,
                'category' => $lesson->category,
                'subcategory' => $lesson->subcategory,
                'title' => $lesson->title,
                'summary' => $lesson->summary,
                'tags' => $lesson->tags,
                'content' => $lesson->content,
                'source_project' => $lesson->source_project, // Keep for backward compatibility
                'source_projects' => $lesson->source_projects ?? [$lesson->source_project],
                'created_at' => $lesson->created_at->toIso8601String(),
            ];

            // Optionally include related lessons
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
        })->toArray();

        return Response::json([
            'results' => $results,
            'count' => count($results),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->nullable()->description('Search keyword to find in lesson content (uses MySQL FULLTEXT search)'),
            'category' => $schema->string()->nullable()->description('Filter lessons by category'),
            'tags' => $schema->array()->nullable()->description('Array of tags to filter by'),
            'limit' => $schema->integer()->default(10)->description('Maximum number of results to return'),
            'include_related' => $schema->boolean()->default(false)->description('Whether to include related lessons in the response'),
        ];
    }
}

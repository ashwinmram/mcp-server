<?php

namespace App\Mcp\Tools;

use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SuggestSearchQueries extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Suggest related search queries based on a topic or query. Expands a query into multiple related searches to help discover all relevant lessons. Useful when you're unsure about the best search terms.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $topic = $request->get('topic');
        $currentQuery = $request->get('query');

        if (empty($topic) && empty($currentQuery)) {
            return Response::error('Topic or query is required');
        }

        $searchTerm = $topic ?? $currentQuery;

        // Get query expansion suggestions based on topic
        $suggestions = $this->getQuerySuggestions(strtolower($searchTerm));

        // Get actual lessons to suggest categories/tags based on what exists
        $sampleLessons = Lesson::query()
            ->generic()
            ->where(function ($query) use ($searchTerm) {
                $query->where('content', 'like', '%'.$searchTerm.'%')
                    ->orWhere('title', 'like', '%'.$searchTerm.'%')
                    ->orWhere('summary', 'like', '%'.$searchTerm.'%');
            })
            ->limit(10)
            ->get();

        // Extract related categories and tags from matching lessons
        $relatedCategories = $sampleLessons
            ->pluck('category')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $relatedTags = $sampleLessons
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->unique()
            ->take(10)
            ->values()
            ->toArray();

        $queries = [];

        // Base query
        $queries[] = [
            'query' => $searchTerm,
            'type' => 'exact',
            'description' => 'Exact match for your search term',
        ];

        // Suggested expanded queries
        foreach ($suggestions as $suggestion) {
            $queries[] = [
                'query' => $suggestion,
                'type' => 'related',
                'description' => 'Related term that might help discover additional lessons',
            ];
        }

        // Category-based queries
        if (! empty($relatedCategories)) {
            foreach (array_slice($relatedCategories, 0, 3) as $category) {
                $queries[] = [
                    'query' => null,
                    'category' => $category,
                    'type' => 'category',
                    'description' => "Browse lessons in category: {$category}",
                ];
            }
        }

        // Tag-based queries
        if (! empty($relatedTags)) {
            foreach (array_slice($relatedTags, 0, 3) as $tag) {
                $queries[] = [
                    'query' => null,
                    'tags' => [$tag],
                    'type' => 'tag',
                    'description' => "Filter lessons by tag: {$tag}",
                ];
            }
        }

        return Response::json([
            'original_topic' => $searchTerm,
            'suggested_queries' => $queries,
            'related_categories' => $relatedCategories,
            'related_tags' => $relatedTags,
            'count' => count($queries),
        ]);
    }

    /**
     * Get query expansion suggestions based on topic.
     */
    protected function getQuerySuggestions(string $topic): array
    {
        $expansions = [
            'validation' => ['form request', 'validation rules', 'error handling', 'form validation'],
            'testing' => ['test', 'tests', 'pest', 'phpunit', 'mocking', 'assertions'],
            'mocking' => ['mock', 'stub', 'spy', 'fake', 'test doubles'],
            'migration' => ['migrations', 'database', 'schema', 'table', 'column'],
            'route' => ['routing', 'routes', 'controller', 'endpoint'],
            'controller' => ['action', 'handler', 'request', 'response'],
            'model' => ['eloquent', 'database', 'relationships', 'factory'],
            'form' => ['forms', 'validation', 'form request', 'input'],
            'api' => ['endpoint', 'resource', 'controller', 'response'],
            'error' => ['errors', 'exception', 'handling', 'failure'],
            'auth' => ['authentication', 'authorization', 'login', 'user'],
            'middleware' => ['middlewares', 'request', 'filter', 'guard'],
            'database' => ['query', 'eloquent', 'model', 'migration'],
            'cache' => ['caching', 'redis', 'performance'],
            'queue' => ['jobs', 'background', 'async', 'worker'],
        ];

        $suggestions = [];

        // Check for exact matches
        foreach ($expansions as $key => $values) {
            if (str_contains($topic, $key)) {
                $suggestions = array_merge($suggestions, $values);
            }
        }

        // Check for partial matches
        foreach ($expansions as $key => $values) {
            if (str_contains($key, $topic) || str_contains($topic, $key)) {
                $suggestions = array_merge($suggestions, $values);
            }
        }

        // Remove duplicates and limit results
        $suggestions = array_unique($suggestions);
        $suggestions = array_slice($suggestions, 0, 5);

        return array_values($suggestions);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'topic' => $schema->string()->nullable()->description('The topic or subject you want to search for (e.g., "validation", "testing")'),
            'query' => $schema->string()->nullable()->description('Current search query to expand (alternative to topic)'),
        ];
    }
}

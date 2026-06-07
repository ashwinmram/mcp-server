<?php

namespace App\Mcp\Tools;

use App\Models\Lesson;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SuggestProjectDetailSearchQueries extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Suggest related search queries for project-specific details based on a topic. Expands a query into multiple related searches within the current project.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $project = app('mcp.project');
        $topic = $request->get('topic');
        $currentQuery = $request->get('query');

        if (empty($topic) && empty($currentQuery)) {
            return Response::error('Topic or query is required');
        }

        $searchTerm = $topic ?? $currentQuery;

        $suggestions = $this->getQuerySuggestions(strtolower($searchTerm));

        $sampleLessons = Lesson::query()
            ->projectDetails()
            ->bySourceProject($project)
            ->where(function ($query) use ($searchTerm) {
                $query->where('content', 'like', '%'.$searchTerm.'%')
                    ->orWhere('title', 'like', '%'.$searchTerm.'%')
                    ->orWhere('summary', 'like', '%'.$searchTerm.'%');
            })
            ->limit(10)
            ->get();

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

        $queries[] = [
            'query' => $searchTerm,
            'type' => 'exact',
            'description' => 'Exact match for your search term',
        ];

        foreach ($suggestions as $suggestion) {
            $queries[] = [
                'query' => $suggestion,
                'type' => 'related',
                'description' => 'Related term that might help discover additional project details',
            ];
        }

        if (! empty($relatedCategories)) {
            foreach (array_slice($relatedCategories, 0, 3) as $category) {
                $queries[] = [
                    'query' => null,
                    'category' => $category,
                    'type' => 'category',
                    'description' => "Browse details in category: {$category}",
                ];
            }
        }

        if (! empty($relatedTags)) {
            foreach (array_slice($relatedTags, 0, 3) as $tag) {
                $queries[] = [
                    'query' => null,
                    'tags' => [$tag],
                    'type' => 'tag',
                    'description' => "Filter details by tag: {$tag}",
                ];
            }
        }

        return Response::json([
            'project' => $project,
            'original_topic' => $searchTerm,
            'suggested_queries' => $queries,
            'related_categories' => $relatedCategories,
            'related_tags' => $relatedTags,
            'count' => count($queries),
        ]);
    }

    /**
     * @return list<string>
     */
    protected function getQuerySuggestions(string $topic): array
    {
        $expansions = [
            'auth' => ['authentication', 'fortify', 'login', 'middleware'],
            'route' => ['routing', 'routes', 'controller', 'web.php'],
            'test' => ['testing', 'pest', 'phpunit', 'feature test'],
            'config' => ['configuration', 'env', 'settings', '.env'],
            'frontend' => ['vue', 'inertia', 'components', 'pages'],
            'mcp' => ['tools', 'resources', 'prompts', 'server'],
            'database' => ['migration', 'model', 'eloquent', 'schema'],
        ];

        $suggestions = [];

        foreach ($expansions as $key => $values) {
            if (str_contains($topic, $key) || str_contains($key, $topic)) {
                $suggestions = array_merge($suggestions, $values);
            }
        }

        return array_values(array_slice(array_unique($suggestions), 0, 5));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'topic' => $schema->string()->nullable()->description('The topic or subject to search for within this project'),
            'query' => $schema->string()->nullable()->description('Current search query to expand (alternative to topic)'),
        ];
    }
}

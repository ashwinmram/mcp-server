<?php

namespace App\Services;

use App\Models\Lesson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LessonImportService
{
    public function __construct(
        protected LessonValidationService $validationService,
        protected LessonContentHashService $hashService
    ) {}

    /**
     * Process and store lessons with deduplication.
     *
     * @return array{created: int, updated: int, skipped: int, errors: array}
     */
    public function processLessons(array $lessons, string $sourceProject): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($lessons as $index => $lessonData) {
            try {
                // Validate lesson structure
                if (empty($lessonData['content']) || empty($lessonData['type'])) {
                    $result['errors'][] = "Lesson at index {$index}: Missing required fields (content or type)";

                    continue;
                }

                // Validate content is generic
                $validation = $this->validationService->validateIsGeneric($lessonData['content']);
                if (! $validation['is_valid']) {
                    $result['errors'][] = "Lesson at index {$index}: ".implode(', ', $validation['errors']);

                    continue;
                }

                // Generate content hash
                $contentHash = $this->hashService->generateHash($lessonData['content']);

                // Check for existing lesson across ALL projects (cross-project deduplication)
                $existingLesson = Lesson::findByContentHashAcrossProjects($contentHash);

                if ($existingLesson) {
                    // Merge tags from existing and new lesson
                    $mergedTags = Lesson::mergeTags(
                        $existingLesson->tags ?? [],
                        $lessonData['tags'] ?? []
                    );

                    // Merge metadata from existing and new lesson
                    $mergedMetadata = Lesson::mergeMetadata(
                        $existingLesson->metadata ?? [],
                        $lessonData['metadata'] ?? []
                    );

                    // Merge source projects
                    $existingSourceProjects = $existingLesson->source_projects ?? [$existingLesson->source_project];
                    $newSourceProjects = array_unique(array_merge($existingSourceProjects, [$sourceProject]));

                    // Check if anything has changed
                    $tagsChanged = $mergedTags !== ($existingLesson->tags ?? []);
                    $metadataChanged = $this->hasMetadataChanged(
                        $existingLesson->metadata ?? [],
                        $lessonData['metadata'] ?? []
                    );
                    $sourceProjectsChanged = $newSourceProjects !== $existingSourceProjects;
                    $categoryChanged = ($lessonData['category'] ?? null) !== $existingLesson->category;

                    // Extract title, summary, and subcategory if not already set
                    $title = $existingLesson->title ?? $this->extractTitle($lessonData);
                    $summary = $existingLesson->summary ?? $this->extractSummary($lessonData);
                    $subcategory = $existingLesson->subcategory ?? $this->extractSubcategory($lessonData, $lessonData['category'] ?? $existingLesson->category);

                    // Check if title/summary changed (only if explicitly provided in lessonData)
                    $titleChanged = (! empty($lessonData['title']) || ! empty($lessonData['metadata']['title'])) &&
                        $title !== $existingLesson->title;
                    $summaryChanged = (! empty($lessonData['summary']) || ! empty($lessonData['metadata']['summary']) || ! empty($lessonData['metadata']['description'])) &&
                        $summary !== $existingLesson->summary;

                    if ($tagsChanged || $metadataChanged || $sourceProjectsChanged || $categoryChanged || $titleChanged || $summaryChanged) {
                        // Update existing lesson with merged data
                        $existingLesson->update([
                            'category' => $lessonData['category'] ?? $existingLesson->category,
                            'subcategory' => $subcategory,
                            'title' => $title,
                            'summary' => $summary,
                            'tags' => $mergedTags,
                            'metadata' => $mergedMetadata,
                            'source_projects' => $newSourceProjects,
                            'is_generic' => $validation['is_valid'],
                        ]);
                        $result['updated']++;
                    } else {
                        // Skip identical lesson
                        $result['skipped']++;
                    }
                } else {
                    // Extract title, summary, and subcategory from lesson data
                    $title = $this->extractTitle($lessonData);
                    $summary = $this->extractSummary($lessonData);
                    $category = $lessonData['category'] ?? null;
                    $subcategory = $this->extractSubcategory($lessonData, $category);

                    // Create new lesson
                    $lesson = Lesson::create([
                        'source_project' => $sourceProject,
                        'source_projects' => [$sourceProject],
                        'type' => $lessonData['type'],
                        'category' => $category,
                        'subcategory' => $subcategory,
                        'title' => $title,
                        'summary' => $summary,
                        'tags' => $lessonData['tags'] ?? [],
                        'metadata' => $lessonData['metadata'] ?? [],
                        'content' => $lessonData['content'],
                        'content_hash' => $contentHash,
                        'is_generic' => true,
                    ]);
                    $result['created']++;

                    // Detect and create relationships with similar lessons
                    $this->detectAndCreateRelationships($lesson);
                }
            } catch (\Exception $e) {
                $result['errors'][] = "Lesson at index {$index}: {$e->getMessage()}";
            }
        }

        return $result;
    }

    /**
     * Check if metadata has changed.
     */
    protected function hasMetadataChanged(array $existing, array $new): bool
    {
        // Merge and compare - if new keys exist or values changed, metadata changed
        $merged = array_merge($existing, $new);
        ksort($merged);
        ksort($existing);

        return $merged !== $existing;
    }

    /**
     * Extract title from lesson data.
     */
    protected function extractTitle(array $lessonData): ?string
    {
        // Try title field first
        if (! empty($lessonData['title'])) {
            return $lessonData['title'];
        }

        // Try metadata title
        if (! empty($lessonData['metadata']['title'])) {
            return $lessonData['metadata']['title'];
        }

        // Try parsing JSON content for title
        if (! empty($lessonData['content'])) {
            $content = $lessonData['content'];
            if (str_starts_with(trim($content), '{')) {
                $decoded = json_decode($content, true);
                if (is_array($decoded) && ! empty($decoded['title'])) {
                    return $decoded['title'];
                }
            }
        }

        return null;
    }

    /**
     * Extract summary from lesson data.
     */
    protected function extractSummary(array $lessonData): ?string
    {
        // Try summary field first
        if (! empty($lessonData['summary'])) {
            return $lessonData['summary'];
        }

        // Try metadata summary
        if (! empty($lessonData['metadata']['summary'])) {
            return $lessonData['metadata']['summary'];
        }

        // Try parsing JSON content for description (often used as summary)
        if (! empty($lessonData['content'])) {
            $content = $lessonData['content'];
            if (str_starts_with(trim($content), '{')) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    if (! empty($decoded['description'])) {
                        return $decoded['description'];
                    }
                    if (! empty($decoded['summary'])) {
                        return $decoded['summary'];
                    }
                }
            }

            // Generate summary from first 2-3 sentences of content
            $text = strip_tags($content);
            $sentences = preg_split('/(?<=[.!?])\s+/', $text, 3);
            if (count($sentences) >= 2) {
                return trim(implode(' ', array_slice($sentences, 0, 2)));
            }
        }

        return null;
    }

    /**
     * Extract subcategory from lesson data based on summary/content keywords.
     */
    protected function extractSubcategory(array $lessonData, ?string $category): ?string
    {
        if (empty($category)) {
            return null;
        }

        // Get text to search (prioritize summary, fallback to content)
        $textToSearch = null;
        if (! empty($lessonData['summary'])) {
            $textToSearch = strtolower($lessonData['summary']);
        } elseif (! empty($lessonData['content'])) {
            $textToSearch = strtolower(substr($lessonData['content'], 0, 1000));
        }

        if (empty($textToSearch)) {
            return null;
        }

        // Category to subcategory keyword mappings
        $subcategoryKeywords = $this->getSubcategoryKeywords();

        if (! isset($subcategoryKeywords[$category])) {
            return null;
        }

        $keywords = $subcategoryKeywords[$category];

        // Score each subcategory based on keyword matches
        $scores = [];
        foreach ($keywords as $subcategory => $keywordList) {
            $score = 0;
            foreach ($keywordList as $keyword) {
                if (str_contains($textToSearch, strtolower($keyword))) {
                    $score += strlen($keyword); // Longer keywords get higher scores
                }
            }
            if ($score > 0) {
                $scores[$subcategory] = $score;
            }
        }

        if (empty($scores)) {
            return null;
        }

        // Return subcategory with highest score
        arsort($scores);

        return array_key_first($scores);
    }

    /**
     * Get subcategory keyword mappings by category.
     *
     * @return array<string, array<string, array<string>>>
     */
    protected function getSubcategoryKeywords(): array
    {
        return [
            'lessons-learned' => [
                'component-architecture' => ['component', 'vue', 'inertia', 'frontend', 'ui', 'modal', 'page', 'layout', 'stub', 'template'],
                'database-backend' => ['database', 'migration', 'eloquent', 'model', 'query', 'pivot', 'foreign key', 'business_id', 'tenant', 'scope'],
                'testing-patterns' => ['test', 'pest', 'phpunit', 'assert', 'mock', 'factory', 'refreshtestdatabase'],
                'frontend-development' => ['frontend', 'vue', 'component', 'inertia', 'router', 'page', 'modal', 'form', 'validation'],
                'inertia-routing' => ['inertia', 'router', 'route', 'redirect', 'render', 'post', 'get', 'preservestate', 'only'],
                'code-quality' => ['pint', 'format', 'style', 'coding', 'naming', 'convention'],
                'error-handling' => ['error', 'exception', 'validation', 'handler', 'flash'],
                'development-environment' => ['git', 'gitignore', 'env', 'config', 'setup'],
            ],
            'testing-patterns' => [
                'backend-testing' => ['phpunit', 'feature', 'test', 'assert', 'refreshdatabase', 'factory', 'laravel route', 'query parameter', 'order by', 'withoutmiddleware', 'csrf', 'notification', 'password'],
                'frontend-testing' => ['vitest', 'vue', 'component', 'test', 'mock', 'router', 'inertia', 'link component', 'vi.hoisted', 'shadow', 'class'],
                'test-structure' => ['test', 'assert', 'setup', 'arrange', 'act', 'backend', 'frontend', 'comprehensive', 'pattern'],
            ],
            'lararvel-coding-style' => [
                'naming-conventions' => ['pascalcase', 'camelcase', 'naming', 'class', 'method', 'entityservice', 'tenantcontroller', 'constructor', 'property promotion'],
                'model-patterns' => ['model', 'eloquent', 'cast', 'fillable', 'relationship', 'scope', 'eager loading', 'n+1', 'query()', 'db::table', 'pivot', '< 30 lines', 'extract helpers'],
                'validation-patterns' => ['validation', 'form request', 'rule', 'required', 'validate', 'dedicated form request', 'pipe chain', 'closure', 'dispatcher::listen', 'listeners'],
                'controller-patterns' => ['controller', 'action', 'service', 'form request', 'domain logic', 'controllers stay thin', 'success banners', 'fortify', 'features::enabled', 'config(', 'middleware', 'auth:sanctum', 'verified', 'can:admin', 'policies', 'gates'],
                'architecture-patterns' => ['service', 'pattern', 'architecture', 'principle', 'storage facade', 'configurable disk', 'inertia::setrootview', 'rootview', 'x-inertia header'],
                'directory-structure' => ['resources/js', 'pages', 'components', 'directory', 'structure', 'path'],
                'enums' => ['enum', 'php enum', 'string-backed', 'database enum', 'enum()'],
                'data-handling' => ['carbon', 'date arithmetic', 'toisostring', 'inertia props', 'locale formatting', 'enumeratesvalues', 'map', 'filter', 'reduce', 'collection', 'immutable'],
            ],
            'inertia-first-architecture' => [
                'routing-patterns' => ['inertia', 'router', 'route', 'redirect', 'render'],
                'data-flow' => ['handleinertia', 'props', 'share', 'data'],
                'component-structure' => ['component', 'page', 'layout'],
            ],
            'testing-quick-reference' => [
                'test-commands' => ['php artisan test', 'test', 'pest', 'phpunit', 'backend provides', 'controllers', 'multi-tenant', 'laravel application'],
                'testing-tips' => ['test', 'assert', 'mock', 'factory', 'withoutmiddleware', 'csrftoken', 'backend provides'],
            ],
            'frontend-testing' => [
                'component-testing' => ['component', 'vue', 'test', 'vitest'],
                'mock-patterns' => ['mock', 'stub', 'vi.fn', 'route', 'usePage', 'identify', 'categorize', 'fix', 'verify', 'jetstream', 'flash message', 'nested structure'],
            ],
            'mcp-configuration' => [
                'mcp-setup' => ['mcp', 'server', 'config', 'setup', 'tool', 'resource'],
                'data-handling' => ['json', 'array', 'tag', 'wherejsoncontains', 'array_values', 'array_unique'],
                'workflow' => ['git', 'commit', 'stage', 'documentation', 'cursorrules', 'ai_*.json'],
            ],
            'package-development' => [
                'package-structure' => ['package', 'composer.json', 'packagist', 'repository'],
                'testing-packages' => ['test', 'package', 'service provider', 'config'],
            ],
            'guidelines' => [
                'coding-guidelines' => ['guideline', 'rule', 'principle', 'best practice', 'laravel boost', 'foundation rules', 'type hints', 'subcategorization', 'database schema', 'migration strategy', 'lessons learned mcp server', 'mcp servers', 'fetch_mcp_resource'],
            ],
            'testing-config' => [
                'test-setup' => ['test', 'config', 'setup', 'env', 'database', 'pint', 'code style', 'formatting', 'gitignore', 'tracked files', 'enum columns', 'migrations', 'db::statement', 'file cleanup', 'json files'],
            ],
            'directory-structure' => [
                'routes' => ['route', 'web.php', 'route'],
                'components' => ['component', 'vue', 'inertia', 'page'],
                'tests' => ['test', 'phpunit', 'vitest', 'trait'],
                'models' => ['model', 'eloquent', 'scope'],
                'services' => ['service', 'business logic'],
                'docs' => ['documentation', 'json', 'ai'],
                'config' => ['gitignore', 'config'],
            ],
            'php-syntax' => [
                'type-declarations' => ['type', 'return type', 'parameter', 'int', 'bool'],
                'constructors' => ['constructor', 'property promotion'],
                'enums' => ['enum', 'php enum', 'string-backed', 'enum()', 'database enum'],
                'variable-handling' => ['null coalescing', '??', 'array access', 'extract', 'variable', 'string interpolation'],
            ],
            'database-subcategories' => [
                'database-design' => ['database', 'table', 'column', 'index', 'foreign key', 'migration'],
                'query-optimization' => ['query', 'performance', 'optimize', 'index', 'eager loading'],
            ],
        ];
    }

    /**
     * Detect and create relationships with similar lessons.
     */
    protected function detectAndCreateRelationships(Lesson $lesson): void
    {
        if (! $lesson->category || empty($lesson->tags) || ! is_array($lesson->tags)) {
            return;
        }

        // Find similar lessons (same category + overlapping tags)
        $similarLessons = Lesson::query()
            ->generic()
            ->where('id', '!=', $lesson->id)
            ->where('category', $lesson->category)
            ->byTags($lesson->tags)
            ->limit(10)
            ->get();

        foreach ($similarLessons as $similarLesson) {
            // Calculate relevance score based on tag overlap
            $relevanceScore = $this->calculateTagOverlapScore($lesson->tags, $similarLesson->tags ?? []);

            // Only create relationship if relevance is above threshold
            if ($relevanceScore >= 0.3) {
                // Check if relationship already exists
                $exists = DB::table('lesson_relationships')
                    ->where('lesson_id', $lesson->id)
                    ->where('related_lesson_id', $similarLesson->id)
                    ->exists();

                if (! $exists) {
                    DB::table('lesson_relationships')->insert([
                        'id' => Str::uuid(),
                        'lesson_id' => $lesson->id,
                        'related_lesson_id' => $similarLesson->id,
                        'relationship_type' => 'related',
                        'relevance_score' => $relevanceScore,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Calculate tag overlap score between two tag arrays.
     */
    protected function calculateTagOverlapScore(array $tags1, array $tags2): float
    {
        if (empty($tags1) || empty($tags2)) {
            return 0.0;
        }

        $intersection = count(array_intersect($tags1, $tags2));
        $union = count(array_unique(array_merge($tags1, $tags2)));

        if ($union === 0) {
            return 0.0;
        }

        return $intersection / $union;
    }
}

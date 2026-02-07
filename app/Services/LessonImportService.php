<?php

namespace App\Services;

use App\Models\Lesson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                if ($this->isMissingRequiredFields($lessonData)) {
                    $result['errors'][] = "Lesson at index {$index}: Missing required fields (content or type)";

                    continue;
                }

                $validation = $this->validationService->validateIsGeneric($lessonData['content']);
                if (! $validation['is_valid']) {
                    $errorMessage = "Lesson at index {$index}: ".implode(', ', $validation['errors']);
                    $result['errors'][] = $errorMessage;

                    $this->logValidationErrors($sourceProject, $index, $validation);

                    continue;
                }

                $contentHash = $this->hashService->generateHash($lessonData['content']);

                $existingLesson = Lesson::findByContentHashAcrossProjects($contentHash);

                if ($existingLesson) {
                    $mergedTags = Lesson::mergeTags(
                        $existingLesson->tags ?? [],
                        $lessonData['tags'] ?? []
                    );

                    $mergedMetadata = Lesson::mergeMetadata(
                        $existingLesson->metadata ?? [],
                        $lessonData['metadata'] ?? []
                    );

                    $existingSourceProjects = $existingLesson->source_projects ?? [$existingLesson->source_project];
                    $newSourceProjects = array_unique(array_merge($existingSourceProjects, [$sourceProject]));

                    $title = $existingLesson->title ?? $this->extractTitle($lessonData);
                    $summary = $existingLesson->summary ?? $this->extractSummary($lessonData);
                    $subcategory = $existingLesson->subcategory ?? $this->extractSubcategory($lessonData, $lessonData['category'] ?? $existingLesson->category);

                    if ($this->shouldUpdateExistingLesson($existingLesson, $lessonData, $mergedTags, $mergedMetadata, $newSourceProjects, $title, $summary)) {
                        $this->updateExistingLesson($existingLesson, $lessonData, $mergedTags, $mergedMetadata, $newSourceProjects, $title, $summary, $subcategory, $validation);

                        $result['updated']++;
                    } else {
                        $result['skipped']++;
                    }
                } else {
                    $title = $this->extractTitle($lessonData);
                    $summary = $this->extractSummary($lessonData);
                    $category = $lessonData['category'] ?? null;
                    $subcategory = $this->extractSubcategory($lessonData, $category);

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

                    $this->detectAndCreateRelationships($lesson);
                }
            } catch (\Exception $e) {
                $errorMessage = "Lesson at index {$index}: {$e->getMessage()}";
                $result['errors'][] = $errorMessage;

                $this->logProcessingError($sourceProject, $index, $e);
            }
        }

        return $result;
    }

    /**
     * Process and store project-specific implementation details (no generic validation, same-project dedupe only).
     *
     * @return array{created: int, updated: int, skipped: int, errors: array}
     */
    public function processProjectDetails(array $lessons, string $sourceProject): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($lessons as $index => $lessonData) {
            try {
                if ($this->isMissingRequiredFields($lessonData)) {
                    $result['errors'][] = "Lesson at index {$index}: Missing required fields (content or type)";

                    continue;
                }

                $contentHash = $this->hashService->generateHash($lessonData['content']);
                $existingLesson = Lesson::findByContentHash($contentHash, $sourceProject);

                if ($existingLesson) {
                    $mergedTags = Lesson::mergeTags(
                        $existingLesson->tags ?? [],
                        $lessonData['tags'] ?? []
                    );
                    $mergedMetadata = Lesson::mergeMetadata(
                        $existingLesson->metadata ?? [],
                        $lessonData['metadata'] ?? []
                    );
                    $title = $existingLesson->title ?? $this->extractTitle($lessonData);
                    $summary = $existingLesson->summary ?? $this->extractSummary($lessonData);
                    $subcategory = $existingLesson->subcategory ?? $this->extractSubcategory($lessonData, $lessonData['category'] ?? $existingLesson->category);

                    if ($this->shouldUpdateExistingLesson($existingLesson, $lessonData, $mergedTags, $mergedMetadata, [$sourceProject], $title, $summary)) {
                        $this->updateExistingProjectDetail($existingLesson, $lessonData, $mergedTags, $mergedMetadata, $title, $summary, $subcategory);
                        $result['updated']++;
                    } else {
                        $result['skipped']++;
                    }
                } else {
                    $title = $this->extractTitle($lessonData);
                    $summary = $this->extractSummary($lessonData);
                    $category = $lessonData['category'] ?? null;
                    $subcategory = $this->extractSubcategory($lessonData, $category);

                    Lesson::create([
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
                        'is_generic' => false,
                    ]);
                    $result['created']++;
                }
            } catch (\Exception $e) {
                $errorMessage = "Lesson at index {$index}: {$e->getMessage()}";
                $result['errors'][] = $errorMessage;
                $this->logProcessingError($sourceProject, $index, $e);
            }
        }

        return $result;
    }

    /**
     * Check if metadata has changed.
     */
    protected function hasMetadataChanged(array $existing, array $new): bool
    {
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
        if (! empty($lessonData['title'])) {
            return $lessonData['title'];
        }

        if (! empty($lessonData['metadata']['title'])) {
            return $lessonData['metadata']['title'];
        }

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
        if (! empty($lessonData['summary'])) {
            return $lessonData['summary'];
        }

        if (! empty($lessonData['metadata']['summary'])) {
            return $lessonData['metadata']['summary'];
        }

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

            return $this->generateSummaryFromContent($content);
        }

        return null;
    }

    protected function generateSummaryFromContent(string $content): ?string
    {
        $text = strip_tags($content);
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, 3);
        if (count($sentences) >= 2) {
            return trim(implode(' ', array_slice($sentences, 0, 2)));
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

        $textToSearch = $this->getTextToSearch($lessonData);
        if (empty($textToSearch)) {
            return null;
        }

        $subcategoryKeywords = $this->getSubcategoryKeywords();

        if (! isset($subcategoryKeywords[$category])) {
            return null;
        }

        $keywords = $subcategoryKeywords[$category];
        $scores = $this->calculateSubcategoryScores($textToSearch, $keywords);

        if (empty($scores)) {
            return null;
        }

        arsort($scores);

        return array_key_first($scores);
    }

    protected function getTextToSearch(array $lessonData): ?string
    {
        if (! empty($lessonData['summary'])) {
            return strtolower($lessonData['summary']);
        }

        if (! empty($lessonData['content'])) {
            return strtolower(substr($lessonData['content'], 0, 1000));
        }

        return null;
    }

    protected function calculateSubcategoryScores(string $textToSearch, array $keywords): array
    {
        $scores = [];
        foreach ($keywords as $subcategory => $keywordList) {
            $score = 0;
            foreach ($keywordList as $keyword) {
                if (str_contains($textToSearch, strtolower($keyword))) {
                    $score += strlen($keyword);
                }
            }
            if ($score > 0) {
                $scores[$subcategory] = $score;
            }
        }

        return $scores;
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

        $similarLessons = Lesson::query()
            ->generic()
            ->where('id', '!=', $lesson->id)
            ->where('category', $lesson->category)
            ->byTags($lesson->tags)
            ->limit(10)
            ->get();

        foreach ($similarLessons as $similarLesson) {
            $relevanceScore = $this->calculateTagOverlapScore($lesson->tags, $similarLesson->tags ?? []);

            if ($relevanceScore >= 0.3 && ! $this->relationshipExists($lesson->id, $similarLesson->id)) {
                $this->createRelationship($lesson->id, $similarLesson->id, $relevanceScore);
            }
        }
    }

    protected function relationshipExists(string $lessonId, string $relatedLessonId): bool
    {
        return DB::table('lesson_relationships')
            ->where('lesson_id', $lessonId)
            ->where('related_lesson_id', $relatedLessonId)
            ->exists();
    }

    protected function createRelationship(string $lessonId, string $relatedLessonId, float $relevanceScore): void
    {
        DB::table('lesson_relationships')->insert([
            'id' => Str::uuid(),
            'lesson_id' => $lessonId,
            'related_lesson_id' => $relatedLessonId,
            'relationship_type' => 'related',
            'relevance_score' => $relevanceScore,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    protected function isMissingRequiredFields(array $lessonData): bool
    {
        return empty($lessonData['content']) || empty($lessonData['type']);
    }

    protected function logValidationErrors(string $sourceProject, int $index, array $validation): void
    {
        Log::warning('Lesson failed generic validation', [
            'source_project' => $sourceProject,
            'lesson_index' => $index,
            'errors' => $validation['errors'],
            'warnings' => $validation['warnings'] ?? [],
        ]);
    }

    protected function logProcessingError(string $sourceProject, int $index, \Exception $e): void
    {
        Log::warning('Failed to process individual lesson', [
            'source_project' => $sourceProject,
            'lesson_index' => $index,
            'error' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ]);
    }

    protected function shouldUpdateExistingLesson(
        Lesson $existingLesson,
        array $lessonData,
        array $mergedTags,
        array $mergedMetadata,
        array $newSourceProjects,
        ?string $title,
        ?string $summary
    ): bool {
        $tagsChanged = $mergedTags !== ($existingLesson->tags ?? []);
        $metadataChanged = $this->hasMetadataChanged(
            $existingLesson->metadata ?? [],
            $lessonData['metadata'] ?? []
        );
        $existingSourceProjects = $existingLesson->source_projects ?? [$existingLesson->source_project];
        $sourceProjectsChanged = $newSourceProjects !== $existingSourceProjects;
        $categoryChanged = ($lessonData['category'] ?? null) !== $existingLesson->category;
        $titleChanged = (! empty($lessonData['title']) || ! empty($lessonData['metadata']['title'])) &&
            $title !== $existingLesson->title;
        $summaryChanged = (! empty($lessonData['summary']) || ! empty($lessonData['metadata']['summary']) || ! empty($lessonData['metadata']['description'])) &&
            $summary !== $existingLesson->summary;

        return $tagsChanged || $metadataChanged || $sourceProjectsChanged || $categoryChanged || $titleChanged || $summaryChanged;
    }

    protected function updateExistingLesson(
        Lesson $existingLesson,
        array $lessonData,
        array $mergedTags,
        array $mergedMetadata,
        array $newSourceProjects,
        ?string $title,
        ?string $summary,
        ?string $subcategory,
        array $validation
    ): void {
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
    }

    protected function updateExistingProjectDetail(
        Lesson $existingLesson,
        array $lessonData,
        array $mergedTags,
        array $mergedMetadata,
        ?string $title,
        ?string $summary,
        ?string $subcategory
    ): void {
        $existingLesson->update([
            'category' => $lessonData['category'] ?? $existingLesson->category,
            'subcategory' => $subcategory,
            'title' => $title,
            'summary' => $summary,
            'tags' => $mergedTags,
            'metadata' => $mergedMetadata,
            'is_generic' => false,
        ]);
    }
}

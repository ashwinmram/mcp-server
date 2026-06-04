<?php

namespace LaravelMcpPusher\Services;

use Illuminate\Support\Facades\File;
use LaravelMcpPusher\Data\CollectedKnowledge;

class KnowledgeCollector
{
    public function __construct(
        protected JsonlDraftService $jsonlDraftService,
        protected ContentTagExtractor $tagExtractor,
    ) {}

    public function collectGeneric(
        ?string $lessonsLearnedPath = null,
        ?string $lessonsJsonPath = null,
        ?string $draftJsonlPath = null,
    ): CollectedKnowledge {
        $lessonsLearnedPath ??= config('mcp-pusher.lessons_learned_path');
        $lessonsJsonPath ??= config('mcp-pusher.lessons_learned_json_path');
        $draftJsonlPath ??= config('mcp-pusher.generic_draft_jsonl');

        $lessons = [];
        $sourcesToTruncate = [];

        if (File::exists($lessonsLearnedPath)) {
            $content = File::get($lessonsLearnedPath);

            if (! empty(trim($content))) {
                $lessons[] = [
                    'type' => 'markdown',
                    'category' => 'guidelines',
                    'tags' => ['laravel', 'lessons-learned', 'guidelines', 'best-practices', 'markdown'],
                    'content' => $content,
                    'metadata' => [
                        'file' => 'lessons-learned.md',
                        'path' => $lessonsLearnedPath,
                    ],
                ];
                $sourcesToTruncate[] = ['path' => $lessonsLearnedPath, 'kind' => 'markdown_empty'];
            }
        }

        if (File::exists($lessonsJsonPath)) {
            $jsonData = json_decode(File::get($lessonsJsonPath), true);

            if (is_array($jsonData) && $jsonData !== []) {
                foreach ($jsonData as $index => $item) {
                    $lesson = $this->normalizeGenericJsonItem($item, $lessonsJsonPath, $index);

                    if ($lesson !== null) {
                        $lessons[] = $lesson;
                    }
                }

                $sourcesToTruncate[] = ['path' => $lessonsJsonPath, 'kind' => 'json_array'];
            }
        }

        $draftEntries = $this->jsonlDraftService->read($draftJsonlPath);

        foreach ($draftEntries as $index => $item) {
            $lesson = $this->normalizeGenericJsonItem($item, $draftJsonlPath, $index, isDraft: true);

            if ($lesson !== null) {
                $lessons[] = $lesson;
            }
        }

        if ($draftEntries !== []) {
            $sourcesToTruncate[] = ['path' => $draftJsonlPath, 'kind' => 'jsonl_clear'];
        }

        return new CollectedKnowledge($lessons, $sourcesToTruncate);
    }

    public function collectProject(
        ?string $mdPath = null,
        ?string $jsonDir = null,
        ?string $draftJsonlPath = null,
    ): CollectedKnowledge {
        $mdPath ??= config('mcp-pusher.project_details_path');
        $jsonDir ??= config('mcp-pusher.project_details_json_dir');
        $draftJsonlPath ??= config('mcp-pusher.project_draft_jsonl');

        $lessons = [];
        $sourcesToTruncate = [];

        if (File::exists($mdPath)) {
            $content = File::get($mdPath);

            if (! empty(trim($content))) {
                $lessons[] = [
                    'type' => 'markdown',
                    'category' => 'project-implementation',
                    'tags' => ['project-details', 'implementation', 'markdown'],
                    'content' => $content,
                    'metadata' => [
                        'file' => 'project-details.md',
                        'path' => $mdPath,
                    ],
                ];
                $sourcesToTruncate[] = ['path' => $mdPath, 'kind' => 'markdown_empty'];
            }
        }

        if (File::isDirectory($jsonDir)) {
            $jsonFiles = array_unique(array_merge(
                File::glob($jsonDir.'/project_details.json') ?: [],
                File::glob($jsonDir.'/project_details_*.json') ?: [],
            ));

            foreach ($jsonFiles as $file) {
                $jsonData = json_decode(File::get($file), true);

                if (! is_array($jsonData) || $jsonData === []) {
                    continue;
                }

                $filename = basename($file);

                foreach ($jsonData as $index => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $type = $item['type'] ?? 'project_detail';

                    if (! in_array($type, ['cursor', 'ai_output', 'manual', 'markdown', 'project_detail'], true)) {
                        $type = 'project_detail';
                    }

                    $lessons[] = [
                        'type' => $type,
                        'category' => $item['category'] ?? 'project-implementation',
                        'subcategory' => $item['subcategory'] ?? null,
                        'title' => $item['title'] ?? null,
                        'summary' => $item['summary'] ?? null,
                        'tags' => $item['tags'] ?? ['project-details'],
                        'content' => $item['content'] ?? json_encode($item, JSON_PRETTY_PRINT),
                        'metadata' => $item['metadata'] ?? ['file' => $filename, 'index' => $index],
                    ];
                }

                $sourcesToTruncate[] = ['path' => $file, 'kind' => 'json_array'];
            }
        }

        foreach ($this->jsonlDraftService->read($draftJsonlPath) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $type = $item['type'] ?? 'project_detail';

            if (! in_array($type, ['cursor', 'ai_output', 'manual', 'markdown', 'project_detail'], true)) {
                $type = 'project_detail';
            }

            $lessons[] = [
                'type' => $type,
                'category' => $item['category'] ?? 'project-implementation',
                'subcategory' => $item['subcategory'] ?? null,
                'title' => $item['title'] ?? null,
                'summary' => $item['summary'] ?? null,
                'tags' => $item['tags'] ?? ['project-details'],
                'content' => $item['content'] ?? json_encode($item, JSON_PRETTY_PRINT),
                'metadata' => array_merge($item['metadata'] ?? [], ['file' => basename($draftJsonlPath), 'index' => $index, 'draft' => true]),
            ];
        }

        if ($this->jsonlDraftService->read($draftJsonlPath) !== []) {
            $sourcesToTruncate[] = ['path' => $draftJsonlPath, 'kind' => 'jsonl_clear'];
        }

        return new CollectedKnowledge($lessons, $sourcesToTruncate);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function normalizeGenericJsonItem(mixed $item, string $sourcePath, int $index, bool $isDraft = false): ?array
    {
        if (is_string($item)) {
            $lessonContent = $item;
            $contentTags = $this->tagExtractor->extract($lessonContent, []);

            return [
                'type' => 'ai_output',
                'category' => 'guidelines',
                'tags' => array_values(array_unique(array_merge($contentTags, ['laravel']))),
                'content' => $lessonContent,
                'metadata' => [
                    'file' => basename($sourcePath),
                    'path' => $sourcePath,
                    'index' => $index,
                    'draft' => $isDraft,
                ],
            ];
        }

        if (! is_array($item)) {
            return null;
        }

        $lessonContent = $item['content'] ?? json_encode($item, JSON_PRETTY_PRINT);
        $contentTags = $this->tagExtractor->extract($lessonContent, $item['tags'] ?? []);
        $tags = array_values(array_unique(array_merge($contentTags, $item['tags'] ?? [], ['laravel'])));

        $lesson = [
            'type' => $item['type'] ?? 'ai_output',
            'category' => $item['category'] ?? 'guidelines',
            'tags' => $tags,
            'content' => $lessonContent,
            'metadata' => array_merge(
                $item['metadata'] ?? [],
                ['file' => basename($sourcePath), 'path' => $sourcePath, 'index' => $index, 'draft' => $isDraft]
            ),
        ];

        if (! empty($item['title'])) {
            $lesson['title'] = $item['title'];
        }

        if (! empty($item['summary'])) {
            $lesson['summary'] = $item['summary'];
        }

        if (array_key_exists('subcategory', $item)) {
            $lesson['subcategory'] = $item['subcategory'];
        }

        return $lesson;
    }
}

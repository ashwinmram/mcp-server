<?php

namespace LaravelMcpPusher\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use LaravelMcpPusher\Services\LessonPusherService;

class PushLessons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:push-lessons
                            {--source= : Source project name (default: project directory name)}
                            {--lessons-learned= : Path to lessons-learned.md file (default: docs/lessons-learned.md)}
                            {--lessons-json= : Path to lessons_learned.json file (default: docs/lessons_learned.json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert lessons-learned.md and lessons_learned.json files to properly formatted lessons with categories/tags and push to MCP server';

    /**
     * Execute the console command.
     */
    public function handle(LessonPusherService $pusherService): int
    {
        $sourceProject = $this->option('source') ?? basename(base_path());
        $lessonsLearnedPath = $this->option('lessons-learned') ?? base_path('docs/lessons-learned.md');
        $lessonsJsonPath = $this->option('lessons-json') ?? base_path('docs/lessons_learned.json');

        $this->info("Converting and pushing lessons from project: {$sourceProject}");
        $this->newLine();

        $lessons = [];
        $filesToEmpty = [];

        // Process lessons-learned.md file
        if (File::exists($lessonsLearnedPath)) {
            $this->info("Reading lessons-learned.md file: {$lessonsLearnedPath}");
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
                $filesToEmpty[] = ['path' => $lessonsLearnedPath, 'type' => 'lessons-learned'];
                $this->info('  ✓ Converted lessons-learned.md');
            } else {
                $this->warn('  ⚠ lessons-learned.md file is empty');
            }
        } else {
            $this->warn("  ⚠ lessons-learned.md file not found at: {$lessonsLearnedPath}");
        }

        // Process lessons_learned.json file
        if (File::exists($lessonsJsonPath)) {
            $this->info("Reading lessons_learned.json file: {$lessonsJsonPath}");

            try {
                $content = File::get($lessonsJsonPath);
                $jsonData = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->error('    ✗ Invalid JSON: '.json_last_error_msg());
                } elseif (! is_array($jsonData)) {
                    $this->warn('    ⚠ Expected JSON array, skipping');
                } else {
                    foreach ($jsonData as $index => $item) {
                        if (is_string($item)) {
                            $lessonContent = $item;
                            $contentTags = $this->extractTagsFromContent($lessonContent, []);
                            $lessons[] = [
                                'type' => 'ai_output',
                                'category' => 'guidelines',
                                'tags' => array_values(array_unique(array_merge($contentTags, ['laravel']))),
                                'content' => $lessonContent,
                                'metadata' => ['file' => 'lessons_learned.json', 'path' => $lessonsJsonPath, 'index' => $index],
                            ];
                        } elseif (is_array($item)) {
                            $lessonContent = $item['content'] ?? json_encode($item, JSON_PRETTY_PRINT);
                            $contentTags = $this->extractTagsFromContent($lessonContent, $item['tags'] ?? []);
                            $tags = array_values(array_unique(array_merge(
                                $contentTags,
                                $item['tags'] ?? [],
                                ['laravel']
                            )));
                            $lesson = [
                                'type' => $item['type'] ?? 'ai_output',
                                'category' => $item['category'] ?? 'guidelines',
                                'tags' => $tags,
                                'content' => $lessonContent,
                                'metadata' => array_merge(
                                    $item['metadata'] ?? [],
                                    ['file' => 'lessons_learned.json', 'path' => $lessonsJsonPath, 'index' => $index]
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
                            $lessons[] = $lesson;
                        }
                    }

                    if (count($jsonData) > 0) {
                        $filesToEmpty[] = ['path' => $lessonsJsonPath, 'type' => 'lessons_json'];
                        $this->info('  ✓ Converted '.count($jsonData).' lesson(s) from lessons_learned.json');
                    }
                }
            } catch (\Exception $e) {
                $this->error("    ✗ Error processing file: {$e->getMessage()}");
            }
        } else {
            $this->warn("  ⚠ lessons_learned.json file not found at: {$lessonsJsonPath}");
        }

        if (empty($lessons)) {
            $this->error('No lessons found to convert and push.');

            return Command::FAILURE;
        }

        $count = count($lessons);
        $this->newLine();
        $this->info("Pushing {$count} lesson(s) to MCP server...");

        try {
            $response = $pusherService->pushLessons($lessons, $sourceProject);

            if ($response->successful()) {
                $data = $response->json();
                $result = $data['data'] ?? [];

                $this->newLine();
                $this->info('Push Summary:');
                $created = $result['created'] ?? 0;
                $updated = $result['updated'] ?? 0;
                $skipped = $result['skipped'] ?? 0;
                $this->line("  Created: {$created}");
                $this->line("  Updated: {$updated}");
                $this->line("  Skipped: {$skipped}");

                if (! empty($result['errors'])) {
                    $this->newLine();
                    $this->warn('Warnings:');
                    foreach ($result['errors'] as $error) {
                        $this->warn("  - {$error}");
                    }
                }

                // Display summary by category
                $this->newLine();
                $this->info('Summary by category:');
                $categories = collect($lessons)->groupBy('category')->map->count();
                foreach ($categories as $category => $categoryCount) {
                    $this->line("  {$category}: {$categoryCount} lesson(s)");
                }

                $this->newLine();
                $this->info('✓ Conversion and push completed successfully!');

                // Empty the source files after successful push
                $this->emptySourceFiles($filesToEmpty);

                return Command::SUCCESS;
            } else {
                $responseData = $response->json();
                $error = $responseData['message'] ?? 'Unknown error';
                $status = $response->status();
                $this->error("Failed to push lessons: {$error}");
                $this->error("Status: {$status}");

                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Error pushing lessons: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }

    /**
     * Extract additional tags from lesson content.
     */
    protected function extractTagsFromContent(string $content, array $baseTags): array
    {
        $tags = $baseTags;

        // Extract additional tags based on content keywords
        $contentLower = strtolower($content);

        // Common Laravel/testing keywords
        $keywordTags = [
            'http::fake' => 'http-mocking',
            'facade' => 'facades',
            'pest' => 'pest',
            'phpunit' => 'phpunit',
            'refreshdatabase' => 'database',
            'feature test' => 'feature-test',
            'unit test' => 'unit-test',
            'artisan command' => 'artisan',
            'service provider' => 'service-provider',
            'composer' => 'composer',
            'package' => 'package',
            'string interpolation' => 'php',
            'null coalescing' => 'php',
        ];

        foreach ($keywordTags as $keyword => $tag) {
            if (str_contains($contentLower, $keyword) && ! in_array($tag, $tags)) {
                $tags[] = $tag;
            }
        }

        return array_values(array_unique($tags));
    }

    /**
     * Empty the source files after successful push.
     *
     * @param  array  $filesToEmpty  Array of files to empty with 'path' and 'type' keys
     */
    protected function emptySourceFiles(array $filesToEmpty): void
    {
        if (empty($filesToEmpty)) {
            return;
        }

        $this->newLine();
        $this->info('Emptying source files...');

        foreach ($filesToEmpty as $fileInfo) {
            $path = $fileInfo['path'];
            $type = $fileInfo['type'];

            try {
                if ($type === 'lessons-learned') {
                    File::put($path, '');
                    $this->line("  ✓ Emptied: {$path}");
                } elseif ($type === 'lessons_json') {
                    File::put($path, "[]\n");
                    $this->line("  ✓ Emptied: {$path}");
                }
            } catch (\Exception $e) {
                $this->warn("  ⚠ Failed to empty {$path}: {$e->getMessage()}");
            }
        }

        $this->info('✓ Source files emptied successfully');
    }
}

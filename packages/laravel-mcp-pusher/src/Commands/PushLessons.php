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
                            {--lessons-learned= : Path to lessons-learned.md file (default: project root)}
                            {--ai-json-dir= : Directory containing AI_*.json files (default: docs)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert lessons-learned.md and AI_*.json files to properly formatted lessons with categories/tags and push to MCP server';

    /**
     * Execute the console command.
     */
    public function handle(LessonPusherService $pusherService): int
    {
        $sourceProject = $this->option('source') ?? basename(base_path());
        $lessonsLearnedPath = $this->option('lessons-learned') ?? base_path('lessons-learned.md');
        $aiJsonDir = $this->option('ai-json-dir') ?? 'docs';
        $aiJsonPath = base_path($aiJsonDir);

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

        // Process AI_*.json files
        if (File::isDirectory($aiJsonPath)) {
            $this->info("Searching for AI_*.json files in: {$aiJsonPath}");
            $aiFiles = File::glob($aiJsonPath.'/AI_*.json');

            if (empty($aiFiles)) {
                $this->warn('  ⚠ No AI_*.json files found');
            } else {
                foreach ($aiFiles as $file) {
                    $filename = basename($file);
                    $this->info("  Processing: {$filename}");

                    try {
                        $content = File::get($file);
                        $jsonData = json_decode($content, true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $this->error('    ✗ Invalid JSON: '.json_last_error_msg());

                            continue;
                        }

                        // Extract category and tags from filename
                        $categoryInfo = $this->extractCategoryFromFilename($filename);
                        $baseTags = $categoryInfo['tags'];

                        $lessonsBefore = count($lessons);

                        // If it's an array, treat each item as a separate lesson
                        if (is_array($jsonData)) {
                            foreach ($jsonData as $index => $item) {
                                $lessonContent = is_string($item) ? $item : json_encode($item, JSON_PRETTY_PRINT);
                                $contentTags = $this->extractTagsFromContent($lessonContent, $baseTags);

                                $lessons[] = [
                                    'type' => 'ai_output',
                                    'category' => $categoryInfo['category'],
                                    'tags' => $contentTags,
                                    'content' => $lessonContent,
                                    'metadata' => [
                                        'file' => $filename,
                                        'path' => $file,
                                        'index' => $index,
                                    ],
                                ];
                            }
                            $this->info('    ✓ Converted '.count($jsonData).' lesson(s)');
                        } else {
                            // Single object or string
                            $lessonContent = is_string($jsonData) ? $jsonData : json_encode($jsonData, JSON_PRETTY_PRINT);
                            $contentTags = $this->extractTagsFromContent($lessonContent, $baseTags);

                            $lessons[] = [
                                'type' => 'ai_output',
                                'category' => $categoryInfo['category'],
                                'tags' => $contentTags,
                                'content' => $lessonContent,
                                'metadata' => [
                                    'file' => $filename,
                                    'path' => $file,
                                ],
                            ];
                            $this->info('    ✓ Converted 1 lesson');
                        }

                        // Track this file for emptying after successful push (only if lessons were created)
                        if (count($lessons) > $lessonsBefore) {
                            $filesToEmpty[] = ['path' => $file, 'type' => 'ai_json'];
                        }
                    } catch (\Exception $e) {
                        $this->error("    ✗ Error processing file: {$e->getMessage()}");
                    }
                }
            }
        } else {
            $this->warn("  ⚠ Directory not found: {$aiJsonPath}");
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
     * Extract category and base tags from filename.
     */
    protected function extractCategoryFromFilename(string $filename): array
    {
        // Remove AI_ prefix and .json extension
        $name = str_replace(['AI_', '.json'], '', $filename);
        $name = strtolower($name);

        // Convert underscores to hyphens for category
        $category = str_replace('_', '-', $name);

        // Generate base tags from filename parts
        $parts = explode('_', str_replace(['AI_', '.json'], '', $filename));
        $tags = array_map('strtolower', $parts);
        $tags[] = 'laravel';

        // Add specific tags based on category patterns
        if (str_contains($category, 'package')) {
            $tags[] = 'package-development';
        }
        if (str_contains($category, 'test')) {
            $tags[] = 'testing';
            $tags[] = 'pest';
        }
        if (str_contains($category, 'php')) {
            $tags[] = 'php';
            $tags[] = 'syntax';
        }
        if (str_contains($category, 'syntax')) {
            $tags[] = 'php';
            $tags[] = 'syntax';
        }

        return [
            'category' => $category,
            'tags' => array_values(array_unique($tags)), // Re-index to ensure array format
        ];
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

        return array_values(array_unique($tags)); // Re-index to ensure array format
    }

    /**
     * Empty the source files after successful push.
     *
     * @param  array  $filesToEmpty  Array of files to empty with 'path' and 'type' keys
     */
    protected function emptySourceFiles(array $filesToEmpty): void
    {
        if (empty($filesToEmpty)) {
            // No files to empty - this is normal if files were already empty or didn't produce lessons
            return;
        }

        $this->newLine();
        $this->info('Emptying source files...');

        foreach ($filesToEmpty as $fileInfo) {
            $path = $fileInfo['path'];
            $type = $fileInfo['type'];

            try {
                if ($type === 'lessons-learned') {
                    // Empty lessons-learned.md file
                    File::put($path, '');
                    $this->line("  ✓ Emptied: {$path}");
                } elseif ($type === 'ai_json') {
                    // Empty AI_*.json file with empty array
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

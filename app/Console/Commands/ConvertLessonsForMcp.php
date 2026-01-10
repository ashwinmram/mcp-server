<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ConvertLessonsForMcp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:convert-lessons
                            {--cursorrules= : Path to .cursorrules file (default: project root)}
                            {--ai-json-dir=docs : Directory containing AI_*.json files}
                            {--output= : Output file path (optional, displays to console if not provided)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert .cursorrules and AI_*.json files to properly formatted lessons with categories and tags';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cursorRulesPath = $this->option('cursorrules') ?? base_path('.cursorrules');
        $aiJsonDir = $this->option('ai-json-dir') ?? 'docs';
        $aiJsonPath = base_path($aiJsonDir);
        $outputPath = $this->option('output');

        $this->info('Converting lessons for MCP format...');
        $this->newLine();

        $lessons = [];

        // Process .cursorrules file
        if (File::exists($cursorRulesPath)) {
            $this->info("Reading .cursorrules file: {$cursorRulesPath}");
            $content = File::get($cursorRulesPath);

            if (! empty(trim($content))) {
                $lessons[] = [
                    'type' => 'cursor',
                    'category' => 'guidelines',
                    'tags' => ['laravel', 'cursor', 'rules', 'guidelines', 'best-practices'],
                    'content' => $content,
                    'metadata' => [
                        'file' => '.cursorrules',
                        'path' => $cursorRulesPath,
                    ],
                ];
                $this->info('  ✓ Converted .cursorrules');
            } else {
                $this->warn('  ⚠ .cursorrules file is empty');
            }
        } else {
            $this->warn("  ⚠ .cursorrules file not found at: {$cursorRulesPath}");
        }

        // Process AI_*.json files
        if (File::isDirectory($aiJsonPath)) {
            $this->info("Searching for AI_*.json files in: {$aiJsonPath}");
            $aiFiles = File::glob($aiJsonPath.'/AI_*.json');

            if (empty($aiFiles)) {
                $this->warn("  ⚠ No AI_*.json files found");
            } else {
                foreach ($aiFiles as $file) {
                    $filename = basename($file);
                    $this->info("  Processing: {$filename}");

                    try {
                        $content = File::get($file);
                        $jsonData = json_decode($content, true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $this->error("    ✗ Invalid JSON: ".json_last_error_msg());
                            continue;
                        }

                        // Extract category and tags from filename
                        $categoryInfo = $this->extractCategoryFromFilename($filename);
                        $baseTags = $categoryInfo['tags'];

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
                            $this->info("    ✓ Converted ".count($jsonData)." lesson(s)");
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
                    } catch (\Exception $e) {
                        $this->error("    ✗ Error processing file: {$e->getMessage()}");
                    }
                }
            }
        } else {
            $this->warn("  ⚠ Directory not found: {$aiJsonPath}");
        }

        if (empty($lessons)) {
            $this->error('No lessons found to convert.');
            return Command::FAILURE;
        }

        $count = count($lessons);
        $this->newLine();
        $this->info("Converted {$count} lesson(s)");

        // Output results
        $output = json_encode($lessons, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($outputPath) {
            File::put($outputPath, $output);
            $this->info("✓ Lessons saved to: {$outputPath}");
        } else {
            $this->newLine();
            $this->line('Converted lessons (JSON format):');
            $this->line($output);
        }

        // Display summary
        $this->newLine();
        $this->info('Summary by category:');
        $categories = collect($lessons)->groupBy('category')->map->count();
        foreach ($categories as $category => $count) {
            $this->line("  {$category}: {$count} lesson(s)");
        }

        $this->newLine();
        $this->info('✓ Conversion completed successfully!');
        $this->info('You can now use these formatted lessons with mcp:push-lessons or the HTTP API.');

        return Command::SUCCESS;
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
}

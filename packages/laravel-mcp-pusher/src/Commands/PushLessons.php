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
                            {--cursorrules= : Path to .cursorrules file (default: project root)}
                            {--ai-json-dir= : Directory containing AI_*.json files (default: docs)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push lessons from .cursorrules and AI_*.json files to MCP server';

    /**
     * Execute the console command.
     */
    public function handle(LessonPusherService $pusherService): int
    {
        $sourceProject = $this->option('source') ?? basename(base_path());
        $cursorRulesPath = $this->option('cursorrules') ?? base_path('.cursorrules');
        $aiJsonDir = $this->option('ai-json-dir') ?? 'docs';
        $aiJsonPath = base_path($aiJsonDir);

        $this->info("Pushing lessons from project: {$sourceProject}");
        $this->newLine();

        $lessons = [];

        // Read .cursorrules file if it exists
        if (File::exists($cursorRulesPath)) {
            $this->info("Reading .cursorrules file...");
            $content = File::get($cursorRulesPath);

            if (! empty(trim($content))) {
                $lessons[] = [
                    'type' => 'cursor',
                    'content' => $content,
                    'metadata' => [
                        'file' => '.cursorrules',
                    ],
                ];
                $this->info('  ✓ Found .cursorrules content');
            }
        } else {
            $this->warn('  ⚠ .cursorrules file not found');
        }

        // Find and read AI_*.json files
        if (File::isDirectory($aiJsonPath)) {
            $this->info("Searching for AI_*.json files in: {$aiJsonPath}");
            $aiFiles = File::glob($aiJsonPath.'/AI_*.json');

            if (! empty($aiFiles)) {
                foreach ($aiFiles as $file) {
                    $this->info("  Reading: ".basename($file));
                    try {
                        $content = File::get($file);
                        $jsonData = json_decode($content, true);

                        if (json_last_error() === JSON_ERROR_NONE) {
                            // If it's an array, treat each item as a separate lesson
                            if (is_array($jsonData)) {
                                foreach ($jsonData as $index => $item) {
                                    $lessons[] = [
                                        'type' => 'ai_output',
                                        'content' => is_string($item) ? $item : json_encode($item, JSON_PRETTY_PRINT),
                                        'metadata' => [
                                            'file' => basename($file),
                                            'index' => $index,
                                        ],
                                    ];
                                }
                            } else {
                                // Single object or string
                                $lessons[] = [
                                    'type' => 'ai_output',
                                    'content' => is_string($jsonData) ? $jsonData : json_encode($jsonData, JSON_PRETTY_PRINT),
                                    'metadata' => [
                                        'file' => basename($file),
                                    ],
                                ];
                            }
                            $this->info('    ✓ Parsed successfully');
                        } else {
                            $this->error("    ✗ Invalid JSON: ".json_last_error_msg());
                        }
                    } catch (\Exception $e) {
                        $this->error("    ✗ Error reading file: {$e->getMessage()}");
                    }
                }
            } else {
                $this->warn('  ⚠ No AI_*.json files found');
            }
        } else {
            $this->warn("  ⚠ Directory not found: {$aiJsonPath}");
        }

        if (empty($lessons)) {
            $this->error('No lessons found to push.');
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

                $this->newLine();
                $this->info('✓ Push completed successfully!');

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
}

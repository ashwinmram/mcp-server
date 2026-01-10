<?php

namespace App\Console\Commands;

use App\Services\LessonImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportInitialLessons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:import-initial
                            {--source= : Source project name}
                            {--cursorrules= : Path to .cursorrules file (default: project root)}
                            {--ai-json-dir=docs : Directory containing AI_*.json files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import initial lessons from .cursorrules and AI_*.json files';

    /**
     * Execute the console command.
     */
    public function handle(LessonImportService $importService): int
    {
        $sourceProject = $this->option('source') ?? $this->ask('Enter source project name', 'default');
        $cursorRulesPath = $this->option('cursorrules') ?? base_path('.cursorrules');
        $aiJsonDir = $this->option('ai-json-dir') ?? 'docs';

        $this->info("Importing lessons from source project: {$sourceProject}");
        $this->newLine();

        $lessons = [];

        // Read .cursorrules file if it exists
        if (File::exists($cursorRulesPath)) {
            $this->info("Reading .cursorrules file: {$cursorRulesPath}");
            $content = File::get($cursorRulesPath);

            if (! empty(trim($content))) {
                $lessons[] = [
                    'type' => 'cursor',
                    'content' => $content,
                    'metadata' => [
                        'file' => '.cursorrules',
                        'path' => $cursorRulesPath,
                    ],
                ];
                $this->info('  ✓ Found .cursorrules content');
            } else {
                $this->warn('  ⚠ .cursorrules file is empty');
            }
        } else {
            $this->warn("  ⚠ .cursorrules file not found at: {$cursorRulesPath}");
        }

        // Find and read AI_*.json files
        $aiJsonPath = base_path($aiJsonDir);
        if (File::isDirectory($aiJsonPath)) {
            $this->info("Searching for AI_*.json files in: {$aiJsonPath}");
            $aiFiles = File::glob($aiJsonPath.'/AI_*.json');

            if (empty($aiFiles)) {
                $this->warn("  ⚠ No AI_*.json files found in {$aiJsonPath}");
            } else {
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
                                            'path' => $file,
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
                                        'path' => $file,
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
            }
        } else {
            $this->warn("  ⚠ Directory not found: {$aiJsonPath}");
        }

        if (empty($lessons)) {
            $this->error('No lessons found to import.');
            return Command::FAILURE;
        }

        $count = count($lessons);
        $this->newLine();
        $this->info("Processing {$count} lesson(s)...");

        // Process lessons using the import service
        $result = $importService->processLessons($lessons, $sourceProject);

        // Display results
        $this->newLine();
        $this->info('Import Summary:');
        $this->line("  Created: {$result['created']}");
        $this->line("  Updated: {$result['updated']}");
        $this->line("  Skipped: {$result['skipped']}");

        if (! empty($result['errors'])) {
            $this->newLine();
            $this->error('Errors:');
            foreach ($result['errors'] as $error) {
                $this->error("  - {$error}");
            }
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('✓ Import completed successfully!');

        return Command::SUCCESS;
    }
}

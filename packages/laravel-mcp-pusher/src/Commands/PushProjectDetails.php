<?php

namespace LaravelMcpPusher\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use LaravelMcpPusher\Services\LessonPusherService;

class PushProjectDetails extends Command
{
    protected $signature = 'mcp:push-project-details
                            {--source= : Source project name (default: project directory name)}
                            {--project-details-file= : Path to project-details.md (default: docs/project-details.md)}
                            {--project-details-json-dir= : Directory containing project_details.json or project_details_*.json (default: docs)}';

    protected $description = 'Convert project-details.md and project_details.json to project implementation details and push to MCP server';

    public function handle(LessonPusherService $pusherService): int
    {
        $sourceProject = $this->option('source') ?? basename(base_path());
        $mdPath = $this->option('project-details-file') ?? base_path('docs/project-details.md');
        $jsonDir = $this->option('project-details-json-dir') ?? 'docs';
        $jsonPath = base_path($jsonDir);

        $this->info("Converting and pushing project details from project: {$sourceProject}");
        $this->newLine();

        $lessons = [];

        if (File::exists($mdPath)) {
            $this->info("Reading project-details.md: {$mdPath}");
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
                $this->info('  ✓ Converted project-details.md');
            } else {
                $this->warn('  ⚠ project-details.md is empty');
            }
        } else {
            $this->warn("  ⚠ project-details.md not found at: {$mdPath}");
        }

        if (File::isDirectory($jsonPath)) {
            $jsonFiles = array_merge(
                File::glob($jsonPath.'/project_details.json'),
                File::glob($jsonPath.'/project_details_*.json')
            );
            $jsonFiles = array_unique($jsonFiles);

            if (! empty($jsonFiles)) {
                foreach ($jsonFiles as $file) {
                    $filename = basename($file);
                    $this->info("  Processing: {$filename}");

                    try {
                        $content = File::get($file);
                        $jsonData = json_decode($content, true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $this->error('    ✗ Invalid JSON: '.json_last_error_msg());

                            continue;
                        }

                        if (! is_array($jsonData)) {
                            $this->warn('    ⚠ Expected JSON array, skipping');

                            continue;
                        }

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
                        $this->info('    ✓ Converted '.count($jsonData).' entry(ies)');
                    } catch (\Exception $e) {
                        $this->error("    ✗ Error: {$e->getMessage()}");
                    }
                }
            } else {
                $this->warn('  ⚠ No project_details.json or project_details_*.json found in '.$jsonPath);
            }
        } else {
            $this->warn("  ⚠ Directory not found: {$jsonPath}");
        }

        if (empty($lessons)) {
            $this->error('No project details found to push.');

            return Command::FAILURE;
        }

        $count = count($lessons);
        $this->newLine();
        $this->info("Pushing {$count} project detail(s) to MCP server...");

        try {
            $response = $pusherService->pushProjectDetails($lessons, $sourceProject);

            if ($response->successful()) {
                $data = $response->json();
                $result = $data['data'] ?? [];
                $this->newLine();
                $this->info('Push Summary:');
                $this->line('  Created: '.($result['created'] ?? 0));
                $this->line('  Updated: '.($result['updated'] ?? 0));
                $this->line('  Skipped: '.($result['skipped'] ?? 0));
                if (! empty($result['errors'])) {
                    $this->newLine();
                    $this->warn('Warnings:');
                    foreach ($result['errors'] as $error) {
                        $this->warn("  - {$error}");
                    }
                }
                $this->newLine();
                $this->info('✓ Project details push completed successfully!');

                return Command::SUCCESS;
            }

            $responseData = $response->json();
            $this->error('Failed to push project details: '.($responseData['message'] ?? 'Unknown error'));

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("Error pushing project details: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}

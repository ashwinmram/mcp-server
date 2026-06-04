<?php

namespace LaravelMcpPusher\Services;

use Illuminate\Console\OutputStyle;

class KnowledgePushOrchestrator
{
    public function __construct(
        protected KnowledgeCollector $collector,
        protected LessonPusherService $pusherService,
        protected KnowledgeSourceTruncator $truncator,
    ) {}

    /**
     * @return array{exit_code: int, generic_pushed: bool, project_pushed: bool}
     */
    public function push(string $sourceProject, bool $truncate, ?OutputStyle $output = null): array
    {
        $generic = $this->collector->collectGeneric();
        $project = $this->collector->collectProject();

        if ($generic->isEmpty() && $project->isEmpty()) {
            $output?->error('No knowledge found to push. Use mcp:append during the session or add docs/lessons and docs/project-details files.');

            return ['exit_code' => 1, 'generic_pushed' => false, 'project_pushed' => false];
        }

        $output?->info("Pushing knowledge from project: {$sourceProject}");
        $output?->newLine();

        if (! $generic->isEmpty()) {
            $output?->info('Pushing '.count($generic->lessons).' generic lesson(s)...');

            $response = $this->pusherService->pushLessons($generic->lessons, $sourceProject);

            if (! $response->successful()) {
                $responseData = $response->json();
                $output?->error('Failed to push lessons: '.($responseData['message'] ?? 'Unknown error'));
                $output?->error('Status: '.$response->status());

                return ['exit_code' => 1, 'generic_pushed' => false, 'project_pushed' => false];
            }

            $this->printSummary($output, 'Lessons', $response->json('data') ?? []);
        }

        if (! $project->isEmpty()) {
            $output?->info('Pushing '.count($project->lessons).' project detail(s)...');

            $response = $this->pusherService->pushProjectDetails($project->lessons, $sourceProject);

            if (! $response->successful()) {
                $responseData = $response->json();
                $output?->error('Failed to push project details: '.($responseData['message'] ?? 'Unknown error'));

                return ['exit_code' => 1, 'generic_pushed' => ! $generic->isEmpty(), 'project_pushed' => false];
            }

            $this->printSummary($output, 'Project details', $response->json('data') ?? []);
        }

        $output?->newLine();
        $output?->info('✓ Knowledge push completed successfully!');

        if ($truncate) {
            $allSources = array_merge($generic->sourcesToTruncate, $project->sourcesToTruncate);
            $this->truncator->truncate($allSources);
            $output?->info('Truncated source file(s) after successful push.');
        }

        return [
            'exit_code' => 0,
            'generic_pushed' => ! $generic->isEmpty(),
            'project_pushed' => ! $project->isEmpty(),
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function printSummary(?OutputStyle $output, string $label, array $result): void
    {
        if ($output === null) {
            return;
        }

        $output->newLine();
        $output->info("{$label} push summary:");
        $output->writeln('  Created: '.($result['created'] ?? 0));
        $output->writeln('  Updated: '.($result['updated'] ?? 0));
        $output->writeln('  Skipped: '.($result['skipped'] ?? 0));

        if (! empty($result['errors'])) {
            $output->warn('  Warnings:');

            foreach ($result['errors'] as $error) {
                $output->warn("    - {$error}");
            }
        }
    }
}

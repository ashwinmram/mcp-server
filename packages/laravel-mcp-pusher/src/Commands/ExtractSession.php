<?php

namespace LaravelMcpPusher\Commands;

use Illuminate\Console\Command;
use LaravelMcpPusher\Services\SessionKnowledgeExtractor;

class ExtractSession extends Command
{
    protected $signature = 'mcp:extract-session
                            {--transcript= : Path to agent transcript JSONL}
                            {--since-git= : Git ref to diff from (e.g. main)}';

    protected $description = 'Fallback: append candidate knowledge from git and/or agent transcript into draft files (review before mcp:push)';

    public function handle(SessionKnowledgeExtractor $extractor): int
    {
        $this->warn('mcp:extract-session is a fallback. Prefer frequent mcp:append during the session.');
        $this->newLine();

        $counts = $extractor->extract(
            $this->option('transcript'),
            $this->option('since-git'),
        );

        $total = $counts['generic'] + $counts['project'];

        if ($total === 0) {
            $this->warn('No candidates extracted. Try --since-git=main or --transcript=/path/to/transcript.jsonl');

            return Command::SUCCESS;
        }

        $this->info("Appended {$counts['generic']} generic and {$counts['project']} project candidate(s) to draft files.");
        $this->line('Review docs/.mcp-session/*-draft.jsonl, then run: php artisan mcp:push --source='.basename(base_path()));

        return Command::SUCCESS;
    }
}

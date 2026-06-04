<?php

namespace LaravelMcpPusher\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use LaravelMcpPusher\Services\SessionKnowledgeExtractor;

class ExtractSession extends Command
{
    protected $signature = 'mcp:extract-session
                            {--since-git=HEAD~1 : Git ref to start the commit range (e.g. HEAD~1, HEAD~7, main)}';

    protected $description = 'Git-only fallback: append candidate knowledge from committed git history into draft files (review before mcp:push)';

    public function handle(SessionKnowledgeExtractor $extractor): int
    {
        $this->warn('mcp:extract-session is a fallback. Prefer frequent mcp:append during the session.');
        $this->newLine();

        $sinceGit = (string) $this->option('since-git');

        try {
            $counts = $extractor->extract($sinceGit);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return Command::FAILURE;
        }

        $total = $counts['generic'] + $counts['project'];

        if ($total === 0) {
            $this->error("No candidates extracted for {$sinceGit}..HEAD. Commit your work first or try a deeper range (e.g. --since-git=HEAD~7).");

            return Command::FAILURE;
        }

        $this->info("Appended {$counts['generic']} generic and {$counts['project']} project candidate(s) to draft files (range: {$sinceGit}..HEAD).");
        $this->line('Review docs/.mcp-session/*-draft.jsonl, then run: php artisan mcp:push --source='.basename(base_path()));

        return Command::SUCCESS;
    }
}

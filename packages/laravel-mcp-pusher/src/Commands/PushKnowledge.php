<?php

namespace LaravelMcpPusher\Commands;

use Illuminate\Console\Command;
use LaravelMcpPusher\Services\KnowledgePushOrchestrator;

class PushKnowledge extends Command
{
    protected $signature = 'mcp:push
                            {--source= : Source project name (default: project directory name)}
                            {--no-truncate : Do not truncate source files after a successful push}';

    protected $description = 'Push generic lessons and project details to the MCP server in one command';

    public function handle(KnowledgePushOrchestrator $orchestrator): int
    {
        $sourceProject = $this->option('source') ?? basename(base_path());
        $truncate = ! $this->option('no-truncate');

        $result = $orchestrator->push($sourceProject, $truncate, $this->output);

        return $result['exit_code'];
    }
}

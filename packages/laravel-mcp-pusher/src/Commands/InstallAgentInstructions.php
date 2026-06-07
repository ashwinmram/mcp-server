<?php

namespace LaravelMcpPusher\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallAgentInstructions extends Command
{
    protected $signature = 'mcp:install-agent-instructions
                            {clients?* : Clients to install: cursor, claude, antigravity (default: all)}
                            {--force : Overwrite existing files}
                            {--with-hooks : Cursor only — install preCompact hook}
                            {--with-cursorrules : Cursor only — copy .cursorrules index}
                            {--with-claude-md : Claude only — copy CLAUDE.md index}
                            {--global : Antigravity only — install global skills}';

    protected $description = 'Install MCP agent instructions for Cursor, Claude Code, and/or Google Antigravity';

    /**
     * @var array<string, string>
     */
    protected array $clientCommands = [
        'cursor' => 'mcp:install-cursor-rules',
        'claude' => 'mcp:install-claude-instructions',
        'antigravity' => 'mcp:install-antigravity-skills',
    ];

    public function handle(): int
    {
        $clients = $this->argument('clients');

        if ($clients === [] || $clients === null) {
            $clients = array_keys($this->clientCommands);
        }

        $unknown = array_diff($clients, array_keys($this->clientCommands));

        if ($unknown !== []) {
            $this->error('Unknown clients: '.implode(', ', $unknown));
            $this->line('Valid clients: '.implode(', ', array_keys($this->clientCommands)));

            return Command::FAILURE;
        }

        $exitCode = Command::SUCCESS;

        foreach ($clients as $client) {
            $this->newLine();
            $this->components->twoColumnDetail('<fg=cyan>'.$client.'</>', 'installing…');

            $options = ['--force' => $this->option('force')];

            if ($client === 'cursor') {
                $options['--with-hooks'] = $this->option('with-hooks');
                $options['--with-cursorrules'] = $this->option('with-cursorrules');
            }

            if ($client === 'claude') {
                $options['--with-claude-md'] = $this->option('with-claude-md');
            }

            if ($client === 'antigravity') {
                $options['--global'] = $this->option('global');
            }

            $result = Artisan::call($this->clientCommands[$client], $options, $this->output);

            if ($result !== Command::SUCCESS) {
                $exitCode = Command::FAILURE;
            }
        }

        return $exitCode;
    }
}

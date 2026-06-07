<?php

namespace LaravelMcpPusher\Commands;

use Illuminate\Console\Command;
use LaravelMcpPusher\Support\AgentInstructionInstaller;

class InstallClaudeInstructions extends Command
{
    protected $signature = 'mcp:install-claude-instructions
                            {--force : Overwrite existing instruction files}
                            {--with-claude-md : Copy CLAUDE.md.example to CLAUDE.md if missing}';

    protected $description = 'Install Claude Code instructions for MCP session startup and knowledge capture';

    /**
     * @var array<int, string>
     */
    protected array $ruleFiles = [
        'mcp-session-startup.md',
        'mcp-session-capture.md',
    ];

    public function handle(AgentInstructionInstaller $installer): int
    {
        $stubsPath = $installer->stubsPath('claude-instructions');
        $rulesPath = base_path('.claude/rules');

        if (! is_dir($stubsPath)) {
            $this->error("Claude instruction stubs not found at: {$stubsPath}");

            return Command::FAILURE;
        }

        $installed = 0;
        $skipped = 0;

        foreach ($this->ruleFiles as $file) {
            $result = $installer->copyFile(
                $stubsPath.DIRECTORY_SEPARATOR.$file,
                $rulesPath.DIRECTORY_SEPARATOR.$file,
                (bool) $this->option('force'),
            );

            if ($result['missing'] ?? false) {
                $this->warn("  Stub missing: {$file}");

                continue;
            }

            if ($result['installed']) {
                $this->info("  Installed: .claude/rules/{$file}");
                $installed++;
            } else {
                $this->line("  Skipped (exists): .claude/rules/{$file}");
                $skipped++;
            }
        }

        if ($this->option('with-claude-md')) {
            $this->installClaudeMd($installer, $stubsPath);
        }

        $this->newLine();
        $this->components->info("Claude instructions: {$installed} installed, {$skipped} skipped.");
        $this->printNextSteps();

        return Command::SUCCESS;
    }

    protected function installClaudeMd(AgentInstructionInstaller $installer, string $stubsPath): void
    {
        $result = $installer->copyFile(
            $stubsPath.DIRECTORY_SEPARATOR.'CLAUDE.md.example',
            base_path('CLAUDE.md'),
            (bool) $this->option('force'),
        );

        if ($result['missing'] ?? false) {
            $this->warn('  Stub missing: CLAUDE.md.example');

            return;
        }

        if ($result['installed']) {
            $this->info('  Installed: CLAUDE.md');
        } else {
            $this->line('  Skipped (exists): CLAUDE.md');
        }
    }

    protected function printNextSteps(): void
    {
        $this->line('Next steps:');
        $this->line('  1. Connect Lessons Learned MCP in Claude Code (see mcp-pusher README).');
        $this->line('  2. Optional Project Details MCP: /mcp/project-details?project=<source>');
        $this->line('  3. Add /docs/.mcp-session/ to .gitignore if not already.');
        $this->line('  4. End of session: review drafts, then php artisan mcp:push --source=<project>');

        if (! $this->option('with-claude-md')) {
            $this->line('  Tip: re-run with --with-claude-md to add a short CLAUDE.md index.');
        }
    }
}

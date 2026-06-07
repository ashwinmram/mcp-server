<?php

namespace LaravelMcpPusher\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCursorRules extends Command
{
    protected $signature = 'mcp:install-cursor-rules
                            {--force : Overwrite existing rule files}
                            {--with-hooks : Install preCompact hook and capture prompt}
                            {--with-cursorrules : Copy cursorrules.example to .cursorrules if missing}';

    protected $description = 'Install Cursor rules for MCP session startup and knowledge capture';

    /**
     * @var array<int, string>
     */
    protected array $ruleFiles = [
        'mcp-session-startup.mdc',
        'mcp-session-capture.mdc',
    ];

    public function handle(): int
    {
        $stubsPath = $this->stubsPath('cursor-rules');
        $rulesPath = base_path('.cursor/rules');

        if (! File::isDirectory($stubsPath)) {
            $this->error("Cursor rule stubs not found at: {$stubsPath}");

            return Command::FAILURE;
        }

        File::ensureDirectoryExists($rulesPath);

        $installed = 0;
        $skipped = 0;

        foreach ($this->ruleFiles as $file) {
            $source = $stubsPath.DIRECTORY_SEPARATOR.$file;
            $destination = $rulesPath.DIRECTORY_SEPARATOR.$file;

            if (! File::exists($source)) {
                $this->warn("Stub missing: {$file}");

                continue;
            }

            if (File::exists($destination) && ! $this->option('force')) {
                $this->line("  Skipped (exists): .cursor/rules/{$file}");
                $skipped++;

                continue;
            }

            File::copy($source, $destination);
            $this->info("  Installed: .cursor/rules/{$file}");
            $installed++;
        }

        if ($this->option('with-cursorrules')) {
            $this->installCursorRulesFile($stubsPath);
        }

        if ($this->option('with-hooks')) {
            $this->installHooks();
        }

        $this->newLine();
        $this->components->info("Cursor rules: {$installed} installed, {$skipped} skipped.");
        $this->line('Next steps:');
        $this->line('  1. Connect Lessons Learned MCP in Cursor (see mcp-pusher README).');
        $this->line('  2. Optional Project Details MCP: /mcp/project-details?project=<source>');
        $this->line('  3. Add /docs/.mcp-session/ to .gitignore if not already.');
        $this->line('  4. End of session: review drafts, then php artisan mcp:push --source=<project>');

        if (! $this->option('with-hooks')) {
            $this->line('  Tip: re-run with --with-hooks to install preCompact capture automation.');
        }

        return Command::SUCCESS;
    }

    protected function installCursorRulesFile(string $stubsPath): void
    {
        $source = $stubsPath.DIRECTORY_SEPARATOR.'cursorrules.example';
        $destination = base_path('.cursorrules');

        if (! File::exists($source)) {
            $this->warn('  Stub missing: cursorrules.example');

            return;
        }

        if (File::exists($destination) && ! $this->option('force')) {
            $this->line('  Skipped (exists): .cursorrules');

            return;
        }

        File::copy($source, $destination);
        $this->info('  Installed: .cursorrules');
    }

    protected function installHooks(): void
    {
        $hooksStubsPath = $this->stubsPath('cursor-hooks');
        $hooksPath = base_path('.cursor/hooks');
        $promptDestination = base_path('.cursor/hooks/knowledge-capture-prompt.txt');

        File::ensureDirectoryExists($hooksPath);

        $hookFiles = [
            'hooks.json.example' => base_path('.cursor/hooks.json'),
            'pre-compact-checkpoint.sh' => $hooksPath.DIRECTORY_SEPARATOR.'pre-compact-checkpoint.sh',
        ];

        foreach ($hookFiles as $stub => $destination) {
            $source = $hooksStubsPath.DIRECTORY_SEPARATOR.$stub;

            if (! File::exists($source)) {
                $this->warn("  Hook stub missing: {$stub}");

                continue;
            }

            if (File::exists($destination) && ! $this->option('force')) {
                $this->line('  Skipped (exists): '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $destination));

                continue;
            }

            File::copy($source, $destination);

            if (str_ends_with($destination, '.sh')) {
                chmod($destination, 0755);
            }

            $this->info('  Installed: '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $destination));
        }

        $promptSource = $this->stubsPath('').'knowledge-capture-prompt.txt';

        if (File::exists($promptSource)) {
            if (! File::exists($promptDestination) || $this->option('force')) {
                File::copy($promptSource, $promptDestination);
                $this->info('  Installed: .cursor/hooks/knowledge-capture-prompt.txt');
            } else {
                $this->line('  Skipped (exists): .cursor/hooks/knowledge-capture-prompt.txt');
            }
        }
    }

    protected function stubsPath(string $subdirectory = ''): string
    {
        $base = dirname(__DIR__, 2).'/stubs';

        if ($subdirectory === '') {
            return $base.DIRECTORY_SEPARATOR;
        }

        return $base.DIRECTORY_SEPARATOR.$subdirectory;
    }
}

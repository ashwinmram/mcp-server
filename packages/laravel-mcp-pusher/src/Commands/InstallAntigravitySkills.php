<?php

namespace LaravelMcpPusher\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use LaravelMcpPusher\Support\AgentInstructionInstaller;

class InstallAntigravitySkills extends Command
{
    protected $signature = 'mcp:install-antigravity-skills
                            {--force : Overwrite existing skill files}
                            {--global : Install to ~/.gemini/antigravity/global_skills/ instead of .agent/skills/}';

    protected $description = 'Install Google Antigravity skills for MCP session startup and knowledge capture';

    /**
     * @var array<int, string>
     */
    protected array $skillDirectories = [
        'mcp-session-startup',
        'mcp-session-capture',
    ];

    public function handle(AgentInstructionInstaller $installer): int
    {
        $stubsPath = $installer->stubsPath('antigravity-skills');
        $targetBase = $this->targetBasePath();

        if (! is_dir($stubsPath)) {
            $this->error("Antigravity skill stubs not found at: {$stubsPath}");

            return Command::FAILURE;
        }

        $installed = 0;
        $skipped = 0;

        foreach ($this->skillDirectories as $skill) {
            $source = $stubsPath.DIRECTORY_SEPARATOR.$skill.DIRECTORY_SEPARATOR.'SKILL.md';
            $destination = $targetBase.DIRECTORY_SEPARATOR.$skill.DIRECTORY_SEPARATOR.'SKILL.md';

            $result = $installer->copyFile($source, $destination, (bool) $this->option('force'));

            if ($result['missing'] ?? false) {
                $this->warn("  Stub missing: {$skill}/SKILL.md");

                continue;
            }

            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', dirname($destination));

            if ($result['installed']) {
                $this->info("  Installed: {$relative}/SKILL.md");
                $installed++;
            } else {
                $this->line("  Skipped (exists): {$relative}/SKILL.md");
                $skipped++;
            }
        }

        $this->newLine();
        $this->components->info("Antigravity skills: {$installed} installed, {$skipped} skipped.");
        $this->printNextSteps($targetBase);

        return Command::SUCCESS;
    }

    protected function targetBasePath(): string
    {
        if ($this->option('global')) {
            $home = $this->homeDirectory();

            return $home.DIRECTORY_SEPARATOR.'.gemini'.DIRECTORY_SEPARATOR.'antigravity'.DIRECTORY_SEPARATOR.'global_skills';
        }

        return base_path('.agent'.DIRECTORY_SEPARATOR.'skills');
    }

    protected function homeDirectory(): string
    {
        $home = getenv('HOME') ?: getenv('USERPROFILE');

        if ($home === false || $home === '') {
            throw new \RuntimeException('Unable to determine home directory for global Antigravity skills install.');
        }

        return $home;
    }

    protected function printNextSteps(string $targetBase): void
    {
        $scope = $this->option('global') ? 'global (all projects)' : 'workspace (this project)';

        $this->line("Install scope: {$scope}");
        $this->line('Next steps:');
        $this->line('  1. Connect Lessons Learned MCP in Antigravity (see mcp-pusher README).');
        $this->line('  2. Optional Project Details MCP: /mcp/project-details?project=<source>');
        $this->line('  3. Add /docs/.mcp-session/ to .gitignore if not already.');
        $this->line('  4. End of session: paste capture prompt from README, review drafts, then mcp:push');

        if ($this->option('global')) {
            $this->line('  Note: some Antigravity builds expect global_skills/ (not skills/) under ~/.gemini/antigravity/.');
        } else {
            $this->line('  Tip: commit .agent/skills/ to share MCP workflow with your team.');
        }

        if (! File::isDirectory($targetBase)) {
            return;
        }

        $this->line("  Skills path: {$targetBase}");
    }
}

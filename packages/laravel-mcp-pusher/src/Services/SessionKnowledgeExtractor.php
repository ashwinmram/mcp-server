<?php

namespace LaravelMcpPusher\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LaravelMcpPusher\Support\KnowledgeScope;

class SessionKnowledgeExtractor
{
    public function __construct(
        protected JsonlDraftService $draftService,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function validateGitRef(string $sinceGit): void
    {
        $workTree = Process::run(['git', 'rev-parse', '--is-inside-work-tree']);

        if (! $workTree->successful() || trim($workTree->output()) !== 'true') {
            throw new InvalidArgumentException('Not a git repository. Run extract-session from a project with git initialized.');
        }

        $ref = Process::run(['git', 'rev-parse', '--verify', "{$sinceGit}^{commit}"]);

        if (! $ref->successful()) {
            throw new InvalidArgumentException(
                "Invalid git ref \"{$sinceGit}\". For the first commit use --since-git=main or an explicit SHA/tag. For history on main use HEAD~N (e.g. HEAD~50)."
            );
        }

        $log = Process::run([
            'git', 'log', "{$sinceGit}..HEAD", '--oneline', '--no-decorate',
        ]);

        if (! $log->successful()) {
            throw new InvalidArgumentException(
                "Could not read git log for {$sinceGit}..HEAD: ".trim($log->errorOutput())
            );
        }

        if (trim($log->output()) === '') {
            throw new InvalidArgumentException(
                "No commits in range {$sinceGit}..HEAD. Commit your work first (staging alone is not enough), or use a deeper ref such as HEAD~7. For --since-git=main you need commits on this branch that are not on main."
            );
        }
    }

    /**
     * @return array{generic: int, project: int}
     */
    public function extract(string $sinceGit): array
    {
        $this->validateGitRef($sinceGit);

        $counts = ['generic' => 0, 'project' => 0];

        foreach ($this->candidatesFromGit($sinceGit) as $candidate) {
            $this->appendCandidate($candidate);
            $counts[KnowledgeScope::fromPayload($candidate)->value]++;
        }

        return $counts;
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    protected function appendCandidate(array $candidate): void
    {
        $scope = KnowledgeScope::fromPayload($candidate);
        $this->draftService->append($candidate, $scope);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function candidatesFromGit(string $sinceGit): array
    {
        $result = Process::run([
            'git', 'log', "{$sinceGit}..HEAD", '--oneline', '--no-decorate',
        ]);

        if (! $result->successful()) {
            return [];
        }

        $candidates = [];

        foreach (preg_split('/\r\n|\r|\n/', trim($result->output())) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $candidates[] = $this->buildCandidate(
                title: 'Git commit: '.Str::limit($line, 80),
                summary: 'Captured from git log during mcp:extract-session fallback.',
                content: $line,
                scope: KnowledgeScope::Generic,
                sinceGit: $sinceGit,
            );
        }

        $diff = Process::run(['git', 'diff', "{$sinceGit}..HEAD", '--stat']);

        if ($diff->successful() && trim($diff->output()) !== '') {
            $candidates[] = $this->buildCandidate(
                title: 'Git diff stat since '.$sinceGit,
                summary: 'File change summary from git diff --stat.',
                content: trim($diff->output()),
                scope: KnowledgeScope::Generic,
                sinceGit: $sinceGit,
            );
        }

        return $candidates;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildCandidate(string $title, string $summary, string $content, KnowledgeScope $scope, string $sinceGit): array
    {
        $type = $scope === KnowledgeScope::Project ? 'project_detail' : 'ai_output';
        $category = $scope === KnowledgeScope::Project ? 'project-implementation' : 'guidelines';

        return [
            'knowledge_scope' => $scope->value,
            'title' => $title,
            'summary' => $summary,
            'category' => $category,
            'subcategory' => $scope === KnowledgeScope::Project ? 'implementation' : 'session-extract',
            'type' => $type,
            'tags' => $scope === KnowledgeScope::Project ? ['project-details', 'extract-session'] : ['lessons-learned', 'extract-session'],
            'content' => $content,
            'metadata' => [
                'source' => 'git',
                'since_git' => $sinceGit,
                'session_date' => now()->toDateString(),
                'captured_at' => now()->toIso8601String(),
            ],
        ];
    }
}

<?php

namespace LaravelMcpPusher\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use LaravelMcpPusher\Support\KnowledgeScope;

class SessionKnowledgeExtractor
{
    public function __construct(
        protected JsonlDraftService $draftService,
    ) {}

    /**
     * @return array{generic: int, project: int}
     */
    public function extract(?string $transcriptPath = null, ?string $sinceGit = null): array
    {
        $counts = ['generic' => 0, 'project' => 0];

        foreach ($this->candidatesFromGit($sinceGit) as $candidate) {
            $this->appendCandidate($candidate);
            $counts[KnowledgeScope::fromPayload($candidate)->value]++;
        }

        foreach ($this->candidatesFromTranscript($transcriptPath) as $candidate) {
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
    protected function candidatesFromGit(?string $sinceGit): array
    {
        if ($sinceGit === null || $sinceGit === '') {
            return [];
        }

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
            );
        }

        $diff = Process::run(['git', 'diff', "{$sinceGit}..HEAD", '--stat']);

        if ($diff->successful() && trim($diff->output()) !== '') {
            $candidates[] = $this->buildCandidate(
                title: 'Git diff stat since '.$sinceGit,
                summary: 'File change summary from git diff --stat.',
                content: trim($diff->output()),
                scope: KnowledgeScope::Generic,
            );
        }

        return $candidates;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function candidatesFromTranscript(?string $transcriptPath): array
    {
        $path = $transcriptPath ?? $this->resolveLatestTranscriptPath();

        if ($path === null || ! File::exists($path)) {
            return [];
        }

        $candidates = [];
        $lines = preg_split('/\r\n|\r|\n/', File::get($path)) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (! is_array($decoded)) {
                continue;
            }

            $text = $this->extractTextFromTranscriptLine($decoded);

            if ($text === null || strlen($text) < 40) {
                continue;
            }

            if (! $this->looksLikeLearning($text)) {
                continue;
            }

            $scope = $this->inferScopeFromText($text);

            $candidates[] = $this->buildCandidate(
                title: 'Transcript note: '.Str::limit($text, 60),
                summary: 'Heuristic extract from agent transcript (review before push).',
                content: $text,
                scope: $scope,
            );
        }

        return array_slice($candidates, 0, 20);
    }

    protected function resolveLatestTranscriptPath(): ?string
    {
        $home = $_SERVER['HOME'] ?? getenv('HOME') ?: '';
        $slug = Str::slug(basename(base_path()));
        $pattern = $home !== ''
            ? rtrim($home, '/')."/.cursor/projects/*{$slug}*/agent-transcripts/*.jsonl"
            : '';

        $files = $pattern !== '' ? (File::glob($pattern) ?: []) : [];

        if ($files === [] && $home !== '') {
            $files = File::glob(rtrim($home, '/').'/.cursor/projects/*/agent-transcripts/*.jsonl') ?: [];
        }

        if ($files === []) {
            return null;
        }

        usort($files, fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        return $files[0];
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    protected function extractTextFromTranscriptLine(array $decoded): ?string
    {
        if (isset($decoded['message']) && is_array($decoded['message'])) {
            $content = $decoded['message']['content'] ?? null;

            if (is_string($content)) {
                return $content;
            }

            if (is_array($content)) {
                foreach ($content as $part) {
                    if (is_array($part) && ($part['type'] ?? '') === 'text' && is_string($part['text'] ?? null)) {
                        return $part['text'];
                    }
                }
            }
        }

        foreach (['text', 'content', 'output'] as $key) {
            if (isset($decoded[$key]) && is_string($decoded[$key])) {
                return $decoded[$key];
            }
        }

        return null;
    }

    protected function looksLikeLearning(string $text): bool
    {
        $needles = ['lesson', 'learned', 'fix', 'pattern', 'convention', 'mcp:', 'error', 'failed', 'resolved'];

        $lower = strtolower($text);

        foreach ($needles as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function inferScopeFromText(string $text): KnowledgeScope
    {
        $lower = strtolower($text);

        if (str_contains($lower, 'project-details')
            || str_contains($lower, 'project detail')
            || str_contains($lower, 'this repo')
            || str_contains($lower, 'this project')
        ) {
            return KnowledgeScope::Project;
        }

        return KnowledgeScope::Generic;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildCandidate(string $title, string $summary, string $content, KnowledgeScope $scope): array
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
                'source' => 'transcript',
                'session_date' => now()->toDateString(),
            ],
        ];
    }
}

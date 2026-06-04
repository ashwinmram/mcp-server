<?php

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    Config::set('mcp-pusher.generic_draft_jsonl', base_path('docs/.mcp-session/lessons-draft.jsonl'));
    Config::set('mcp-pusher.project_draft_jsonl', base_path('docs/.mcp-session/project-details-draft.jsonl'));

    foreach ([
        base_path('docs/.mcp-session/lessons-draft.jsonl'),
        base_path('docs/.mcp-session/project-details-draft.jsonl'),
    ] as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }
});

/**
 * @return array<int, string>
 */
function extractSessionCommandParts(PendingProcess $process): array
{
    return is_array($process->command) ? $process->command : [];
}

function fakeGitExtractSession(array $responses): void
{
    Process::preventStrayProcesses();

    Process::fake(function (PendingProcess $process) use ($responses) {
        $parts = extractSessionCommandParts($process);

        if ($parts === [] || ($parts[0] ?? '') !== 'git') {
            return Process::result(errorOutput: 'unexpected command', exitCode: 1);
        }

        if (in_array('--is-inside-work-tree', $parts, true)) {
            return Process::result(output: $responses['work_tree'] ?? "true\n");
        }

        if (in_array('--verify', $parts, true)) {
            if ($responses['verify_fail'] ?? false) {
                return Process::result(errorOutput: 'bad ref', exitCode: 1);
            }

            return Process::result(output: $responses['verify_output'] ?? "abc123\n");
        }

        if (in_array('log', $parts, true)) {
            return Process::result(output: $responses['log'] ?? '');
        }

        if (in_array('diff', $parts, true)) {
            return Process::result(output: $responses['diff'] ?? '');
        }

        return Process::result(errorOutput: 'unmatched git command', exitCode: 1);
    });
}

test('extract session warns fallback message', function () {
    fakeGitExtractSession([
        'log' => '',
    ]);

    $this->artisan('mcp:extract-session')
        ->expectsOutputToContain('fallback')
        ->assertExitCode(1);
});

test('extract session fails when no commits in range', function () {
    fakeGitExtractSession([
        'log' => '',
    ]);

    $this->artisan('mcp:extract-session')
        ->expectsOutputToContain('No commits in range')
        ->assertExitCode(1);
});

test('extract session fails when not a git repository', function () {
    fakeGitExtractSession([
        'work_tree' => "false\n",
    ]);

    $this->artisan('mcp:extract-session')
        ->expectsOutputToContain('Not a git repository')
        ->assertExitCode(1);
});

test('extract session appends from git history', function () {
    fakeGitExtractSession([
        'log' => "def456 Fix Pest tests\n",
        'diff' => " tests/ExampleTest.php | 2 ++\n",
    ]);

    $this->artisan('mcp:extract-session')
        ->expectsOutputToContain('Appended')
        ->assertExitCode(0);

    expect(File::exists(base_path('docs/.mcp-session/lessons-draft.jsonl')))->toBeTrue();

    $lines = array_values(array_filter(
        File::lines(base_path('docs/.mcp-session/lessons-draft.jsonl'))->all(),
        fn (string $line): bool => trim($line) !== '',
    ));
    expect($lines)->toHaveCount(2);

    $first = json_decode($lines[0], true);
    expect($first['metadata']['source'])->toBe('git')
        ->and($first['metadata']['since_git'])->toBe('HEAD~1');
});

test('extract session uses custom since-git ref', function () {
    fakeGitExtractSession([
        'log' => "111 aaa\n222 bbb\n",
        'diff' => '',
    ]);

    $this->artisan('mcp:extract-session', ['--since-git' => 'HEAD~7'])
        ->expectsOutputToContain('Appended')
        ->assertExitCode(0);

    $lines = array_values(array_filter(
        File::lines(base_path('docs/.mcp-session/lessons-draft.jsonl'))->all(),
        fn (string $line): bool => trim($line) !== '',
    ));
    expect($lines)->toHaveCount(2);
});

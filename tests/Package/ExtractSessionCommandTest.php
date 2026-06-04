<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

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

test('extract session warns and exits successfully when no candidates found', function () {
    $this->artisan('mcp:extract-session')
        ->expectsOutputToContain('fallback')
        ->expectsOutputToContain('No candidates extracted')
        ->assertExitCode(0);
});

test('extract session appends from transcript file', function () {
    $transcript = base_path('storage/framework/testing/transcript.jsonl');
    File::ensureDirectoryExists(dirname($transcript));

    $line = json_encode([
        'message' => [
            'content' => 'We learned a lesson about fixing Pest tests when RefreshDatabase fails.',
        ],
    ]);
    File::put($transcript, $line.PHP_EOL);

    $this->artisan('mcp:extract-session', ['--transcript' => $transcript])
        ->expectsOutputToContain('Appended')
        ->assertExitCode(0);

    expect(File::exists(base_path('docs/.mcp-session/lessons-draft.jsonl')))->toBeTrue();
});

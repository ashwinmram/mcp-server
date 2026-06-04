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

test('appends generic entry to lessons draft jsonl', function () {
    $payload = json_encode([
        'knowledge_scope' => 'generic',
        'title' => 'Test lesson',
        'summary' => 'Summary for agents',
        'category' => 'testing',
        'subcategory' => 'append',
        'type' => 'ai_output',
        'tags' => ['pest'],
        'content' => 'Use RefreshDatabase in feature tests.',
    ]);

    $this->artisan('mcp:append', ['payload' => $payload])
        ->expectsOutputToContain('Appended generic knowledge entry')
        ->assertExitCode(0);

    $path = base_path('docs/.mcp-session/lessons-draft.jsonl');
    expect(File::exists($path))->toBeTrue();
    expect(File::get($path))->toContain('Test lesson');
});

test('appends project entry when type is project_detail', function () {
    $payload = json_encode([
        'title' => 'Auth path',
        'summary' => 'Where auth lives',
        'category' => 'project-implementation',
        'subcategory' => 'auth',
        'type' => 'project_detail',
        'tags' => ['auth'],
        'content' => 'Controllers live under app/Http/Controllers.',
    ]);

    $this->artisan('mcp:append', ['payload' => $payload])
        ->expectsOutputToContain('Appended project knowledge entry')
        ->assertExitCode(0);

    expect(File::get(base_path('docs/.mcp-session/project-details-draft.jsonl')))
        ->toContain('Auth path');
});

test('fails when required fields are missing', function () {
    $this->artisan('mcp:append', ['payload' => '{"title":"Only title"}'])
        ->assertExitCode(1);
});

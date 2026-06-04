<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('mcp-pusher.server_url', 'https://mcp-server.test');
    Config::set('mcp-pusher.api_token', 'test-api-token');

    $paths = [
        base_path('docs/lessons-learned.md'),
        base_path('docs/lessons_learned.json'),
        base_path('docs/project-details.md'),
        base_path('docs/project_details.json'),
        base_path('docs/.mcp-session/lessons-draft.jsonl'),
        base_path('docs/.mcp-session/project-details-draft.jsonl'),
    ];

    foreach ($paths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    if (! File::isDirectory(base_path('docs'))) {
        File::makeDirectory(base_path('docs'), 0755, true);
    }
});

test('fails when no knowledge sources have content', function () {
    $this->artisan('mcp:push', ['--source' => 'test-project'])
        ->expectsOutputToContain('No knowledge found to push')
        ->assertExitCode(1);
});

test('pushes generic lessons from lessons-learned.md', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'data' => ['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []],
        ], 201),
    ]);

    $path = base_path('docs/lessons-learned.md');
    File::put($path, 'Always use type hints in PHP functions.');

    $this->artisan('mcp:push', ['--source' => 'test-project'])
        ->expectsOutputToContain('Pushing knowledge from project: test-project')
        ->expectsOutputToContain('Lessons push summary')
        ->assertExitCode(0);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/api/lessons')
        && $request->data()['source_project'] === 'test-project');

    expect(File::get($path))->toBe('');
});

test('pushes project details from markdown file', function () {
    Http::fake([
        'https://mcp-server.test/api/project-details' => Http::response([
            'success' => true,
            'data' => ['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []],
        ], 201),
    ]);

    $path = base_path('docs/project-details.md');
    File::put($path, '## Auth\nAuth lives in app/Http/Controllers/Auth/.');

    $this->artisan('mcp:push', ['--source' => 'my-app'])
        ->expectsOutputToContain('Project details push summary')
        ->assertExitCode(0);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/api/project-details')
        && $request->data()['source_project'] === 'my-app');

    expect(File::get($path))->toBe('');
});

test('pushes both generic and project buckets in one command', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'data' => ['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []],
        ], 201),
        'https://mcp-server.test/api/project-details' => Http::response([
            'success' => true,
            'data' => ['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []],
        ], 201),
    ]);

    File::put(base_path('docs/lessons-learned.md'), 'Generic lesson content.');
    File::put(base_path('docs/project-details.md'), 'Project specific path info.');

    $this->artisan('mcp:push', ['--source' => 'dual-bucket'])
        ->assertExitCode(0);

    Http::assertSentCount(2);
});

test('fails fast when lessons push fails and does not call project details', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response(['message' => 'Server error'], 500),
    ]);

    File::put(base_path('docs/lessons-learned.md'), 'Generic content.');
    File::put(base_path('docs/project-details.md'), 'Project content.');

    $this->artisan('mcp:push', ['--source' => 'test-project'])
        ->assertExitCode(1);

    Http::assertSentCount(1);
    expect(File::get(base_path('docs/project-details.md')))->toContain('Project content');
});

test('does not truncate when --no-truncate is used', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'data' => ['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []],
        ], 201),
    ]);

    $content = 'Keep this content after push.';
    File::put(base_path('docs/lessons-learned.md'), $content);

    $this->artisan('mcp:push', ['--source' => 'test-project', '--no-truncate' => true])
        ->assertExitCode(0);

    expect(File::get(base_path('docs/lessons-learned.md')))->toBe($content);
});

test('merges generic draft jsonl into push payload', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'data' => ['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []],
        ], 201),
    ]);

    $draftDir = base_path('docs/.mcp-session');
    File::ensureDirectoryExists($draftDir);

    $entry = json_encode([
        'knowledge_scope' => 'generic',
        'title' => 'Draft lesson',
        'summary' => 'From jsonl draft',
        'category' => 'testing',
        'subcategory' => 'draft',
        'type' => 'ai_output',
        'tags' => ['test'],
        'content' => 'Draft body',
    ]);
    File::put(base_path('docs/.mcp-session/lessons-draft.jsonl'), $entry.PHP_EOL);

    $this->artisan('mcp:push', ['--source' => 'test-project'])
        ->assertExitCode(0);

    Http::assertSent(function (Request $request) {
        $lessons = $request->data()['lessons'] ?? [];

        return collect($lessons)->contains(fn ($lesson) => ($lesson['content'] ?? '') === 'Draft body');
    });
});

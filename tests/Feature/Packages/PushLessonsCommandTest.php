<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('services.mcp.server_url', 'https://mcp-server.test');
    Config::set('services.mcp.api_token', 'test-api-token');
    Config::set('mcp-pusher.server_url', 'https://mcp-server.test');
    Config::set('mcp-pusher.api_token', 'test-api-token');

    $lessonsLearnedPath = base_path('docs/lessons-learned.md');
    if (File::exists($lessonsLearnedPath)) {
        File::delete($lessonsLearnedPath);
    }

    $lessonsJsonPath = base_path('docs/lessons_learned.json');
    if (File::exists($lessonsJsonPath)) {
        File::delete($lessonsJsonPath);
    }

    $docsDir = base_path('docs');
    if (! File::isDirectory($docsDir)) {
        File::makeDirectory($docsDir, 0755, true);
    }
});

test('pushes lessons from lessons-learned.md file', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'message' => 'Lessons processed successfully',
            'data' => [
                'created' => 1,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [],
            ],
        ], 201),
    ]);

    $content = 'Always use type hints in PHP functions.';
    $path = base_path('docs/lessons-learned.md');
    File::put($path, $content);

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
    ])
        ->expectsOutput('Converting and pushing lessons from project: test-project')
        ->expectsOutputToContain('Reading lessons-learned.md file')
        ->expectsOutputToContain('Pushing 1 lesson(s) to MCP server...')
        ->expectsOutput('Push Summary:')
        ->expectsOutputToContain('Created:')
        ->expectsOutputToContain('✓ Conversion and push completed successfully!')
        ->assertExitCode(0);

    Http::assertSent(function (Request $request) {
        $url = $request->url();
        $data = $request->data();

        return str_contains($url, 'https://mcp-server.test/api/lessons')
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer test-api-token')
            && isset($data['source_project'])
            && $data['source_project'] === 'test-project'
            && isset($data['lessons'])
            && is_array($data['lessons'])
            && count($data['lessons']) === 1
            && $data['lessons'][0]['type'] === 'markdown';
    });

    expect(File::exists($path))->toBeTrue();
    expect(File::get($path))->toBeEmpty();

    if (File::exists($path)) {
        File::delete($path);
    }
});

test('pushes lessons from lessons_learned.json file', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'message' => 'Lessons processed successfully',
            'data' => [
                'created' => 2,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [],
            ],
        ], 201),
    ]);

    $docsDir = base_path('docs');
    $jsonContent = json_encode([
        ['content' => 'Always validate user input', 'category' => 'validation', 'tags' => ['validation']],
        ['content' => 'Use dependency injection', 'category' => 'architecture', 'tags' => ['di']],
    ]);
    File::put($docsDir.'/lessons_learned.json', $jsonContent);
    $lessonsJsonPath = $docsDir.'/lessons_learned.json';

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
        '--lessons-json' => $lessonsJsonPath,
    ])
        ->expectsOutput('Converting and pushing lessons from project: test-project')
        ->expectsOutputToContain('Reading lessons_learned.json file')
        ->expectsOutputToContain('lesson(s) to MCP server...')
        ->expectsOutput('Push Summary:')
        ->expectsOutputToContain('Created:')
        ->expectsOutputToContain('✓ Conversion and push completed successfully!')
        ->assertExitCode(0);

    Http::assertSent(function (Request $request) {
        $data = $request->data();

        return isset($data['lessons'])
            && is_array($data['lessons'])
            && count($data['lessons']) === 2
            && $data['lessons'][0]['type'] === 'ai_output'
            && $data['lessons'][1]['type'] === 'ai_output';
    });

    expect(File::exists($lessonsJsonPath))->toBeTrue();
    expect(trim(File::get($lessonsJsonPath)))->toBe('[]');

    if (File::exists($lessonsJsonPath)) {
        File::delete($lessonsJsonPath);
    }
});

test('handles missing lessons-learned.md file gracefully', function () {
    $path = base_path('docs/lessons-learned.md');
    if (File::exists($path)) {
        File::delete($path);
    }

    $docsDir = base_path('docs');
    File::put($docsDir.'/lessons_learned.json', json_encode([['content' => 'test', 'category' => 'test']]));

    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'data' => ['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []],
        ], 201),
    ]);

    $lessonsJsonPath = $docsDir.'/lessons_learned.json';

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
    ])
        ->expectsOutputToContain('⚠ lessons-learned.md file not found')
        ->assertExitCode(0);

    expect(File::exists($lessonsJsonPath))->toBeTrue();
    expect(trim(File::get($lessonsJsonPath)))->toBe('[]');

    if (File::exists($lessonsJsonPath)) {
        File::delete($lessonsJsonPath);
    }
});

test('handles invalid json files gracefully', function () {
    $docsDir = base_path('docs');
    File::put($docsDir.'/lessons_learned.json', 'invalid json content {');

    $path = base_path('docs/lessons-learned.md');
    File::put($path, 'Test content');

    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'data' => ['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []],
        ], 201),
    ]);

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
        '--lessons-json' => base_path('docs/lessons_learned.json'),
    ])
        ->expectsOutputToContain('✗ Invalid JSON:')
        ->assertExitCode(0);

    expect(File::exists($path))->toBeTrue();
    expect(File::get($path))->toBeEmpty();

    if (File::exists($path)) {
        File::delete($path);
    }
    if (File::exists($docsDir.'/lessons_learned.json')) {
        File::delete($docsDir.'/lessons_learned.json');
    }
});

test('returns failure when no lessons found', function () {
    $path = base_path('docs/lessons-learned.md');
    if (File::exists($path)) {
        File::delete($path);
    }

    $lessonsJsonPath = base_path('docs/lessons_learned.json');
    if (File::exists($lessonsJsonPath)) {
        File::delete($lessonsJsonPath);
    }

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
    ])
        ->expectsOutput('No lessons found to convert and push.')
        ->assertExitCode(1);
});

test('handles api errors gracefully', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'message' => 'Authentication failed',
        ], 401),
    ]);

    $path = base_path('docs/lessons-learned.md');
    File::put($path, 'Test content');

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
    ])
        ->expectsOutputToContain('Failed')
        ->expectsOutputToContain('401')
        ->assertExitCode(1);

    expect(File::exists($path))->toBeTrue();
    expect(File::get($path))->toBe('Test content');

    if (File::exists($path)) {
        File::delete($path);
    }
});

test('uses default source project when not specified', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'data' => ['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []],
        ], 201),
    ]);

    $path = base_path('docs/lessons-learned.md');
    File::put($path, 'Test content');

    $this->artisan('mcp:push-lessons')
        ->assertExitCode(0);

    Http::assertSent(function (Request $request) {
        return isset($request->data()['source_project'])
            && $request->data()['source_project'] === basename(base_path());
    });

    if (File::exists($path)) {
        File::delete($path);
    }
});

test('displays push summary with errors', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'message' => 'Lessons processed successfully',
            'data' => [
                'created' => 1,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [
                    'Some lessons failed validation',
                ],
            ],
        ], 201),
    ]);

    $path = base_path('docs/lessons-learned.md');
    File::put($path, 'Test content');

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
    ])
        ->expectsOutput('Push Summary:')
        ->expectsOutputToContain('Created:')
        ->expectsOutputToContain('Warnings:')
        ->expectsOutputToContain('Some lessons failed validation')
        ->expectsOutputToContain('✓ Conversion and push completed successfully!')
        ->assertExitCode(0);

    expect(File::exists($path))->toBeTrue();
    expect(File::get($path))->toBeEmpty();

    if (File::exists($path)) {
        File::delete($path);
    }
});

test('handles empty lessons-learned.md file', function () {
    $path = base_path('docs/lessons-learned.md');
    File::put($path, '');

    $docsDir = base_path('docs');
    File::put($docsDir.'/lessons_learned.json', json_encode([['content' => 'test', 'category' => 'test']]));

    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'data' => ['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []],
        ], 201),
    ]);

    $lessonsJsonPath = $docsDir.'/lessons_learned.json';

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
    ])
        ->assertExitCode(0);

    Http::assertSent(function (Request $request) {
        $lessons = $request->data()['lessons'] ?? [];
        $markdownLessons = array_filter($lessons, fn ($lesson) => $lesson['type'] === 'markdown');

        return count($markdownLessons) === 0;
    });

    expect(File::exists($lessonsJsonPath))->toBeTrue();
    expect(trim(File::get($lessonsJsonPath)))->toBe('[]');

    if (File::exists($path)) {
        File::delete($path);
    }
    if (File::exists($lessonsJsonPath)) {
        File::delete($lessonsJsonPath);
    }
});

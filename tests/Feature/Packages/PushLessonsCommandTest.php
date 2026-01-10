<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use LaravelMcpPusher\Services\LessonPusherService;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up test configuration
    Config::set('services.mcp.server_url', 'https://mcp-server.test');
    Config::set('services.mcp.api_token', 'test-api-token');
    Config::set('mcp-pusher.server_url', 'https://mcp-server.test');
    Config::set('mcp-pusher.api_token', 'test-api-token');
});

test('pushes lessons from cursorrules file', function () {
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

    // Create a temporary .cursorrules file
    $content = 'Always use type hints in PHP functions.';
    $path = base_path('.cursorrules');
    File::put($path, $content);

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
    ])
        ->expectsOutput('Pushing lessons from project: test-project')
        ->expectsOutputToContain('Reading .cursorrules file...')
        ->expectsOutputToContain('Pushing 1 lesson(s) to MCP server...')
        ->expectsOutput('Push Summary:')
        ->expectsOutputToContain('Created:')
        ->expectsOutputToContain('✓ Push completed successfully!')
        ->assertExitCode(0);

    // Verify HTTP request was made correctly
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
            && $data['lessons'][0]['type'] === 'cursor';
    });

    // Cleanup
    if (File::exists($path)) {
        File::delete($path);
    }
});

test('pushes lessons from ai json files', function () {
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

    // Create docs directory and AI JSON file
    $docsDir = base_path('docs');
    if (! File::isDirectory($docsDir)) {
        File::makeDirectory($docsDir, 0755, true);
    }

    $jsonContent = json_encode([
        'lesson1' => 'Always validate user input',
        'lesson2' => 'Use dependency injection',
    ]);
    File::put($docsDir.'/AI_lessons.json', $jsonContent);

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
        '--ai-json-dir' => 'docs',
    ])
        ->expectsOutput('Pushing lessons from project: test-project')
        ->expectsOutputToContain('Searching for AI_*.json files')
        ->expectsOutputToContain('lesson(s) to MCP server...')
        ->expectsOutput('Push Summary:')
        ->expectsOutputToContain('Created:')
        ->expectsOutput('✓ Push completed successfully!')
        ->assertExitCode(0);

    // Verify HTTP request
    Http::assertSent(function (Request $request) {
        $data = $request->data();
        return isset($data['lessons'])
            && is_array($data['lessons'])
            && count($data['lessons']) === 2
            && $data['lessons'][0]['type'] === 'ai_output'
            && $data['lessons'][1]['type'] === 'ai_output';
    });

    // Cleanup
    if (File::exists($docsDir.'/AI_lessons.json')) {
        File::delete($docsDir.'/AI_lessons.json');
    }
});

test('handles missing cursorrules file gracefully', function () {
    $path = base_path('.cursorrules');
    if (File::exists($path)) {
        File::delete($path);
    }

    // Create at least one AI JSON file so command doesn't fail completely
    $docsDir = base_path('docs');
    if (! File::isDirectory($docsDir)) {
        File::makeDirectory($docsDir, 0755, true);
    }
    File::put($docsDir.'/AI_test.json', json_encode(['test' => 'content']));

    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'data' => ['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []],
        ], 201),
    ]);

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
    ])
        ->expectsOutputToContain('⚠ .cursorrules file not found')
        ->assertExitCode(0);

    // Cleanup
    if (File::exists($docsDir.'/AI_test.json')) {
        File::delete($docsDir.'/AI_test.json');
    }
});

test('handles invalid json files gracefully', function () {
    $docsDir = base_path('docs');
    if (! File::isDirectory($docsDir)) {
        File::makeDirectory($docsDir, 0755, true);
    }

    File::put($docsDir.'/AI_invalid.json', 'invalid json content {');

    // Create a valid .cursorrules file so command doesn't fail completely
    $path = base_path('.cursorrules');
    File::put($path, 'Test content');

    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'data' => ['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []],
        ], 201),
    ]);

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
        '--ai-json-dir' => 'docs',
    ])
        ->expectsOutputToContain('✗ Invalid JSON:')
        ->assertExitCode(0);

    // Cleanup
    if (File::exists($docsDir.'/AI_invalid.json')) {
        File::delete($docsDir.'/AI_invalid.json');
    }
    if (File::exists($path)) {
        File::delete($path);
    }
});

test('returns failure when no lessons found', function () {
    $path = base_path('.cursorrules');
    if (File::exists($path)) {
        File::delete($path);
    }

    // Ensure no AI JSON files exist
    $docsDir = base_path('docs');
    if (File::isDirectory($docsDir)) {
        $files = File::glob($docsDir.'/AI_*.json');
        foreach ($files as $file) {
            File::delete($file);
        }
    }

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
    ])
        ->expectsOutput('No lessons found to push.')
        ->assertExitCode(1);
});

test('handles api errors gracefully', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'message' => 'Authentication failed',
        ], 401),
    ]);

    $path = base_path('.cursorrules');
    File::put($path, 'Test content');

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
    ])
        ->expectsOutputToContain('Failed')
        ->expectsOutputToContain('401')
        ->assertExitCode(1);

    // Cleanup
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

    $path = base_path('.cursorrules');
    File::put($path, 'Test content');

    $this->artisan('mcp:push-lessons')
        ->assertExitCode(0);

    // Verify the source_project is the directory name
    Http::assertSent(function (Request $request) {
        return isset($request->data()['source_project'])
            && $request->data()['source_project'] === basename(base_path());
    });

    // Cleanup
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

    $path = base_path('.cursorrules');
    File::put($path, 'Test content');

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
    ])
        ->expectsOutput('Push Summary:')
        ->expectsOutputToContain('Created:')
        ->expectsOutputToContain('Warnings:')
        ->expectsOutputToContain('Some lessons failed validation')
        ->assertExitCode(0);

    // Cleanup
    if (File::exists($path)) {
        File::delete($path);
    }
});

test('handles empty cursorrules file', function () {
    $path = base_path('.cursorrules');
    File::put($path, '');

    // Create at least one AI JSON file so command doesn't fail completely
    $docsDir = base_path('docs');
    if (! File::isDirectory($docsDir)) {
        File::makeDirectory($docsDir, 0755, true);
    }
    File::put($docsDir.'/AI_test.json', json_encode(['test' => 'content']));

    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'data' => ['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []],
        ], 201),
    ]);

    $this->artisan('mcp:push-lessons', [
        '--source' => 'test-project',
    ])
        ->assertExitCode(0);

    // Verify .cursorrules content is not included (empty file)
    Http::assertSent(function (Request $request) {
        $lessons = $request->data()['lessons'] ?? [];
        $cursorLessons = array_filter($lessons, fn ($lesson) => $lesson['type'] === 'cursor');
        return count($cursorLessons) === 0; // Empty .cursorrules should not create a lesson
    });

    // Cleanup
    if (File::exists($path)) {
        File::delete($path);
    }
    if (File::exists($docsDir.'/AI_test.json')) {
        File::delete($docsDir.'/AI_test.json');
    }
});

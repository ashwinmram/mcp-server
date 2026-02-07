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

    $mdPath = base_path('docs/project-details.md');
    if (File::exists($mdPath)) {
        File::delete($mdPath);
    }
    $docsDir = base_path('docs');
    if (File::isDirectory($docsDir)) {
        foreach (File::glob($docsDir.'/project_details*.json') as $file) {
            File::delete($file);
        }
    }
});

test('fails when no project details files found', function () {
    $this->artisan('mcp:push-project-details', ['--source' => 'test-project'])
        ->assertExitCode(1);
});

test('pushes project details from markdown file', function () {
    Http::fake([
        'https://mcp-server.test/api/project-details' => Http::response([
            'success' => true,
            'data' => ['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []],
        ], 201),
    ]);

    $path = base_path('docs/project-details.md');
    if (! File::isDirectory(dirname($path))) {
        File::makeDirectory(dirname($path), 0755, true);
    }
    File::put($path, '## Auth\nAuth lives in app/Http/Controllers/Auth/.');

    $this->artisan('mcp:push-project-details', ['--source' => 'my-app'])
        ->expectsOutputToContain('Converting and pushing project details from project: my-app')
        ->expectsOutputToContain('Pushing 1 project detail(s)')
        ->assertExitCode(0);

    Http::assertSent(function (Request $request) {
        $data = $request->data();

        return str_contains($request->url(), '/api/project-details')
            && $data['source_project'] === 'my-app'
            && count($data['lessons']) === 1
            && $data['lessons'][0]['type'] === 'markdown';
    });

    File::delete($path);
});

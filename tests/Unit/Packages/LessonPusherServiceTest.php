<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use LaravelMcpPusher\Services\LessonPusherService;

uses(Tests\TestCase::class);

beforeEach(function () {
    Config::set('services.mcp.server_url', 'https://mcp-server.test');
    Config::set('services.mcp.api_token', 'test-api-token');
    Http::fake();
});

test('pushes lessons to mcp server with correct configuration', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response([
            'success' => true,
            'data' => ['created' => 1],
        ], 201),
    ]);

    $service = new LessonPusherService();
    $lessons = [
        [
            'type' => 'cursor',
            'content' => 'Test lesson content',
        ],
    ];

    $response = $service->pushLessons($lessons, 'test-project');

    expect($response->successful())->toBeTrue()
        ->and($response->json())->toHaveKey('success')
        ->and($response->json()['success'])->toBeTrue();

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://mcp-server.test/api/lessons'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer test-api-token')
            && $request->hasHeader('Accept', 'application/json')
            && isset($request->data()['source_project'])
            && $request->data()['source_project'] === 'test-project'
            && isset($request->data()['lessons'])
            && count($request->data()['lessons']) === 1;
    });
});

test('throws exception when server url is missing', function () {
    Config::set('services.mcp.server_url', '');
    Config::set('services.mcp.api_token', 'test-api-token');

    $service = new LessonPusherService();

    expect(fn () => $service->pushLessons([], 'test-project'))
        ->toThrow(\RuntimeException::class, 'MCP server URL and API token must be configured');
});

test('throws exception when api token is missing', function () {
    Config::set('services.mcp.server_url', 'https://mcp-server.test');
    Config::set('services.mcp.api_token', '');

    $service = new LessonPusherService();

    expect(fn () => $service->pushLessons([], 'test-project'))
        ->toThrow(\RuntimeException::class, 'MCP server URL and API token must be configured');
});

test('handles server url with trailing slash', function () {
    Config::set('services.mcp.server_url', 'https://mcp-server.test/');
    Config::set('services.mcp.api_token', 'test-api-token');

    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response(['success' => true], 201),
    ]);

    $service = new LessonPusherService();
    $service->pushLessons([], 'test-project');

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://mcp-server.test/api/lessons'; // Should not have double slashes
    });
});

test('sends correct payload structure', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response(['success' => true], 201),
    ]);

    $service = new LessonPusherService();
    $lessons = [
        [
            'type' => 'cursor',
            'content' => 'Lesson 1',
            'category' => 'coding',
            'tags' => ['php', 'laravel'],
            'metadata' => ['file' => '.cursorrules'],
        ],
        [
            'type' => 'ai_output',
            'content' => 'Lesson 2',
            'metadata' => ['file' => 'AI_test.json'],
        ],
    ];

    $service->pushLessons($lessons, 'my-project');

    Http::assertSent(function (Request $request) {
        $data = $request->data();
        return isset($data['source_project'])
            && $data['source_project'] === 'my-project'
            && isset($data['lessons'])
            && is_array($data['lessons'])
            && count($data['lessons']) === 2
            && $data['lessons'][0]['type'] === 'cursor'
            && $data['lessons'][1]['type'] === 'ai_output';
    });
});

test('includes authorization header with bearer token', function () {
    Config::set('services.mcp.api_token', 'my-secret-token-123');

    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response(['success' => true], 201),
    ]);

    $service = new LessonPusherService();
    $service->pushLessons([], 'test-project');

    Http::assertSent(function (Request $request) {
        return $request->hasHeader('Authorization', 'Bearer my-secret-token-123');
    });
});

test('includes accept json header', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response(['success' => true], 201),
    ]);

    $service = new LessonPusherService();
    $service->pushLessons([], 'test-project');

    Http::assertSent(function (Request $request) {
        return $request->hasHeader('Accept', 'application/json');
    });
});

test('handles empty lessons array', function () {
    Http::fake([
        'https://mcp-server.test/api/lessons' => Http::response(['success' => true], 201),
    ]);

    $service = new LessonPusherService();
    $response = $service->pushLessons([], 'test-project');

    expect($response->successful())->toBeTrue();

    Http::assertSent(function (Request $request) {
        $data = $request->data();
        return isset($data['lessons']) && is_array($data['lessons']) && count($data['lessons']) === 0;
    });
});

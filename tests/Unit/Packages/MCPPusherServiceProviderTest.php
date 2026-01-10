<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use LaravelMcpPusher\MCPPusherServiceProvider;

uses(RefreshDatabase::class);

test('service provider registers command', function () {
    // The command should be registered via package discovery
    // Check that it exists in the Artisan command list
    $commandExists = array_key_exists('mcp:push-lessons', Artisan::all());
    expect($commandExists)->toBeTrue();
});

test('service provider merges configuration', function () {
    // Configuration should be merged and accessible
    expect(config('mcp-pusher.cursorrules_path'))->toBe(base_path('.cursorrules'))
        ->and(config('mcp-pusher.ai_json_directory'))->toBe(base_path('docs'));
});

test('configuration references services.mcp config', function () {
    Config::set('services.mcp.server_url', 'https://test.example.com');
    Config::set('services.mcp.api_token', 'test-token');

    // Package config should reference services.mcp
    $serverUrl = config('mcp-pusher.server_url');
    $apiToken = config('mcp-pusher.api_token');

    expect($serverUrl)->toBe('https://test.example.com')
        ->and($apiToken)->toBe('test-token');
});

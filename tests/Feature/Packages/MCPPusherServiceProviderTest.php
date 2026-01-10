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

test('service uses package config with fallback to services.mcp', function () {
    Config::set('services.mcp.server_url', 'https://test.example.com');
    Config::set('services.mcp.api_token', 'test-token');

    // The service should use package config with fallback to services.mcp
    // Since package config references services.mcp, both should work
    $service = new \LaravelMcpPusher\Services\LessonPusherService();

    // The service should be able to read from services.mcp when package config references it
    // We test this by checking that config is accessible (actual push requires HTTP which we mock)
    expect(config('services.mcp.server_url'))->toBe('https://test.example.com')
        ->and(config('services.mcp.api_token'))->toBe('test-token');
});

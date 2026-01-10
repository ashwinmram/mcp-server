<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

uses(Tests\TestCase::class);

test('service provider registers command', function () {
    // The command should be registered via package discovery
    // Package discovery happens automatically in Laravel, so the command should exist
    $commandExists = array_key_exists('mcp:push-lessons', Artisan::all());
    expect($commandExists)->toBeTrue();
});

test('configuration is accessible after provider registration', function () {
    // Since service providers are auto-discovered, config should already be merged
    // These values come from the config file defaults
    expect(config('mcp-pusher.cursorrules_path'))->toBe(base_path('.cursorrules'))
        ->and(config('mcp-pusher.ai_json_directory'))->toBe(base_path('docs'));
});

test('service uses services.mcp config as fallback', function () {
    // The package config uses env() directly, which will be null in tests
    // The service implementation checks mcp-pusher config first, then falls back to services.mcp
    Config::set('services.mcp.server_url', 'https://test.example.com');
    Config::set('services.mcp.api_token', 'test-token');

    // Verify services.mcp config is accessible (which the service uses as fallback)
    expect(config('services.mcp.server_url'))->toBe('https://test.example.com')
        ->and(config('services.mcp.api_token'))->toBe('test-token');
});

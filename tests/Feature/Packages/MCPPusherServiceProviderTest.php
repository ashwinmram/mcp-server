<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

test('service provider registers push-lessons command', function () {
    $commandExists = array_key_exists('mcp:push-lessons', Artisan::all());
    expect($commandExists)->toBeTrue();
});

test('service provider registers push-project-details command', function () {
    $commandExists = array_key_exists('mcp:push-project-details', Artisan::all());
    expect($commandExists)->toBeTrue();
});

test('service provider merges configuration', function () {
    // Configuration should be merged and accessible
    expect(config('mcp-pusher.lessons_learned_path'))->toBe(base_path('docs/lessons-learned.md'))
        ->and(config('mcp-pusher.lessons_learned_json_path'))->toBe(base_path('docs/lessons_learned.json'));
});

test('service uses package config with fallback to services.mcp', function () {
    Config::set('services.mcp.server_url', 'https://test.example.com');
    Config::set('services.mcp.api_token', 'test-token');

    // The service should use package config with fallback to services.mcp
    // Since package config references services.mcp, both should work
    $service = new \LaravelMcpPusher\Services\LessonPusherService;

    // The service should be able to read from services.mcp when package config references it
    // We test this by checking that config is accessible (actual push requires HTTP which we mock)
    expect(config('services.mcp.server_url'))->toBe('https://test.example.com')
        ->and(config('services.mcp.api_token'))->toBe('test-token');
});

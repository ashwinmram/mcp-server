<?php

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);

    // Create test lessons
    Lesson::factory()->create([
        'content' => 'Always use type hints in PHP functions',
        'category' => 'coding',
        'tags' => ['php', 'best-practices'],
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'content' => 'Use validation rules in Laravel controllers',
        'category' => 'validation',
        'tags' => ['laravel', 'validation'],
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'content' => 'Different content',
        'category' => 'routing',
        'is_generic' => false, // Non-generic, should be excluded
    ]);
});

test('searches lessons by keyword', function () {
    $tool = new \App\Mcp\Tools\SearchLessons();
    $request = new Request(['query' => 'type hints']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\Response::class)
        ->and($data['count'])->toBeGreaterThan(0)
        ->and($data['results'][0]['content'])->toContain('type hints');
});

test('filters lessons by category', function () {
    $tool = new \App\Mcp\Tools\SearchLessons();
    $request = new Request(['category' => 'validation']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBe(1)
        ->and($data['results'][0]['category'])->toBe('validation');
});

test('filters lessons by tags', function () {
    $tool = new \App\Mcp\Tools\SearchLessons();
    $request = new Request(['tags' => ['php']]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBe(1)
        ->and($data['results'][0]['tags'])->toContain('php');
});

test('respects limit parameter', function () {
    Lesson::factory()->count(5)->create(['is_generic' => true]);

    $tool = new \App\Mcp\Tools\SearchLessons();
    $request = new Request(['limit' => 2]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBeLessThanOrEqual(2);
});

test('only returns generic lessons', function () {
    $tool = new \App\Mcp\Tools\SearchLessons();
    $request = new Request([]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    // Should not include non-generic lesson (only 2 generic lessons)
    expect($data['count'])->toBe(2)
        ->and($data['results'])->each->toHaveKey('content');
});

test('returns empty results when no matches', function () {
    $tool = new \App\Mcp\Tools\SearchLessons();
    $request = new Request(['query' => 'nonexistentkeyword12345']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBe(0)
        ->and($data['results'])->toBeArray();
});

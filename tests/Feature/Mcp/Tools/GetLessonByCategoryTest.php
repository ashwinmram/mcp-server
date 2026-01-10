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
});

test('gets lessons by category', function () {
    Lesson::factory()->count(3)->create([
        'category' => 'validation',
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'category' => 'routing',
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetLessonByCategory();
    $request = new Request(['category' => 'validation']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['category'])->toBe('validation')
        ->and($data['count'])->toBe(3)
        ->and($data['lessons'])->toHaveCount(3);
});

test('returns error when category is missing', function () {
    $tool = new \App\Mcp\Tools\GetLessonByCategory();
    $request = new Request([]);

    $response = $tool->handle($request);
    $content = getResponseText($response);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\Response::class)
        ->and($content)->toContain('Category is required');
});

test('respects limit parameter', function () {
    Lesson::factory()->count(5)->create([
        'category' => 'validation',
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetLessonByCategory();
    $request = new Request(['category' => 'validation', 'limit' => 2]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBeLessThanOrEqual(2);
});

test('returns empty results for non-existent category', function () {
    $tool = new \App\Mcp\Tools\GetLessonByCategory();
    $request = new Request(['category' => 'nonexistent-category']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBe(0)
        ->and($data['lessons'])->toBeArray();
});

test('only returns generic lessons', function () {
    Lesson::factory()->create([
        'category' => 'validation',
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'category' => 'validation',
        'is_generic' => false,
    ]);

    $tool = new \App\Mcp\Tools\GetLessonByCategory();
    $request = new Request(['category' => 'validation']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBe(1);
});

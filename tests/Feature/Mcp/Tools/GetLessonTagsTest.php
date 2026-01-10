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

test('gets all unique tags from lessons', function () {
    Lesson::factory()->create([
        'tags' => ['php', 'laravel', 'api'],
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'tags' => ['php', 'best-practices'],
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'tags' => ['vue', 'frontend'],
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetLessonTags();
    $request = new Request([]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['tags'])->toBeArray()
        ->and($data['count'])->toBe(6) // php, laravel, api, best-practices, vue, frontend
        ->and($data['tags'])->toContain('php')
        ->and($data['tags'])->toContain('laravel');
});

test('returns sorted tags', function () {
    Lesson::factory()->create([
        'tags' => ['zebra', 'apple', 'banana'],
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetLessonTags();
    $request = new Request([]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    $tags = $data['tags'];
    expect($tags)->toBeArray();
    // Check if sorted (first should be 'apple', last should be 'zebra')
    if (count($tags) > 1) {
        expect($tags[0])->toBeLessThanOrEqual($tags[count($tags) - 1]);
    }
});

test('returns empty array when no lessons have tags', function () {
    Lesson::factory()->create([
        'tags' => null,
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetLessonTags();
    $request = new Request([]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['tags'])->toBeArray()
        ->and($data['count'])->toBe(0);
});

test('only includes tags from generic lessons', function () {
    Lesson::factory()->create([
        'tags' => ['generic-tag'],
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'tags' => ['non-generic-tag'],
        'is_generic' => false,
    ]);

    $tool = new \App\Mcp\Tools\GetLessonTags();
    $request = new Request([]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['tags'])->toContain('generic-tag')
        ->and($data['tags'])->not->toContain('non-generic-tag');
});

test('handles lessons with empty tags array', function () {
    Lesson::factory()->create([
        'tags' => [],
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'tags' => ['valid-tag'],
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetLessonTags();
    $request = new Request([]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['tags'])->toContain('valid-tag')
        ->and($data['count'])->toBe(1);
});

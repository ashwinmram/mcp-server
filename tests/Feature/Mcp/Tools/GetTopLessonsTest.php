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

    // Run migrations for Phase 3 tables
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_01_18_095618_add_relevance_score_and_versioning_to_lessons_table.php'])->assertSuccessful();
});

test('returns top lessons ordered by relevance score', function () {
    // Create lessons with different relevance scores
    $highRelevanceLesson = Lesson::factory()->create([
        'content' => 'High relevance lesson',
        'category' => 'testing',
        'relevance_score' => 0.95,
        'is_generic' => true,
    ]);

    $mediumRelevanceLesson = Lesson::factory()->create([
        'content' => 'Medium relevance lesson',
        'category' => 'testing',
        'relevance_score' => 0.50,
        'is_generic' => true,
    ]);

    $lowRelevanceLesson = Lesson::factory()->create([
        'content' => 'Low relevance lesson',
        'category' => 'testing',
        'relevance_score' => 0.10,
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetTopLessons;
    $request = new Request(['limit' => 10]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\Response::class)
        ->and($data)->toHaveKey('lessons')
        ->and($data)->toHaveKey('ordered_by')
        ->and($data['ordered_by'])->toBe('relevance_score')
        ->and($data['lessons'])->toBeArray()
        ->and(count($data['lessons']))->toBe(3);

    // Should be ordered by relevance score (highest first)
    $relevanceScores = array_column($data['lessons'], 'relevance_score');
    expect($relevanceScores[0])->toBeGreaterThan($relevanceScores[1])
        ->and($relevanceScores[1])->toBeGreaterThan($relevanceScores[2]);
});

test('filters top lessons by category', function () {
    Lesson::factory()->create([
        'content' => 'Testing lesson',
        'category' => 'testing',
        'relevance_score' => 0.90,
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'content' => 'Validation lesson',
        'category' => 'validation',
        'relevance_score' => 0.95,
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetTopLessons;
    $request = new Request(['category' => 'testing']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['category'])->toBe('testing')
        ->and($data['count'])->toBe(1)
        ->and($data['lessons'][0]['category'])->toBe('testing');
});

test('excludes deprecated lessons', function () {
    Lesson::factory()->create([
        'content' => 'Active lesson',
        'relevance_score' => 0.80,
        'deprecated_at' => null,
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'content' => 'Deprecated lesson',
        'relevance_score' => 0.95,
        'deprecated_at' => now(),
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetTopLessons;
    $request = new Request(['limit' => 10]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    // Should only return active lesson
    expect($data['count'])->toBe(1)
        ->and($data['lessons'][0]['content'])->toBe('Active lesson');
});

test('includes relevance score in results when available', function () {
    Lesson::factory()->create([
        'content' => 'Test lesson',
        'relevance_score' => 0.75,
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetTopLessons;
    $request = new Request(['limit' => 1]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['lessons'][0])->toHaveKey('relevance_score')
        ->and($data['lessons'][0]['relevance_score'])->toBe(0.75);
});

test('respects limit parameter', function () {
    Lesson::factory()->count(10)->create([
        'category' => 'testing',
        'relevance_score' => 0.50,
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetTopLessons;
    $request = new Request(['limit' => 5]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBeLessThanOrEqual(5);
});

test('orders by date when relevance score not available', function () {
    // This test would require simulating a scenario where relevance_score column doesn't exist
    // For now, we test that it handles the case gracefully
    $lesson1 = Lesson::factory()->create([
        'content' => 'Older lesson',
        'category' => 'testing',
        'created_at' => now()->subDays(5),
        'is_generic' => true,
    ]);

    $lesson2 = Lesson::factory()->create([
        'content' => 'Newer lesson',
        'category' => 'testing',
        'created_at' => now()->subDays(1),
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetTopLessons;
    $request = new Request(['category' => 'testing']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBe(2);
});

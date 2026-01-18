<?php

use App\Models\Lesson;
use App\Models\LessonUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);

    // Run migrations for Phase 3 tables
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_01_18_095617_create_lesson_usages_table.php'])->assertSuccessful();
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_01_18_095618_add_relevance_score_and_versioning_to_lessons_table.php'])->assertSuccessful();
});

test('returns statistics for all categories', function () {
    Lesson::factory()->count(5)->create([
        'category' => 'testing',
        'relevance_score' => 0.75,
        'is_generic' => true,
    ]);

    Lesson::factory()->count(3)->create([
        'category' => 'validation',
        'relevance_score' => 0.60,
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetCategoryStatistics;
    $request = new Request([]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\Response::class)
        ->and($data)->toHaveKey('categories')
        ->and($data)->toHaveKey('total_categories')
        ->and($data['total_categories'])->toBe(2)
        ->and($data['categories'])->toBeArray()
        ->and(count($data['categories']))->toBe(2);

    // Should have category statistics
    $testingCategory = collect($data['categories'])->firstWhere('category', 'testing');
    expect($testingCategory)->not->toBeNull()
        ->and($testingCategory['total_lessons'])->toBe(5)
        ->and($testingCategory)->toHaveKey('avg_relevance_score');
});

test('returns statistics for a specific category', function () {
    Lesson::factory()->count(5)->create([
        'category' => 'testing',
        'relevance_score' => 0.75,
        'is_generic' => true,
    ]);

    Lesson::factory()->count(3)->create([
        'category' => 'validation',
        'relevance_score' => 0.60,
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetCategoryStatistics;
    $request = new Request(['category' => 'testing']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data)->toHaveKey('category')
        ->and($data['category'])->toBe('testing')
        ->and($data['total_lessons'])->toBe(5)
        ->and($data)->toHaveKey('relevance_score')
        ->and($data['relevance_score'])->toHaveKey('average');
});

test('includes top lessons when requested', function () {
    Lesson::factory()->create([
        'content' => 'Top lesson',
        'category' => 'testing',
        'title' => 'Top Lesson Title',
        'relevance_score' => 0.95,
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'content' => 'Lower lesson',
        'category' => 'testing',
        'relevance_score' => 0.50,
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetCategoryStatistics;
    $request = new Request([
        'category' => 'testing',
        'include_top_lessons' => true,
        'top_lessons_limit' => 1,
    ]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data)->toHaveKey('top_lessons')
        ->and($data['top_lessons'])->toBeArray()
        ->and(count($data['top_lessons']))->toBe(1)
        ->and($data['top_lessons'][0]['title'])->toBe('Top Lesson Title')
        ->and($data['top_lessons'][0]['relevance_score'])->toBe(0.95);
});

test('excludes top lessons when not requested', function () {
    Lesson::factory()->count(3)->create([
        'category' => 'testing',
        'relevance_score' => 0.75,
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetCategoryStatistics;
    $request = new Request([
        'category' => 'testing',
        'include_top_lessons' => false,
    ]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data)->not->toHaveKey('top_lessons');
});

test('includes usage statistics when available', function () {
    $lesson = Lesson::factory()->create([
        'category' => 'testing',
        'relevance_score' => 0.75,
        'is_generic' => true,
    ]);

    LessonUsage::factory()->count(10)->create([
        'lesson_id' => $lesson->id,
        'was_helpful' => true,
    ]);

    LessonUsage::factory()->count(2)->create([
        'lesson_id' => $lesson->id,
        'was_helpful' => false,
    ]);

    $tool = new \App\Mcp\Tools\GetCategoryStatistics;
    $request = new Request(['category' => 'testing']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data)->toHaveKey('usage')
        ->and($data['usage'])->toHaveKey('total_usages')
        ->and($data['usage'])->toHaveKey('helpfulness_rate')
        ->and($data['usage']['total_usages'])->toBe(12)
        ->and($data['usage']['helpfulness_rate'])->toBeGreaterThan(80); // 10/12 = 83.33%
});

test('returns error for non-existent category', function () {
    $tool = new \App\Mcp\Tools\GetCategoryStatistics;
    $request = new Request(['category' => 'nonexistent']);

    $response = $tool->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('not found');
});

test('orders categories by average relevance score', function () {
    Lesson::factory()->count(3)->create([
        'category' => 'lower-category',
        'relevance_score' => 0.50,
        'is_generic' => true,
    ]);

    Lesson::factory()->count(3)->create([
        'category' => 'higher-category',
        'relevance_score' => 0.90,
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\GetCategoryStatistics;
    $request = new Request([]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    // Higher relevance category should come first
    expect($data['categories'][0]['category'])->toBe('higher-category')
        ->and($data['categories'][0]['avg_relevance_score'])->toBeGreaterThan($data['categories'][1]['avg_relevance_score']);
});

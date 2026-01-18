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

test('searches lessons by keyword using FULLTEXT search', function () {
    $tool = new \App\Mcp\Tools\SearchLessons;
    $request = new Request(['query' => 'type hints']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\Response::class)
        ->and($data['count'])->toBeGreaterThan(0)
        ->and($data['results'][0]['content'])->toContain('type hints');
});

test('includes title and summary in search results', function () {
    $lesson = Lesson::factory()->create([
        'content' => 'Always use type hints in PHP functions for better code quality and type safety',
        'title' => 'PHP Type Hints Best Practice',
        'summary' => 'Use type hints for better code quality',
        'category' => 'coding',
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\SearchLessons;
    // Search for a word that definitely exists in the content
    $request = new Request(['query' => 'functions']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    // Find our lesson in the results
    $ourLesson = collect($data['results'])->firstWhere('id', $lesson->id);

    expect($ourLesson)->not->toBeNull()
        ->and($ourLesson)->toHaveKey('title')
        ->and($ourLesson)->toHaveKey('summary')
        ->and($ourLesson['title'])->toBe('PHP Type Hints Best Practice')
        ->and($ourLesson['summary'])->toBe('Use type hints for better code quality');
});

test('filters lessons by category', function () {
    $tool = new \App\Mcp\Tools\SearchLessons;
    $request = new Request(['category' => 'validation']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBe(1)
        ->and($data['results'][0]['category'])->toBe('validation');
});

test('filters lessons by tags', function () {
    $tool = new \App\Mcp\Tools\SearchLessons;
    $request = new Request(['tags' => ['php']]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBe(1)
        ->and($data['results'][0]['tags'])->toContain('php');
});

test('respects limit parameter', function () {
    Lesson::factory()->count(5)->create(['is_generic' => true]);

    $tool = new \App\Mcp\Tools\SearchLessons;
    $request = new Request(['limit' => 2]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBeLessThanOrEqual(2);
});

test('only returns generic lessons', function () {
    $tool = new \App\Mcp\Tools\SearchLessons;
    $request = new Request([]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    // Should not include non-generic lesson (only 2 generic lessons)
    expect($data['count'])->toBe(2)
        ->and($data['results'])->each->toHaveKey('content');
});

test('returns empty results when no matches', function () {
    $tool = new \App\Mcp\Tools\SearchLessons;
    $request = new Request(['query' => 'nonexistentkeyword12345']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBe(0)
        ->and($data['results'])->toBeArray();
});

test('includes related lessons when include_related is true', function () {
    $lesson1 = Lesson::factory()->create([
        'content' => 'Main lesson content',
        'category' => 'testing',
        'tags' => ['php', 'pest'],
        'is_generic' => true,
    ]);

    $lesson2 = Lesson::factory()->create([
        'content' => 'Related lesson content',
        'category' => 'testing',
        'tags' => ['php', 'pest'],
        'is_generic' => true,
    ]);

    // Create relationship
    \DB::table('lesson_relationships')->insert([
        'id' => \Str::uuid(),
        'lesson_id' => $lesson1->id,
        'related_lesson_id' => $lesson2->id,
        'relationship_type' => 'related',
        'relevance_score' => 0.8,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tool = new \App\Mcp\Tools\SearchLessons;
    $request = new Request(['query' => 'Main lesson', 'include_related' => true]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['results'][0])->toHaveKey('related_lessons')
        ->and($data['results'][0]['related_lessons'])->toBeArray()
        ->and(count($data['results'][0]['related_lessons']))->toBeGreaterThan(0);
});

test('tracks usage when lessons are retrieved', function () {
    // Run migrations for Phase 3 tables
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_01_18_095617_create_lesson_usages_table.php'])->assertSuccessful();

    $lesson = Lesson::factory()->create([
        'content' => 'Trackable lesson content',
        'category' => 'testing',
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\SearchLessons;
    $request = new Request(['query' => 'Trackable']);

    $response = $tool->handle($request);

    // Verify usage was tracked
    $usage = \App\Models\LessonUsage::where('lesson_id', $lesson->id)->first();
    expect($usage)->not->toBeNull()
        ->and($usage->query_context)->toContain('query: Trackable');
});

test('filters out deprecated lessons by default', function () {
    // Run migrations for Phase 3 tables
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_01_18_095618_add_relevance_score_and_versioning_to_lessons_table.php'])->assertSuccessful();

    $activeLesson = Lesson::factory()->create([
        'content' => 'Active lesson content',
        'category' => 'testing',
        'is_generic' => true,
        'deprecated_at' => null,
    ]);

    $deprecatedLesson = Lesson::factory()->create([
        'content' => 'Deprecated lesson content',
        'category' => 'testing',
        'is_generic' => true,
        'deprecated_at' => now(),
    ]);

    $tool = new \App\Mcp\Tools\SearchLessons;
    $request = new Request(['category' => 'testing']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    // Should only return active lesson
    expect($data['count'])->toBe(1)
        ->and($data['results'][0]['id'])->toBe($activeLesson->id)
        ->and($data['results'][0]['id'])->not->toBe($deprecatedLesson->id);
});

test('includes deprecated lessons when include_deprecated is true', function () {
    // Run migrations for Phase 3 tables
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_01_18_095618_add_relevance_score_and_versioning_to_lessons_table.php'])->assertSuccessful();

    $activeLesson = Lesson::factory()->create([
        'content' => 'Active lesson content',
        'category' => 'testing',
        'is_generic' => true,
        'deprecated_at' => null,
    ]);

    $deprecatedLesson = Lesson::factory()->create([
        'content' => 'Deprecated lesson content',
        'category' => 'testing',
        'is_generic' => true,
        'deprecated_at' => now(),
    ]);

    $tool = new \App\Mcp\Tools\SearchLessons;
    $request = new Request([
        'category' => 'testing',
        'include_deprecated' => true,
    ]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    // Should return both active and deprecated lessons
    expect($data['count'])->toBe(2);
    $lessonIds = collect($data['results'])->pluck('id')->toArray();
    expect($lessonIds)->toContain($activeLesson->id)
        ->and($lessonIds)->toContain($deprecatedLesson->id);
});

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

test('marks a lesson as helpful', function () {
    $lesson = Lesson::factory()->create([
        'content' => 'Test lesson content',
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\MarkLessonHelpful;
    $request = new Request([
        'lesson_id' => $lesson->id,
        'was_helpful' => true,
    ]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\Response::class)
        ->and($data['success'])->toBeTrue()
        ->and($data['message'])->toContain('helpful')
        ->and($data['lesson_id'])->toBe($lesson->id);

    // Verify usage was created
    $usage = LessonUsage::where('lesson_id', $lesson->id)->first();
    expect($usage)->not->toBeNull()
        ->and($usage->was_helpful)->toBeTrue();
});

test('marks a lesson as not helpful', function () {
    $lesson = Lesson::factory()->create([
        'content' => 'Test lesson content',
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\MarkLessonHelpful;
    $request = new Request([
        'lesson_id' => $lesson->id,
        'was_helpful' => false,
    ]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['success'])->toBeTrue()
        ->and($data['message'])->toContain('not helpful');

    // Verify usage was created
    $usage = LessonUsage::where('lesson_id', $lesson->id)->first();
    expect($usage->was_helpful)->toBeFalse();
});

test('updates existing usage when marking lesson', function () {
    $lesson = Lesson::factory()->create([
        'content' => 'Test lesson content',
        'is_generic' => true,
    ]);

    // Create initial usage
    LessonUsage::create([
        'lesson_id' => $lesson->id,
        'query_context' => 'Initial search',
        'was_helpful' => null,
        'session_id' => 'test-session',
    ]);

    $tool = new \App\Mcp\Tools\MarkLessonHelpful;
    $request = new Request([
        'lesson_id' => $lesson->id,
        'was_helpful' => true,
    ]);

    $response = $tool->handle($request);

    // Verify existing usage was updated (not duplicated)
    $usages = LessonUsage::where('lesson_id', $lesson->id)->get();
    expect($usages)->toHaveCount(1)
        ->and($usages->first()->was_helpful)->toBeTrue();
});

test('returns error when lesson ID is missing', function () {
    $tool = new \App\Mcp\Tools\MarkLessonHelpful;
    $request = new Request(['was_helpful' => true]);

    $response = $tool->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Lesson ID is required');
});

test('returns error when lesson not found', function () {
    $tool = new \App\Mcp\Tools\MarkLessonHelpful;
    $request = new Request([
        'lesson_id' => 'non-existent-id',
        'was_helpful' => true,
    ]);

    $response = $tool->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('not found');
});

test('defaults was_helpful to true when not provided', function () {
    $lesson = Lesson::factory()->create([
        'content' => 'Test lesson content',
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\MarkLessonHelpful;
    $request = new Request(['lesson_id' => $lesson->id]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['message'])->toContain('helpful');

    $usage = LessonUsage::where('lesson_id', $lesson->id)->first();
    expect($usage->was_helpful)->toBeTrue();
});

<?php

use App\Models\Lesson;
use App\Models\LessonUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Run migrations for Phase 3 tables
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_01_18_095617_create_lesson_usages_table.php'])->assertSuccessful();
});

test('belongs to a lesson', function () {
    $lesson = Lesson::factory()->create([
        'content' => 'Test lesson content',
        'is_generic' => true,
    ]);

    $usage = LessonUsage::create([
        'lesson_id' => $lesson->id,
        'query_context' => 'test query',
        'was_helpful' => true,
        'session_id' => 'test-session',
    ]);

    expect($usage->lesson)->toBeInstanceOf(Lesson::class)
        ->and($usage->lesson->id)->toBe($lesson->id);
});

test('lesson has many usages', function () {
    $lesson = Lesson::factory()->create([
        'content' => 'Test lesson content',
        'is_generic' => true,
    ]);

    LessonUsage::create([
        'lesson_id' => $lesson->id,
        'query_context' => 'query 1',
        'was_helpful' => true,
    ]);

    LessonUsage::create([
        'lesson_id' => $lesson->id,
        'query_context' => 'query 2',
        'was_helpful' => false,
    ]);

    expect($lesson->usages)->toHaveCount(2)
        ->and($lesson->usages->first())->toBeInstanceOf(LessonUsage::class);
});

test('casts was_helpful to boolean', function () {
    $lesson = Lesson::factory()->create(['is_generic' => true]);

    $usage = LessonUsage::create([
        'lesson_id' => $lesson->id,
        'was_helpful' => 1, // Integer instead of boolean
    ]);

    expect($usage->was_helpful)->toBeBool()
        ->and($usage->was_helpful)->toBeTrue();
});

test('can have null was_helpful for implicit usage tracking', function () {
    $lesson = Lesson::factory()->create(['is_generic' => true]);

    $usage = LessonUsage::create([
        'lesson_id' => $lesson->id,
        'query_context' => 'test query',
        'was_helpful' => null, // Implicit tracking
    ]);

    expect($usage->was_helpful)->toBeNull();
    expect($usage->refresh()->was_helpful)->toBeNull();
});

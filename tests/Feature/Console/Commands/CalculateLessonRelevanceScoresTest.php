<?php

use App\Models\Lesson;
use App\Models\LessonUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Run migrations for Phase 3 tables
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_01_18_095617_create_lesson_usages_table.php'])->assertSuccessful();
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_01_18_095618_add_relevance_score_and_versioning_to_lessons_table.php'])->assertSuccessful();
});

test('calculates relevance scores for lessons', function () {
    // Create lessons with different usage patterns
    $popularLesson = Lesson::factory()->create([
        'content' => 'Popular lesson content',
        'is_generic' => true,
        'relevance_score' => 0.0,
        'created_at' => now()->subDays(10), // Recently created
    ]);

    $oldLesson = Lesson::factory()->create([
        'content' => 'Old lesson content',
        'is_generic' => true,
        'relevance_score' => 0.0,
        'created_at' => now()->subDays(400), // Old lesson
    ]);

    // Create usages for popular lesson
    LessonUsage::factory()->count(10)->create([
        'lesson_id' => $popularLesson->id,
        'was_helpful' => true, // All helpful
    ]);

    // Create usages for old lesson (fewer, some not helpful)
    LessonUsage::factory()->count(3)->create([
        'lesson_id' => $oldLesson->id,
        'was_helpful' => true,
    ]);
    LessonUsage::factory()->count(2)->create([
        'lesson_id' => $oldLesson->id,
        'was_helpful' => false,
    ]);

    $this->artisan('lessons:calculate-relevance-scores')
        ->assertSuccessful()
        ->expectsOutput('Calculating relevance scores for all lessons...');

    // Refresh models
    $popularLesson->refresh();
    $oldLesson->refresh();

    // Popular lesson should have higher relevance score
    expect($popularLesson->relevance_score)->toBeGreaterThan($oldLesson->relevance_score)
        ->and($popularLesson->relevance_score)->toBeGreaterThan(0.0)
        ->and($oldLesson->relevance_score)->toBeGreaterThan(0.0);
});

test('handles lessons with no usage', function () {
    $lesson = Lesson::factory()->create([
        'content' => 'Unused lesson content',
        'is_generic' => true,
        'relevance_score' => 0.5,
    ]);

    $this->artisan('lessons:calculate-relevance-scores')
        ->assertSuccessful();

    $lesson->refresh();

    // Lesson with no usage should still have some score (from recency weight)
    // but lower than lessons with usage
    expect($lesson->relevance_score)->toBeGreaterThanOrEqual(0.0)
        ->and($lesson->relevance_score)->toBeLessThan(1.0);
});

test('dry run shows what would be updated', function () {
    $lesson = Lesson::factory()->create([
        'content' => 'Test lesson content',
        'is_generic' => true,
        'relevance_score' => 0.0,
    ]);

    LessonUsage::factory()->count(5)->create([
        'lesson_id' => $lesson->id,
        'was_helpful' => true,
    ]);

    $this->artisan('lessons:calculate-relevance-scores', ['--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutput('Calculating relevance scores for all lessons...');

    // Lesson should not be updated in dry run
    $lesson->refresh();
    expect($lesson->relevance_score)->toBe(0.0);
});

test('handles lessons with partial helpful feedback', function () {
    $lesson = Lesson::factory()->create([
        'content' => 'Partially helpful lesson',
        'is_generic' => true,
        'relevance_score' => 0.0,
        'created_at' => now()->subDays(30),
    ]);

    // 70% helpful (7 helpful, 3 not helpful)
    LessonUsage::factory()->count(7)->create([
        'lesson_id' => $lesson->id,
        'was_helpful' => true,
    ]);
    LessonUsage::factory()->count(3)->create([
        'lesson_id' => $lesson->id,
        'was_helpful' => false,
    ]);

    $this->artisan('lessons:calculate-relevance-scores')
        ->assertSuccessful();

    $lesson->refresh();

    // Lesson should have a score between 0 and 1
    // With 70% helpfulness and recent creation, score should be reasonable
    expect($lesson->relevance_score)->toBeGreaterThan(0.0)
        ->and($lesson->relevance_score)->toBeLessThanOrEqual(1.0)
        ->and($lesson->relevance_score)->toBeGreaterThan(0.3); // Should be reasonable given 70% helpful rate
});

// Note: This test is skipped because RefreshDatabase runs migrations automatically
// In a real scenario, this would be tested with a separate database connection
// test('returns error when migrations not run', function () {
//     // Don't run migrations - should fail gracefully
//     $this->artisan('lessons:calculate-relevance-scores')
//         ->assertFailed()
//         ->expectsOutput('lesson_usages table does not exist');
// });

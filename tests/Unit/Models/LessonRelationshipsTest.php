<?php

use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('lesson can have related lessons', function () {
    $lesson1 = Lesson::factory()->create(['is_generic' => true]);
    $lesson2 = Lesson::factory()->create(['is_generic' => true]);

    \DB::table('lesson_relationships')->insert([
        'id' => \Str::uuid(),
        'lesson_id' => $lesson1->id,
        'related_lesson_id' => $lesson2->id,
        'relationship_type' => 'related',
        'relevance_score' => 0.8,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $relatedLessons = $lesson1->getAllRelatedLessons();

    expect($relatedLessons)->toHaveCount(1)
        ->and($relatedLessons->first()->id)->toBe($lesson2->id);
});

test('lesson can filter related lessons by type', function () {
    $lesson1 = Lesson::factory()->create(['is_generic' => true]);
    $lesson2 = Lesson::factory()->create(['is_generic' => true]);
    $lesson3 = Lesson::factory()->create(['is_generic' => true]);

    \DB::table('lesson_relationships')->insert([
        [
            'id' => \Str::uuid(),
            'lesson_id' => $lesson1->id,
            'related_lesson_id' => $lesson2->id,
            'relationship_type' => 'prerequisite',
            'relevance_score' => 0.9,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => \Str::uuid(),
            'lesson_id' => $lesson1->id,
            'related_lesson_id' => $lesson3->id,
            'relationship_type' => 'related',
            'relevance_score' => 0.7,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $prerequisites = $lesson1->getRelatedLessonsByType('prerequisite');

    expect($prerequisites)->toHaveCount(1)
        ->and($prerequisites->first()->id)->toBe($lesson2->id);
});

test('lesson can find similar lessons by category and tags', function () {
    $lesson1 = Lesson::factory()->create([
        'category' => 'testing',
        'tags' => ['php', 'pest', 'laravel'],
        'is_generic' => true,
    ]);

    $lesson2 = Lesson::factory()->create([
        'category' => 'testing',
        'tags' => ['php', 'pest'],
        'is_generic' => true,
    ]);

    $lesson3 = Lesson::factory()->create([
        'category' => 'routing',
        'tags' => ['laravel'],
        'is_generic' => true,
    ]);

    $similarLessons = $lesson1->findSimilarLessons();

    expect($similarLessons)->toHaveCount(1)
        ->and($similarLessons->first()->id)->toBe($lesson2->id);
});

test('related lessons include relationship metadata', function () {
    $lesson1 = Lesson::factory()->create(['is_generic' => true]);
    $lesson2 = Lesson::factory()->create(['is_generic' => true]);

    \DB::table('lesson_relationships')->insert([
        'id' => \Str::uuid(),
        'lesson_id' => $lesson1->id,
        'related_lesson_id' => $lesson2->id,
        'relationship_type' => 'prerequisite',
        'relevance_score' => 0.85,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $relatedLessons = $lesson1->getAllRelatedLessons();

    expect($relatedLessons->first()->pivot->relationship_type)->toBe('prerequisite')
        ->and($relatedLessons->first()->pivot->relevance_score)->toBe(0.85);
});

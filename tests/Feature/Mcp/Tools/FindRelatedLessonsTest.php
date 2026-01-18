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

test('finds related lessons for a lesson', function () {
    $lesson1 = Lesson::factory()->create([
        'content' => 'Main lesson',
        'category' => 'testing',
        'is_generic' => true,
    ]);

    $lesson2 = Lesson::factory()->create([
        'content' => 'Related lesson',
        'category' => 'testing',
        'is_generic' => true,
    ]);

    \DB::table('lesson_relationships')->insert([
        'id' => \Str::uuid(),
        'lesson_id' => $lesson1->id,
        'related_lesson_id' => $lesson2->id,
        'relationship_type' => 'related',
        'relevance_score' => 0.8,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tool = new \App\Mcp\Tools\FindRelatedLessons();
    $request = new Request(['lesson_id' => $lesson1->id]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['lesson_id'])->toBe($lesson1->id)
        ->and($data['count'])->toBe(1)
        ->and($data['related_lessons'][0]['id'])->toBe($lesson2->id)
        ->and($data['related_lessons'][0]['relationship_type'])->toBe('related');
});

test('filters by relationship type', function () {
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

    $tool = new \App\Mcp\Tools\FindRelatedLessons();
    $request = new Request([
        'lesson_id' => $lesson1->id,
        'relationship_type' => 'prerequisite',
    ]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBe(1)
        ->and($data['related_lessons'][0]['relationship_type'])->toBe('prerequisite')
        ->and($data['related_lessons'][0]['id'])->toBe($lesson2->id);
});

test('respects limit parameter', function () {
    $lesson1 = Lesson::factory()->create(['is_generic' => true]);

    // Create 5 related lessons
    $relatedLessons = Lesson::factory()->count(5)->create(['is_generic' => true]);

    foreach ($relatedLessons as $related) {
        \DB::table('lesson_relationships')->insert([
            'id' => \Str::uuid(),
            'lesson_id' => $lesson1->id,
            'related_lesson_id' => $related->id,
            'relationship_type' => 'related',
            'relevance_score' => 0.8,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $tool = new \App\Mcp\Tools\FindRelatedLessons();
    $request = new Request([
        'lesson_id' => $lesson1->id,
        'limit' => 2,
    ]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBeLessThanOrEqual(2);
});

test('returns error when lesson_id is missing', function () {
    $tool = new \App\Mcp\Tools\FindRelatedLessons();
    $request = new Request([]);

    $response = $tool->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('lesson_id is required');
});

test('returns error when lesson not found', function () {
    $tool = new \App\Mcp\Tools\FindRelatedLessons();
    $request = new Request(['lesson_id' => '00000000-0000-0000-0000-000000000000']);

    $response = $tool->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Lesson not found');
});

test('includes title and summary in related lessons', function () {
    $lesson1 = Lesson::factory()->create(['is_generic' => true]);
    $lesson2 = Lesson::factory()->create([
        'title' => 'Related Lesson Title',
        'summary' => 'Related lesson summary',
        'is_generic' => true,
    ]);

    \DB::table('lesson_relationships')->insert([
        'id' => \Str::uuid(),
        'lesson_id' => $lesson1->id,
        'related_lesson_id' => $lesson2->id,
        'relationship_type' => 'related',
        'relevance_score' => 0.8,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tool = new \App\Mcp\Tools\FindRelatedLessons();
    $request = new Request(['lesson_id' => $lesson1->id]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['related_lessons'][0])->toHaveKey('title')
        ->and($data['related_lessons'][0])->toHaveKey('summary')
        ->and($data['related_lessons'][0]['title'])->toBe('Related Lesson Title')
        ->and($data['related_lessons'][0]['summary'])->toBe('Related lesson summary');
});

test('returns empty array when no related lessons', function () {
    $lesson = Lesson::factory()->create(['is_generic' => true]);

    $tool = new \App\Mcp\Tools\FindRelatedLessons();
    $request = new Request(['lesson_id' => $lesson->id]);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['count'])->toBe(0)
        ->and($data['related_lessons'])->toBeArray();
});

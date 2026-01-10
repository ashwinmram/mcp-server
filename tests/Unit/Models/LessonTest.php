<?php

use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create a lesson', function () {
    $lesson = Lesson::factory()->create([
        'source_project' => 'test-project',
        'type' => 'cursor',
        'content' => 'Test lesson content',
    ]);

    expect($lesson)->toBeInstanceOf(Lesson::class)
        ->and($lesson->source_project)->toBe('test-project')
        ->and($lesson->type)->toBe('cursor')
        ->and($lesson->content)->toBe('Test lesson content');
});

test('generates content hash automatically on creation', function () {
    $content = 'Test lesson content';
    $expectedHash = hash('sha256', $content);

    $lesson = Lesson::factory()->create([
        'content' => $content,
    ]);

    expect($lesson->content_hash)->toBe($expectedHash);
});

test('updates content hash when content changes', function () {
    $lesson = Lesson::factory()->create([
        'content' => 'Original content',
    ]);

    $originalHash = $lesson->content_hash;

    $lesson->update(['content' => 'Updated content']);

    expect($lesson->content_hash)->not->toBe($originalHash)
        ->and($lesson->content_hash)->toBe(hash('sha256', 'Updated content'));
});

test('casts tags and metadata to array', function () {
    $lesson = Lesson::factory()->create([
        'tags' => ['tag1', 'tag2'],
        'metadata' => ['key' => 'value'],
    ]);

    expect($lesson->tags)->toBeArray()
        ->and($lesson->metadata)->toBeArray()
        ->and($lesson->tags)->toBe(['tag1', 'tag2'])
        ->and($lesson->metadata)->toBe(['key' => 'value']);
});

test('has generic scope', function () {
    Lesson::factory()->create(['is_generic' => true]);
    Lesson::factory()->create(['is_generic' => false]);

    $genericLessons = Lesson::generic()->get();

    expect($genericLessons)->toHaveCount(1)
        ->and($genericLessons->first()->is_generic)->toBeTrue();
});

test('has byCategory scope', function () {
    Lesson::factory()->create(['category' => 'validation']);
    Lesson::factory()->create(['category' => 'routing']);

    $validationLessons = Lesson::byCategory('validation')->get();

    expect($validationLessons)->toHaveCount(1)
        ->and($validationLessons->first()->category)->toBe('validation');
});

test('has byTags scope', function () {
    Lesson::factory()->create(['tags' => ['laravel', 'api']]);
    Lesson::factory()->create(['tags' => ['vue', 'frontend']]);

    $laravelLessons = Lesson::byTags(['laravel'])->get();

    expect($laravelLessons)->toHaveCount(1)
        ->and($laravelLessons->first()->tags)->toContain('laravel');
});

test('has bySourceProject scope', function () {
    Lesson::factory()->create(['source_project' => 'project-a']);
    Lesson::factory()->create(['source_project' => 'project-b']);

    $projectALessons = Lesson::bySourceProject('project-a')->get();

    expect($projectALessons)->toHaveCount(1)
        ->and($projectALessons->first()->source_project)->toBe('project-a');
});

test('findByContentHash finds lesson by hash and source project', function () {
    $content = 'Test content';
    $hash = Lesson::generateContentHash($content);

    $lesson = Lesson::factory()->create([
        'source_project' => 'test-project',
        'content' => $content,
        'content_hash' => $hash,
    ]);

    $found = Lesson::findByContentHash($hash, 'test-project');

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($lesson->id);
});

test('findByContentHash returns null when not found', function () {
    $hash = hash('sha256', 'non-existent content');

    $found = Lesson::findByContentHash($hash, 'non-existent-project');

    expect($found)->toBeNull();
});

test('generateContentHash generates consistent hash', function () {
    $content = 'Test content';
    $hash1 = Lesson::generateContentHash($content);
    $hash2 = Lesson::generateContentHash($content);

    expect($hash1)->toBe($hash2)
        ->and(strlen($hash1))->toBe(64); // SHA-256 produces 64 character hex string
});

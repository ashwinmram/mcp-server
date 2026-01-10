<?php

use App\Models\Lesson;
use App\Services\LessonImportService;
use App\Services\LessonContentHashService;
use App\Services\LessonValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('processes lessons and creates new ones', function () {
    $service = new LessonImportService(
        new LessonValidationService(),
        new LessonContentHashService()
    );

    $lessons = [
        [
            'type' => 'cursor',
            'content' => 'Always use type hints in PHP functions.',
            'category' => 'coding',
            'tags' => ['php'],
        ],
    ];

    $result = $service->processLessons($lessons, 'test-project');

    expect($result['created'])->toBe(1)
        ->and($result['updated'])->toBe(0)
        ->and($result['skipped'])->toBe(0)
        ->and($result['errors'])->toBeEmpty();

    $this->assertDatabaseHas('lessons', [
        'source_project' => 'test-project',
        'type' => 'cursor',
    ]);
});

test('skips duplicate lessons', function () {
    $service = new LessonImportService(
        new LessonValidationService(),
        new LessonContentHashService()
    );

    $content = 'This is duplicate content';
    $lessons = [
        [
            'type' => 'cursor',
            'content' => $content,
        ],
    ];

    // First processing
    $result1 = $service->processLessons($lessons, 'test-project');
    expect($result1['created'])->toBe(1);

    // Second processing (duplicate)
    $result2 = $service->processLessons($lessons, 'test-project');
    expect($result2['skipped'])->toBe(1);

    $this->assertDatabaseCount('lessons', 1);
});

test('updates lesson when metadata changes', function () {
    $service = new LessonImportService(
        new LessonValidationService(),
        new LessonContentHashService()
    );

    $content = 'Test content';
    $lessons = [
        [
            'type' => 'cursor',
            'content' => $content,
            'metadata' => ['key' => 'value1'],
        ],
    ];

    // First processing
    $result1 = $service->processLessons($lessons, 'test-project');
    expect($result1['created'])->toBe(1);

    // Update with new metadata
    $lessons[0]['metadata'] = ['key' => 'value2'];
    $result2 = $service->processLessons($lessons, 'test-project');
    expect($result2['updated'])->toBe(1);

    $lesson = Lesson::first();
    expect($lesson->metadata['key'])->toBe('value2');
});

test('rejects lessons with project-specific paths', function () {
    $service = new LessonImportService(
        new LessonValidationService(),
        new LessonContentHashService()
    );

    $lessons = [
        [
            'type' => 'cursor',
            'content' => 'The file is at /var/www/myproject/app/Models/User.php',
        ],
    ];

    $result = $service->processLessons($lessons, 'test-project');

    expect($result['created'])->toBe(0)
        ->and($result['errors'])->not->toBeEmpty();
});

test('rejects lessons with missing required fields', function () {
    $service = new LessonImportService(
        new LessonValidationService(),
        new LessonContentHashService()
    );

    $lessons = [
        [
            // Missing type and content
        ],
    ];

    $result = $service->processLessons($lessons, 'test-project');

    expect($result['created'])->toBe(0)
        ->and($result['errors'])->not->toBeEmpty();
});

test('processes multiple lessons', function () {
    $service = new LessonImportService(
        new LessonValidationService(),
        new LessonContentHashService()
    );

    $lessons = [
        [
            'type' => 'cursor',
            'content' => 'First lesson',
        ],
        [
            'type' => 'ai_output',
            'content' => 'Second lesson',
        ],
    ];

    $result = $service->processLessons($lessons, 'test-project');

    expect($result['created'])->toBe(2)
        ->and($result['errors'])->toBeEmpty();

    $this->assertDatabaseCount('lessons', 2);
});

test('handles errors gracefully and continues processing', function () {
    $service = new LessonImportService(
        new LessonValidationService(),
        new LessonContentHashService()
    );

    $lessons = [
        [
            'type' => 'cursor',
            'content' => 'Valid lesson',
        ],
        [
            // Invalid - missing fields
        ],
        [
            'type' => 'ai_output',
            'content' => 'Another valid lesson',
        ],
    ];

    $result = $service->processLessons($lessons, 'test-project');

    expect($result['created'])->toBe(2)
        ->and($result['errors'])->toHaveCount(1);
});

<?php

use App\Models\Lesson;
use App\Services\LessonContentHashService;
use App\Services\LessonImportService;
use App\Services\LessonValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('processes lessons and creates new ones', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
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
        new LessonValidationService,
        new LessonContentHashService
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
        new LessonValidationService,
        new LessonContentHashService
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
        new LessonValidationService,
        new LessonContentHashService
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
        new LessonValidationService,
        new LessonContentHashService
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
        new LessonValidationService,
        new LessonContentHashService
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
        new LessonValidationService,
        new LessonContentHashService
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

test('deduplicates lessons across different source projects', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $content = 'This is duplicate content across projects';
    $lessons = [
        [
            'type' => 'cursor',
            'content' => $content,
            'tags' => ['php', 'laravel'],
        ],
    ];

    // First processing from project-a
    $result1 = $service->processLessons($lessons, 'project-a');
    expect($result1['created'])->toBe(1);

    // Second processing from project-b (same content)
    $result2 = $service->processLessons($lessons, 'project-b');
    expect($result2['updated'])->toBe(1)
        ->and($result2['created'])->toBe(0);

    // Should only have one lesson in database
    $this->assertDatabaseCount('lessons', 1);

    $lesson = Lesson::first();
    expect($lesson->source_project)->toBe('project-a') // Oldest project
        ->and($lesson->source_projects)->toContain('project-a', 'project-b');
});

test('merges tags when duplicate found across projects', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $content = 'Test content for tag merging';
    $lessons1 = [
        [
            'type' => 'cursor',
            'content' => $content,
            'tags' => ['php', 'laravel'],
        ],
    ];

    $lessons2 = [
        [
            'type' => 'cursor',
            'content' => $content,
            'tags' => ['php', 'testing', 'pest'],
        ],
    ];

    // First processing from project-a
    $service->processLessons($lessons1, 'project-a');

    // Second processing from project-b with different tags
    $service->processLessons($lessons2, 'project-b');

    $lesson = Lesson::first();
    $mergedTags = $lesson->tags;
    sort($mergedTags);

    expect($mergedTags)->toContain('php', 'laravel', 'testing', 'pest')
        ->and(count($mergedTags))->toBe(4);
});

test('merges metadata when duplicate found across projects', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $content = 'Test content for metadata merging';
    $lessons1 = [
        [
            'type' => 'cursor',
            'content' => $content,
            'metadata' => ['file' => 'file1.json', 'path' => '/path/to/file1'],
        ],
    ];

    $lessons2 = [
        [
            'type' => 'cursor',
            'content' => $content,
            'metadata' => ['file' => 'file2.json', 'path' => '/path/to/file2'],
        ],
    ];

    // First processing from project-a
    $service->processLessons($lessons1, 'project-a');

    // Second processing from project-b with different metadata
    $service->processLessons($lessons2, 'project-b');

    $lesson = Lesson::first();
    // Metadata should be merged (file2 should overwrite file1, path should be merged)
    expect($lesson->metadata)->toHaveKey('file')
        ->and($lesson->metadata)->toHaveKey('path');
});

test('tracks multiple source projects in source_projects array', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $content = 'Content shared across multiple projects';
    $lessons = [
        [
            'type' => 'cursor',
            'content' => $content,
        ],
    ];

    // Process from three different projects
    $service->processLessons($lessons, 'project-a');
    $service->processLessons($lessons, 'project-b');
    $service->processLessons($lessons, 'project-c');

    $lesson = Lesson::first();
    expect($lesson->source_projects)->toHaveCount(3)
        ->and($lesson->source_projects)->toContain('project-a', 'project-b', 'project-c')
        ->and($lesson->source_project)->toBe('project-a'); // Oldest project
});

test('initializes source_projects array when creating new lesson', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $lessons = [
        [
            'type' => 'cursor',
            'content' => 'New lesson content',
            'tags' => ['php'],
        ],
    ];

    $result = $service->processLessons($lessons, 'new-project');

    expect($result['created'])->toBe(1);

    $lesson = Lesson::first();
    expect($lesson->source_projects)->toBe(['new-project'])
        ->and($lesson->source_project)->toBe('new-project');
});

test('extracts title from lesson data', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $lessons = [
        [
            'type' => 'cursor',
            'content' => 'Test content',
            'title' => 'Test Title',
        ],
    ];

    $service->processLessons($lessons, 'test-project');

    $lesson = Lesson::first();
    expect($lesson->title)->toBe('Test Title');
});

test('extracts title from JSON content', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $lessons = [
        [
            'type' => 'cursor',
            'content' => json_encode(['title' => 'JSON Title', 'description' => 'Test']),
        ],
    ];

    $service->processLessons($lessons, 'test-project');

    $lesson = Lesson::first();
    expect($lesson->title)->toBe('JSON Title');
});

test('extracts summary from lesson data', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $lessons = [
        [
            'type' => 'cursor',
            'content' => 'Test content',
            'summary' => 'Test Summary',
        ],
    ];

    $service->processLessons($lessons, 'test-project');

    $lesson = Lesson::first();
    expect($lesson->summary)->toBe('Test Summary');
});

test('extracts summary from JSON description', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $lessons = [
        [
            'type' => 'cursor',
            'content' => json_encode(['description' => 'JSON Description']),
        ],
    ];

    $service->processLessons($lessons, 'test-project');

    $lesson = Lesson::first();
    expect($lesson->summary)->toBe('JSON Description');
});

test('generates summary from first sentences when not provided', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $lessons = [
        [
            'type' => 'cursor',
            'content' => 'First sentence. Second sentence. Third sentence.',
        ],
    ];

    $service->processLessons($lessons, 'test-project');

    $lesson = Lesson::first();
    expect($lesson->summary)->toContain('First sentence')
        ->and($lesson->summary)->toContain('Second sentence');
});

test('detects and creates relationships between similar lessons', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    // Create first lesson
    $lessons1 = [
        [
            'type' => 'cursor',
            'content' => 'Testing Vue components',
            'category' => 'testing',
            'tags' => ['vue', 'testing', 'components'],
        ],
    ];

    $service->processLessons($lessons1, 'project-a');

    // Create second lesson with same category and overlapping tags
    $lessons2 = [
        [
            'type' => 'cursor',
            'content' => 'Testing Vue dialogs',
            'category' => 'testing',
            'tags' => ['vue', 'testing', 'dialogs'],
        ],
    ];

    $service->processLessons($lessons2, 'project-b');

    // Check if relationship was created
    $lesson1 = Lesson::where('content', 'Testing Vue components')->first();
    $lesson2 = Lesson::where('content', 'Testing Vue dialogs')->first();

    $relationship = \DB::table('lesson_relationships')
        ->where('lesson_id', $lesson2->id)
        ->where('related_lesson_id', $lesson1->id)
        ->first();

    expect($relationship)->not->toBeNull()
        ->and($relationship->relationship_type)->toBe('related')
        ->and($relationship->relevance_score)->toBeGreaterThan(0);
});

test('does not create relationships when tags do not overlap enough', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    // Create first lesson
    $lessons1 = [
        [
            'type' => 'cursor',
            'content' => 'Laravel validation',
            'category' => 'validation',
            'tags' => ['laravel', 'validation'],
        ],
    ];

    $service->processLessons($lessons1, 'project-a');

    // Create second lesson with different tags (no overlap)
    $lessons2 = [
        [
            'type' => 'cursor',
            'content' => 'Vue components',
            'category' => 'frontend',
            'tags' => ['vue', 'components'],
        ],
    ];

    $service->processLessons($lessons2, 'project-b');

    // Check that no relationship was created
    $lesson2 = Lesson::where('content', 'Vue components')->first();

    $relationship = \DB::table('lesson_relationships')
        ->where('lesson_id', $lesson2->id)
        ->first();

    expect($relationship)->toBeNull();
});

test('updates title and summary when updating existing lesson', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $content = 'Test content';
    $lessons1 = [
        [
            'type' => 'cursor',
            'content' => $content,
        ],
    ];

    $service->processLessons($lessons1, 'project-a');

    // Update with title and summary
    $lessons2 = [
        [
            'type' => 'cursor',
            'content' => $content,
            'title' => 'Updated Title',
            'summary' => 'Updated Summary',
        ],
    ];

    $service->processLessons($lessons2, 'project-b');

    $lesson = Lesson::first();
    expect($lesson->title)->toBe('Updated Title')
        ->and($lesson->summary)->toBe('Updated Summary');
});

test('processProjectDetails creates lesson with is_generic false', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $lessons = [
        [
            'type' => 'project_detail',
            'content' => 'Project-specific: config lives in config/app.php',
            'category' => 'project-implementation',
        ],
    ];

    $result = $service->processProjectDetails($lessons, 'my-app');

    expect($result['created'])->toBe(1);
    $lesson = Lesson::first();
    expect($lesson->is_generic)->toBeFalse()
        ->and($lesson->source_project)->toBe('my-app');
});

test('processProjectDetails does not run generic validation', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $lessons = [
        [
            'type' => 'project_detail',
            'content' => 'The file is at /var/www/myproject/app/Models/User.php',
        ],
    ];

    $result = $service->processProjectDetails($lessons, 'my-app');

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toBeEmpty();
});

test('processProjectDetails deduplicates only within same project', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $content = 'Shared content';
    $lessons = [['type' => 'project_detail', 'content' => $content]];

    $result1 = $service->processProjectDetails($lessons, 'project-a');
    $result2 = $service->processProjectDetails($lessons, 'project-b');

    expect($result1['created'])->toBe(1)
        ->and($result2['created'])->toBe(1);
    $this->assertDatabaseCount('lessons', 2);
});

test('processProjectDetails updates existing lesson within same project', function () {
    $service = new LessonImportService(
        new LessonValidationService,
        new LessonContentHashService
    );

    $content = 'Same content';
    $lessons1 = [['type' => 'project_detail', 'content' => $content]];
    $service->processProjectDetails($lessons1, 'my-app');

    $lessons2 = [
        [
            'type' => 'project_detail',
            'content' => $content,
            'title' => 'Updated Title',
        ],
    ];
    $result = $service->processProjectDetails($lessons2, 'my-app');

    expect($result['updated'])->toBe(1)
        ->and($result['created'])->toBe(0);
    $lesson = Lesson::where('source_project', 'my-app')->first();
    expect($lesson->title)->toBe('Updated Title');
});

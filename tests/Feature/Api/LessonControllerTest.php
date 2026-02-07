<?php

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
});

test('can store lessons via API', function () {
    $payload = [
        'source_project' => 'test-project',
        'lessons' => [
            [
                'type' => 'markdown',
                'content' => 'Always use type hints in PHP functions.',
                'category' => 'coding',
                'tags' => ['php', 'best-practices'],
                'metadata' => ['file' => 'lessons-learned.md'],
            ],
        ],
    ];

    $response = $this->postJson('/api/lessons', $payload);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'created',
                'updated',
                'skipped',
                'errors',
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'created' => 1,
            ],
        ]);

    $this->assertDatabaseHas('lessons', [
        'source_project' => 'test-project',
        'type' => 'markdown',
        'category' => 'coding',
    ]);
});

test('requires authentication to store lessons', function () {
    // Refresh the application to clear any authentication state
    // Then make a request without authenticating
    $this->refreshApplication();

    $response = $this->postJson('/api/lessons', [
        'source_project' => 'test-project',
        'lessons' => [
            [
                'type' => 'cursor',
                'content' => 'Test content',
            ],
        ],
    ]);

    $response->assertStatus(401);
});

test('validates required fields when storing lessons', function () {
    $response = $this->postJson('/api/lessons', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['source_project', 'lessons']);
});

test('validates lesson structure when storing', function () {
    $payload = [
        'source_project' => 'test-project',
        'lessons' => [
            [
                // Missing required fields
            ],
        ],
    ];

    $response = $this->postJson('/api/lessons', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['lessons.0.type', 'lessons.0.content']);
});

test('rejects lessons with invalid type', function () {
    $payload = [
        'source_project' => 'test-project',
        'lessons' => [
            [
                'type' => 'invalid-type',
                'content' => 'Test content',
            ],
        ],
    ];

    $response = $this->postJson('/api/lessons', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['lessons.0.type']);
});

test('can list lessons via API', function () {
    Lesson::factory()->count(5)->create([
        'source_project' => 'test-project',
        'is_generic' => true,
    ]);

    $response = $this->getJson('/api/lessons');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'source_project',
                    'type',
                    'content',
                    'created_at',
                ],
            ],
        ]);
});

test('can filter lessons by source project', function () {
    Lesson::factory()->create(['source_project' => 'project-a']);
    Lesson::factory()->create(['source_project' => 'project-b']);

    $response = $this->getJson('/api/lessons?source_project=project-a');

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
    expect($response->json('data.0.source_project'))->toBe('project-a');
});

test('can filter lessons by category', function () {
    Lesson::factory()->create(['category' => 'validation']);
    Lesson::factory()->create(['category' => 'routing']);

    $response = $this->getJson('/api/lessons?category=validation');

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
    expect($response->json('data.0.category'))->toBe('validation');
});

test('can filter lessons by type', function () {
    Lesson::factory()->create(['type' => 'cursor']);
    Lesson::factory()->create(['type' => 'ai_output']);

    $response = $this->getJson('/api/lessons?type=cursor');

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
    expect($response->json('data.0.type'))->toBe('cursor');
});

test('can show a single lesson', function () {
    $lesson = Lesson::factory()->create();

    $response = $this->getJson("/api/lessons/{$lesson->id}");

    $response->assertStatus(200)
        ->assertJson([
            'id' => $lesson->id,
            'content' => $lesson->content,
        ]);
});

test('returns 404 for non-existent lesson', function () {
    $response = $this->getJson('/api/lessons/00000000-0000-0000-0000-000000000000');

    $response->assertStatus(404);
});

test('deduplicates lessons with same content hash', function () {
    $content = 'This is duplicate content';
    $payload = [
        'source_project' => 'test-project',
        'lessons' => [
            [
                'type' => 'cursor',
                'content' => $content,
            ],
        ],
    ];

    // First push
    $response1 = $this->postJson('/api/lessons', $payload);
    $response1->assertStatus(201);
    expect($response1->json('data.created'))->toBe(1);

    // Second push (duplicate)
    $response2 = $this->postJson('/api/lessons', $payload);
    $response2->assertStatus(201);
    expect($response2->json('data.skipped'))->toBe(1);

    // Should only have one lesson in database
    $this->assertDatabaseCount('lessons', 1);
});

test('rejects lessons with project-specific paths', function () {
    $payload = [
        'source_project' => 'test-project',
        'lessons' => [
            [
                'type' => 'cursor',
                'content' => 'The file is at /var/www/myproject/app/Models/User.php',
            ],
        ],
    ];

    $response = $this->postJson('/api/lessons', $payload);

    // All lessons failed validation, should return 422 Unprocessable Entity
    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => 'All lessons failed validation or processing',
    ]);
    // Lesson should be rejected and not stored
    $errors = $response->json('data.errors');
    expect($errors)->not->toBeEmpty();
});

test('can store project details via API', function () {
    $payload = [
        'source_project' => 'my-app',
        'lessons' => [
            [
                'type' => 'project_detail',
                'content' => 'Auth lives in app/Http/Controllers/Auth/. Env uses APP_KEY for encryption.',
                'category' => 'project-implementation',
                'tags' => ['project-details', 'auth'],
                'metadata' => ['file' => 'project-details.md'],
            ],
        ],
    ];

    $response = $this->postJson('/api/project-details', $payload);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'created' => 1,
            ],
        ]);

    $this->assertDatabaseHas('lessons', [
        'source_project' => 'my-app',
        'is_generic' => false,
        'type' => 'project_detail',
    ]);
});

test('project details endpoint accepts project-specific paths', function () {
    $payload = [
        'source_project' => 'test-project',
        'lessons' => [
            [
                'type' => 'project_detail',
                'content' => 'The file is at /var/www/myproject/app/Models/User.php',
            ],
        ],
    ];

    $response = $this->postJson('/api/project-details', $payload);

    $response->assertStatus(201);
    $this->assertDatabaseHas('lessons', [
        'source_project' => 'test-project',
        'is_generic' => false,
        'content' => 'The file is at /var/www/myproject/app/Models/User.php',
    ]);
});

test('requires authentication to store project details', function () {
    $this->refreshApplication();

    $response = $this->postJson('/api/project-details', [
        'source_project' => 'my-app',
        'lessons' => [
            [
                'type' => 'project_detail',
                'content' => 'Test content',
            ],
        ],
    ]);

    $response->assertStatus(401);
});

test('project details deduplicate only within same project', function () {
    $content = 'Same content in two projects';
    $payload = [
        'source_project' => 'project-a',
        'lessons' => [
            [
                'type' => 'project_detail',
                'content' => $content,
            ],
        ],
    ];

    $this->postJson('/api/project-details', $payload)->assertStatus(201);
    $this->postJson('/api/project-details', [
        'source_project' => 'project-b',
        'lessons' => [['type' => 'project_detail', 'content' => $content]],
    ])->assertStatus(201);

    // Two lessons (one per project), not merged
    $this->assertDatabaseCount('lessons', 2);
    expect(Lesson::where('content', $content)->count())->toBe(2);
});

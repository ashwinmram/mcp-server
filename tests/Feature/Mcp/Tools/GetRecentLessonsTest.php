<?php

use App\Mcp\Tools\GetRecentLessons;
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

test('returns lessons ordered by created_at descending', function () {
    $older = Lesson::factory()->create([
        'is_generic' => true,
        'content' => 'Older lesson',
        'created_at' => now()->subDays(2),
    ]);
    $newer = Lesson::factory()->create([
        'is_generic' => true,
        'content' => 'Newer lesson',
        'created_at' => now(),
    ]);

    $tool = new GetRecentLessons;
    $data = getResponseData($tool->handle(new Request(['limit' => 2])));

    expect($data['count'])->toBe(2)
        ->and($data['results'][0]['id'])->toBe($newer->id)
        ->and($data['ordered_by'])->toBe('created_at');
});

test('filters by source_project', function () {
    Lesson::factory()->create([
        'is_generic' => true,
        'source_project' => 'project-a',
        'content' => 'From A',
    ]);
    Lesson::factory()->create([
        'is_generic' => true,
        'source_project' => 'project-b',
        'content' => 'From B',
    ]);

    $tool = new GetRecentLessons;
    $data = getResponseData($tool->handle(new Request(['source_project' => 'project-a'])));

    expect($data['count'])->toBe(1)
        ->and($data['results'][0]['source_project'])->toBe('project-a');
});

test('filters by days parameter', function () {
    Lesson::factory()->create([
        'is_generic' => true,
        'created_at' => now()->subDays(10),
    ]);
    $recent = Lesson::factory()->create([
        'is_generic' => true,
        'created_at' => now()->subDay(),
    ]);

    $tool = new GetRecentLessons;
    $data = getResponseData($tool->handle(new Request(['days' => 3])));

    expect($data['count'])->toBe(1)
        ->and($data['results'][0]['id'])->toBe($recent->id);
});

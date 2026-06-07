<?php

use App\Mcp\Resources\LessonsRecentResource;
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

test('returns recent lessons markdown with ids', function () {
    $lesson = Lesson::factory()->create([
        'is_generic' => true,
        'title' => 'Recent test lesson',
    ]);

    $resource = new LessonsRecentResource;
    $content = getResponseText($resource->handle(new Request([])));

    expect($content)->toContain('Recent Generic Lessons')
        ->and($content)->toContain($lesson->id)
        ->and($content)->toContain('Recent test lesson');
});

test('respects limit parameter', function () {
    Lesson::factory()->count(5)->create(['is_generic' => true]);

    $resource = new LessonsRecentResource;
    $content = getResponseText($resource->handle(new Request(['limit' => 2])));

    expect(substr_count($content, '**ID:**'))->toBe(2);
});

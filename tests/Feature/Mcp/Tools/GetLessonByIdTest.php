<?php

use App\Mcp\Tools\GetLessonById;
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

test('returns generic lesson by id', function () {
    $lesson = Lesson::factory()->create([
        'is_generic' => true,
        'content' => 'Full lesson content',
    ]);

    $tool = new GetLessonById;
    $data = getResponseData($tool->handle(new Request(['lesson_id' => $lesson->id])));

    expect($data['lesson']['id'])->toBe($lesson->id)
        ->and($data['lesson']['content'])->toBe('Full lesson content');
});

test('returns error when lesson is project-specific', function () {
    $lesson = Lesson::factory()->create(['is_generic' => false]);

    $tool = new GetLessonById;
    $response = $tool->handle(new Request(['lesson_id' => $lesson->id]));

    expect(getResponseText($response))->toContain('not found');
});

test('requires lesson_id', function () {
    $tool = new GetLessonById;
    $response = $tool->handle(new Request([]));

    expect(getResponseText($response))->toContain('required');
});

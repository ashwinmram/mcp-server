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

test('provides lessons for specific category', function () {
    Lesson::factory()->create([
        'category' => 'validation',
        'content' => 'Always validate user input',
        'tags' => ['laravel', 'security'],
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'category' => 'validation',
        'content' => 'Use form request classes',
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'category' => 'routing',
        'is_generic' => true,
    ]);

    $prompt = new \App\Mcp\Prompts\LessonsByCategory();
    $request = new Request(['category' => 'validation']);

    $response = $prompt->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Lessons in Category: validation')
        ->and($content)->toContain('Total lessons: 2')
        ->and($content)->toContain('Always validate user input')
        ->and($content)->not->toContain('routing');
});

test('returns message when category is missing', function () {
    $prompt = new \App\Mcp\Prompts\LessonsByCategory();
    $request = new Request([]);

    $response = $prompt->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Please provide a category parameter');
});

test('returns message for non-existent category', function () {
    $prompt = new \App\Mcp\Prompts\LessonsByCategory();
    $request = new Request(['category' => 'nonexistent']);

    $response = $prompt->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('No lessons found in category')
        ->and($content)->toContain('nonexistent');
});

test('limits results to 10 lessons', function () {
    Lesson::factory()->count(15)->create([
        'category' => 'validation',
        'is_generic' => true,
    ]);

    $prompt = new \App\Mcp\Prompts\LessonsByCategory();
    $request = new Request(['category' => 'validation']);

    $response = $prompt->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Showing 10 of 15 lessons');
});

test('only includes generic lessons', function () {
    Lesson::factory()->create([
        'category' => 'validation',
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'category' => 'validation',
        'is_generic' => false,
    ]);

    $prompt = new \App\Mcp\Prompts\LessonsByCategory();
    $request = new Request(['category' => 'validation']);

    $response = $prompt->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Total lessons: 1');
});

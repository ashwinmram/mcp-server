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

test('provides overview of available lessons', function () {
    Lesson::factory()->count(5)->create([
        'is_generic' => true,
        'category' => 'validation',
    ]);

    Lesson::factory()->create([
        'is_generic' => false, // Should be excluded
    ]);

    $prompt = new \App\Mcp\Prompts\LessonsLearnedOverview();
    $request = new Request([]);

    $response = $prompt->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Lessons Learned Overview')
        ->and($content)->toContain('Total generic lessons available: 5')
        ->and($content)->not->toContain('6'); // Should not include non-generic
});

test('lists available categories', function () {
    Lesson::factory()->create(['category' => 'validation', 'is_generic' => true]);
    Lesson::factory()->create(['category' => 'routing', 'is_generic' => true]);
    Lesson::factory()->create(['category' => 'validation', 'is_generic' => true]);

    $prompt = new \App\Mcp\Prompts\LessonsLearnedOverview();
    $request = new Request([]);

    $response = $prompt->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Available Categories')
        ->and($content)->toContain('validation')
        ->and($content)->toContain('routing');
});

test('includes popular tags', function () {
    Lesson::factory()->create([
        'tags' => ['php', 'laravel', 'api'],
        'is_generic' => true,
    ]);

    $prompt = new \App\Mcp\Prompts\LessonsLearnedOverview();
    $request = new Request([]);

    $response = $prompt->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Popular Tags')
        ->and($content)->toContain('php');
});

test('includes recent lessons', function () {
    Lesson::factory()->create([
        'type' => 'cursor',
        'category' => 'coding',
        'is_generic' => true,
    ]);

    $prompt = new \App\Mcp\Prompts\LessonsLearnedOverview();
    $request = new Request([]);

    $response = $prompt->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Recent Lessons')
        ->and($content)->toContain('cursor');
});

test('handles empty lessons gracefully', function () {
    $prompt = new \App\Mcp\Prompts\LessonsLearnedOverview();
    $request = new Request([]);

    $response = $prompt->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Lessons Learned Overview')
        ->and($content)->toContain('Total generic lessons available: 0');
});

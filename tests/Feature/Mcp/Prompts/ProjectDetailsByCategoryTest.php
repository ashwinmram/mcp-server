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
    app()->instance('mcp.project', 'category-project');
});

test('returns project details for category', function () {
    Lesson::factory()->create([
        'source_project' => 'category-project',
        'is_generic' => false,
        'category' => 'auth',
        'title' => 'Auth config',
        'summary' => 'Sanctum is used for API tokens.',
        'tags' => ['auth', 'sanctum'],
    ]);
    Lesson::factory()->create([
        'source_project' => 'category-project',
        'is_generic' => false,
        'category' => 'auth',
        'title' => 'Middleware',
        'summary' => 'auth:sanctum on API routes.',
    ]);
    Lesson::factory()->create([
        'source_project' => 'category-project',
        'is_generic' => false,
        'category' => 'routing',
    ]);

    $prompt = new \App\Mcp\Prompts\ProjectDetailsByCategory;
    $response = $prompt->handle(new Request(['category' => 'auth']));
    $content = getResponseText($response);

    expect($content)->toContain('Project Details in Category: auth')
        ->and($content)->toContain('category-project')
        ->and($content)->toContain('Total details: 2')
        ->and($content)->toContain('Auth config')
        ->and($content)->toContain('Sanctum is used')
        ->and($content)->toContain('Middleware')
        ->and($content)->not->toContain('routing');
});

test('returns message when category is missing', function () {
    $prompt = new \App\Mcp\Prompts\ProjectDetailsByCategory;
    $response = $prompt->handle(new Request([]));
    $content = getResponseText($response);

    expect($content)->toContain('Please provide a category parameter');
});

test('returns message for non-existent category', function () {
    Lesson::factory()->create([
        'source_project' => 'category-project',
        'is_generic' => false,
        'category' => 'auth',
    ]);

    $prompt = new \App\Mcp\Prompts\ProjectDetailsByCategory;
    $response = $prompt->handle(new Request(['category' => 'nonexistent']));
    $content = getResponseText($response);

    expect($content)->toContain('No project details found in category')
        ->and($content)->toContain('nonexistent')
        ->and($content)->toContain('GetProjectDetailsOverview');
});

test('limits results to 10 and suggests tool for more', function () {
    Lesson::factory()->count(15)->create([
        'source_project' => 'category-project',
        'is_generic' => false,
        'category' => 'auth',
    ]);

    $prompt = new \App\Mcp\Prompts\ProjectDetailsByCategory;
    $response = $prompt->handle(new Request(['category' => 'auth']));
    $content = getResponseText($response);

    expect($content)->toContain('Total details: 15')
        ->and($content)->toContain('Showing 10 of 15')
        ->and($content)->toContain('GetProjectDetailsByCategory');
});

test('only includes details for bound project', function () {
    Lesson::factory()->create([
        'source_project' => 'category-project',
        'is_generic' => false,
        'category' => 'auth',
        'title' => 'Our auth',
    ]);
    Lesson::factory()->create([
        'source_project' => 'other-project',
        'is_generic' => false,
        'category' => 'auth',
        'title' => 'Other auth',
    ]);

    $prompt = new \App\Mcp\Prompts\ProjectDetailsByCategory;
    $response = $prompt->handle(new Request(['category' => 'auth']));
    $content = getResponseText($response);

    expect($content)->toContain('Total details: 1')
        ->and($content)->toContain('Our auth')
        ->and($content)->not->toContain('Other auth');
});

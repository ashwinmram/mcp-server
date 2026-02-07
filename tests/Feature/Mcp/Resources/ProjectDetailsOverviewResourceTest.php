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
    app()->instance('mcp.project', 'test-project');
});

test('returns overview with project id, totals, and categories', function () {
    Lesson::factory()->count(2)->create([
        'source_project' => 'test-project',
        'is_generic' => false,
        'category' => 'auth',
    ]);
    Lesson::factory()->create([
        'source_project' => 'test-project',
        'is_generic' => false,
        'category' => 'routing',
    ]);

    $resource = new \App\Mcp\Resources\ProjectDetailsOverviewResource;
    $response = $resource->handle(new Request([]));
    $content = getResponseText($response);

    expect($content)->toContain('Project Details Overview')
        ->and($content)->toContain('test-project')
        ->and($content)->toContain('**Total Details:** 3')
        ->and($content)->toContain('auth')
        ->and($content)->toContain('routing')
        ->and($content)->toContain('How to Use')
        ->and($content)->toContain('SearchProjectDetails')
        ->and($content)->toContain('When to use Project Details vs Lessons Learned');
});

test('excludes generic lessons and other projects', function () {
    Lesson::factory()->create([
        'source_project' => 'test-project',
        'is_generic' => false,
        'category' => 'auth',
    ]);
    Lesson::factory()->create([
        'source_project' => 'test-project',
        'is_generic' => true,
        'category' => 'coding',
    ]);
    Lesson::factory()->create([
        'source_project' => 'other-project',
        'is_generic' => false,
        'category' => 'auth',
    ]);

    $resource = new \App\Mcp\Resources\ProjectDetailsOverviewResource;
    $response = $resource->handle(new Request([]));
    $content = getResponseText($response);

    expect($content)->toContain('**Total Details:** 1')
        ->and($content)->toContain('auth')
        ->and($content)->not->toContain('coding')
        ->and($content)->not->toContain('other-project');
});

test('includes recent project details and tags', function () {
    Lesson::factory()->create([
        'source_project' => 'test-project',
        'is_generic' => false,
        'title' => 'Auth setup',
        'category' => 'auth',
        'tags' => ['mcp', 'auth'],
        'content' => 'Use Sanctum for API tokens.',
    ]);

    $resource = new \App\Mcp\Resources\ProjectDetailsOverviewResource;
    $response = $resource->handle(new Request([]));
    $content = getResponseText($response);

    expect($content)->toContain('Recent Project Details')
        ->and($content)->toContain('Auth setup')
        ->and($content)->toContain('Use Sanctum for API tokens')
        ->and($content)->toContain('Tags in This Project')
        ->and($content)->toContain('mcp');
});

test('handles empty project details gracefully', function () {
    $resource = new \App\Mcp\Resources\ProjectDetailsOverviewResource;
    $response = $resource->handle(new Request([]));
    $content = getResponseText($response);

    expect($content)->toContain('Project Details Overview')
        ->and($content)->toContain('test-project')
        ->and($content)->toContain('**Total Details:** 0')
        ->and($content)->toContain('How to Use');
});

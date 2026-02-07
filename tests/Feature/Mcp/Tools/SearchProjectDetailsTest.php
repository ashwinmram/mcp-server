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

test('returns only project details for bound project', function () {
    Lesson::factory()->create([
        'source_project' => 'test-project',
        'is_generic' => false,
        'content' => 'Auth is in app/Http/Controllers/Auth',
        'category' => 'project-implementation',
    ]);
    Lesson::factory()->create([
        'source_project' => 'other-project',
        'is_generic' => false,
        'content' => 'Different project details',
    ]);

    $tool = new \App\Mcp\Tools\SearchProjectDetails;
    $response = $tool->handle(new Request([]));
    $data = getResponseData($response);

    expect($data['project'])->toBe('test-project')
        ->and($data['count'])->toBe(1)
        ->and($data['results'][0]['content'])->toContain('Auth is in app');
});

test('excludes generic lessons', function () {
    Lesson::factory()->create([
        'source_project' => 'test-project',
        'is_generic' => true,
        'content' => 'Generic lesson',
    ]);
    Lesson::factory()->create([
        'source_project' => 'test-project',
        'is_generic' => false,
        'content' => 'Project detail',
    ]);

    $tool = new \App\Mcp\Tools\SearchProjectDetails;
    $response = $tool->handle(new Request([]));
    $data = getResponseData($response);

    expect($data['count'])->toBe(1)
        ->and($data['results'][0]['content'])->toBe('Project detail');
});

test('filters by category', function () {
    Lesson::factory()->create([
        'source_project' => 'test-project',
        'is_generic' => false,
        'category' => 'auth',
    ]);
    Lesson::factory()->create([
        'source_project' => 'test-project',
        'is_generic' => false,
        'category' => 'routing',
    ]);

    $tool = new \App\Mcp\Tools\SearchProjectDetails;
    $response = $tool->handle(new Request(['category' => 'auth']));
    $data = getResponseData($response);

    expect($data['count'])->toBe(1)
        ->and($data['results'][0]['category'])->toBe('auth');
});

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
    app()->instance('mcp.project', 'overview-project');
});

test('provides compact overview of project details', function () {
    Lesson::factory()->count(2)->create([
        'source_project' => 'overview-project',
        'is_generic' => false,
        'category' => 'auth',
    ]);
    Lesson::factory()->create([
        'source_project' => 'overview-project',
        'is_generic' => false,
        'category' => 'routing',
    ]);

    $prompt = new \App\Mcp\Prompts\ProjectDetailsOverview;
    $response = $prompt->handle(new Request([]));
    $content = getResponseText($response);

    expect($content)->toContain('Project Details Overview')
        ->and($content)->toContain('overview-project')
        ->and($content)->toContain('Total project details: 3')
        ->and($content)->toContain('By Category')
        ->and($content)->toContain('auth')
        ->and($content)->toContain('routing')
        ->and($content)->toContain('SearchProjectDetails');
});

test('excludes generic lessons and other projects', function () {
    Lesson::factory()->create([
        'source_project' => 'overview-project',
        'is_generic' => false,
        'category' => 'auth',
    ]);
    Lesson::factory()->create([
        'source_project' => 'overview-project',
        'is_generic' => true,
        'category' => 'other',
    ]);

    $prompt = new \App\Mcp\Prompts\ProjectDetailsOverview;
    $response = $prompt->handle(new Request([]));
    $content = getResponseText($response);

    expect($content)->toContain('Total project details: 1')
        ->and($content)->not->toContain('other');
});

test('includes recent entries', function () {
    Lesson::factory()->create([
        'source_project' => 'overview-project',
        'is_generic' => false,
        'title' => 'API routes',
        'type' => 'project_detail',
        'category' => 'routing',
    ]);

    $prompt = new \App\Mcp\Prompts\ProjectDetailsOverview;
    $response = $prompt->handle(new Request([]));
    $content = getResponseText($response);

    expect($content)->toContain('Recent Entries')
        ->and($content)->toContain('API routes')
        ->and($content)->toContain('routing');
});

test('handles empty project details', function () {
    $prompt = new \App\Mcp\Prompts\ProjectDetailsOverview;
    $response = $prompt->handle(new Request([]));
    $content = getResponseText($response);

    expect($content)->toContain('Project Details Overview')
        ->and($content)->toContain('Total project details: 0');
});

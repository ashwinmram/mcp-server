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
    app()->instance('mcp.project', 'disambiguation-project');
});

test('returns guidance on when to use Project Details vs Lessons Learned', function () {
    $prompt = new \App\Mcp\Prompts\WhenToUseProjectDetails;
    $response = $prompt->handle(new Request([]));
    $content = getResponseText($response);

    expect($content)->toContain('When to Use Project Details vs Lessons Learned')
        ->and($content)->toContain('disambiguation-project')
        ->and($content)->toContain('Use Project Details')
        ->and($content)->toContain('How is X done in this project')
        ->and($content)->toContain('Where is Y in this codebase')
        ->and($content)->toContain('Use Lessons Learned')
        ->and($content)->toContain('SearchProjectDetails')
        ->and($content)->toContain('GetProjectDetailsByCategory')
        ->and($content)->toContain('GetProjectDetailsOverview');
});

test('includes count of project details for current project', function () {
    Lesson::factory()->count(3)->create([
        'source_project' => 'disambiguation-project',
        'is_generic' => false,
        'category' => 'auth',
    ]);

    $expectedCount = Lesson::query()
        ->projectDetails()
        ->bySourceProject('disambiguation-project')
        ->active()
        ->count();
    expect($expectedCount)->toBe(3);

    $prompt = new \App\Mcp\Prompts\WhenToUseProjectDetails;
    $response = $prompt->handle(new Request(['project' => 'disambiguation-project']));
    $content = getResponseText($response);

    expect($content)->toContain("**{$expectedCount}** project detail(s) available");
});

test('excludes generic and other projects from count', function () {
    Lesson::factory()->create([
        'source_project' => 'disambiguation-project',
        'is_generic' => false,
        'category' => 'auth',
    ]);
    Lesson::factory()->create([
        'source_project' => 'disambiguation-project',
        'is_generic' => true,
    ]);
    Lesson::factory()->create([
        'source_project' => 'other-project',
        'is_generic' => false,
    ]);

    $expectedCount = Lesson::query()
        ->projectDetails()
        ->bySourceProject('disambiguation-project')
        ->active()
        ->count();
    expect($expectedCount)->toBe(1);

    $prompt = new \App\Mcp\Prompts\WhenToUseProjectDetails;
    $response = $prompt->handle(new Request(['project' => 'disambiguation-project']));
    $content = getResponseText($response);

    expect($content)->toContain("**{$expectedCount}** project detail(s) available");
});

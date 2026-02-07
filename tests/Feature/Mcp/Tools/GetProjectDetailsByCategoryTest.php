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
    app()->instance('mcp.project', 'my-app');
});

test('gets project details by category for bound project', function () {
    Lesson::factory()->count(2)->create([
        'source_project' => 'my-app',
        'is_generic' => false,
        'category' => 'auth',
    ]);
    Lesson::factory()->create([
        'source_project' => 'my-app',
        'is_generic' => false,
        'category' => 'routing',
    ]);

    $tool = new \App\Mcp\Tools\GetProjectDetailsByCategory;
    $response = $tool->handle(new Request(['category' => 'auth']));
    $data = getResponseData($response);

    expect($data['project'])->toBe('my-app')
        ->and($data['category'])->toBe('auth')
        ->and($data['count'])->toBe(2)
        ->and($data['lessons'])->toHaveCount(2);
});

test('returns error when category is missing', function () {
    $tool = new \App\Mcp\Tools\GetProjectDetailsByCategory;
    $response = $tool->handle(new Request([]));
    $content = getResponseText($response);

    expect($content)->toContain('Category is required');
});

test('returns only details for bound project', function () {
    Lesson::factory()->create([
        'source_project' => 'my-app',
        'is_generic' => false,
        'category' => 'auth',
    ]);
    Lesson::factory()->create([
        'source_project' => 'other-app',
        'is_generic' => false,
        'category' => 'auth',
    ]);

    $tool = new \App\Mcp\Tools\GetProjectDetailsByCategory;
    $response = $tool->handle(new Request(['category' => 'auth']));
    $data = getResponseData($response);

    expect($data['count'])->toBe(1)
        ->and($data['lessons'][0]['source_project'])->toBe('my-app');
});

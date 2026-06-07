<?php

use App\Mcp\Tools\GetProjectDetailsCategoryStatistics;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
    app()->instance('mcp.project', 'stats-project');
});

test('returns stats for all categories', function () {
    Lesson::factory()->count(2)->create([
        'source_project' => 'stats-project',
        'is_generic' => false,
        'category' => 'auth',
    ]);
    Lesson::factory()->create([
        'source_project' => 'stats-project',
        'is_generic' => false,
        'category' => 'routing',
    ]);

    $tool = new GetProjectDetailsCategoryStatistics;
    $data = getResponseData($tool->handle(new Request([])));

    expect($data['project'])->toBe('stats-project')
        ->and($data['total_categories'])->toBe(2);
});

test('returns stats for specific category', function () {
    Lesson::factory()->count(3)->create([
        'source_project' => 'stats-project',
        'is_generic' => false,
        'category' => 'auth',
    ]);

    $tool = new GetProjectDetailsCategoryStatistics;
    $data = getResponseData($tool->handle(new Request(['category' => 'auth'])));

    expect($data['total_details'])->toBe(3)
        ->and($data['category'])->toBe('auth');
});

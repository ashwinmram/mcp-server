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

test('returns overview with total and by category', function () {
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

    $tool = new \App\Mcp\Tools\GetProjectDetailsOverview;
    $response = $tool->handle(new Request([]));
    $data = getResponseData($response);

    expect($data['project'])->toBe('overview-project')
        ->and($data['total_entries'])->toBe(3)
        ->and($data['by_category'])->toHaveKey('auth')
        ->and($data['by_category'])->toHaveKey('routing')
        ->and($data['by_category']['auth'])->toBe(2)
        ->and($data['by_category']['routing'])->toBe(1);
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
        'category' => 'coding',
    ]);
    Lesson::factory()->create([
        'source_project' => 'other-project',
        'is_generic' => false,
        'category' => 'auth',
    ]);

    $tool = new \App\Mcp\Tools\GetProjectDetailsOverview;
    $response = $tool->handle(new Request([]));
    $data = getResponseData($response);

    expect($data['total_entries'])->toBe(1)
        ->and($data['by_category']['auth'])->toBe(1);
});

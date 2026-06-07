<?php

use App\Mcp\Tools\GetLatestCaptureSummary;
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

test('returns latest generic on lessons server without project', function () {
    Lesson::factory()->create([
        'is_generic' => true,
        'created_at' => now()->subDay(),
    ]);
    $latest = Lesson::factory()->create([
        'is_generic' => true,
        'created_at' => now(),
    ]);

    $tool = new GetLatestCaptureSummary;
    $data = getResponseData($tool->handle(new Request([])));

    expect($data['latest_generic']['id'])->toBe($latest->id)
        ->and($data['latest_project_detail'])->toBeNull();
});

test('returns both when project context is bound', function () {
    app()->instance('mcp.project', 'capture-project');

    $now = now();
    $generic = Lesson::factory()->create([
        'is_generic' => true,
        'created_at' => $now,
    ]);
    $detail = Lesson::factory()->create([
        'source_project' => 'capture-project',
        'is_generic' => false,
        'created_at' => $now,
    ]);

    $tool = new GetLatestCaptureSummary;
    $data = getResponseData($tool->handle(new Request([])));

    expect($data['latest_generic']['id'])->toBe($generic->id)
        ->and($data['latest_project_detail']['id'])->toBe($detail->id)
        ->and($data['captured_together'])->toBeTrue()
        ->and($data['source_project'])->toBe('capture-project');
});

test('uses source_project param on lessons server', function () {
    Lesson::factory()->create(['is_generic' => true]);
    $detail = Lesson::factory()->create([
        'source_project' => 'param-project',
        'is_generic' => false,
    ]);

    $tool = new GetLatestCaptureSummary;
    $data = getResponseData($tool->handle(new Request(['source_project' => 'param-project'])));

    expect($data['latest_project_detail']['id'])->toBe($detail->id)
        ->and($data['source_project'])->toBe('param-project');
});

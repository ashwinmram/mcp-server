<?php

use App\Mcp\Tools\GetRecentProjectDetails;
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

test('returns project details ordered by updated_at', function () {
    $older = Lesson::factory()->create([
        'source_project' => 'test-project',
        'is_generic' => false,
        'updated_at' => now()->subDays(2),
    ]);
    $newer = Lesson::factory()->create([
        'source_project' => 'test-project',
        'is_generic' => false,
        'updated_at' => now(),
    ]);

    $tool = new GetRecentProjectDetails;
    $data = getResponseData($tool->handle(new Request(['limit' => 2])));

    expect($data['project'])->toBe('test-project')
        ->and($data['results'][0]['id'])->toBe($newer->id)
        ->and($data['ordered_by'])->toBe('updated_at');
});

test('excludes other projects', function () {
    Lesson::factory()->create([
        'source_project' => 'test-project',
        'is_generic' => false,
    ]);
    Lesson::factory()->create([
        'source_project' => 'other-project',
        'is_generic' => false,
    ]);

    $tool = new GetRecentProjectDetails;
    $data = getResponseData($tool->handle(new Request([])));

    expect($data['count'])->toBe(1);
});

<?php

use App\Mcp\Resources\ProjectDetailsRecentResource;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
    app()->instance('mcp.project', 'recent-project');
});

test('returns recent project details markdown with ids', function () {
    $detail = Lesson::factory()->create([
        'source_project' => 'recent-project',
        'is_generic' => false,
        'title' => 'Sidebar config',
    ]);

    $resource = new ProjectDetailsRecentResource;
    $content = getResponseText($resource->handle(new Request([])));

    expect($content)->toContain('Recent Project Details')
        ->and($content)->toContain('recent-project')
        ->and($content)->toContain($detail->id)
        ->and($content)->toContain('Sidebar config');
});

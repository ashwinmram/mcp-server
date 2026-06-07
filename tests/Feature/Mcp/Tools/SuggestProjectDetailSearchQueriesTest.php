<?php

use App\Mcp\Tools\SuggestProjectDetailSearchQueries;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
    app()->instance('mcp.project', 'suggest-project');
});

test('suggests queries based on topic', function () {
    Lesson::factory()->create([
        'source_project' => 'suggest-project',
        'is_generic' => false,
        'content' => 'Authentication uses Fortify',
        'category' => 'auth',
        'tags' => ['fortify'],
    ]);

    $tool = new SuggestProjectDetailSearchQueries;
    $data = getResponseData($tool->handle(new Request(['topic' => 'auth'])));

    expect($data['project'])->toBe('suggest-project')
        ->and($data['count'])->toBeGreaterThan(0)
        ->and($data['related_categories'])->toContain('auth');
});

test('requires topic or query', function () {
    $tool = new SuggestProjectDetailSearchQueries;
    $response = $tool->handle(new Request([]));

    expect(getResponseText($response))->toContain('required');
});

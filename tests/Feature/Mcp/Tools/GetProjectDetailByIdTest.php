<?php

use App\Mcp\Tools\GetProjectDetailById;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
    app()->instance('mcp.project', 'my-project');
});

test('returns project detail by id', function () {
    $detail = Lesson::factory()->create([
        'source_project' => 'my-project',
        'is_generic' => false,
        'title' => 'Auth setup',
        'content' => 'Fortify config details',
    ]);

    $tool = new GetProjectDetailById;
    $data = getResponseData($tool->handle(new Request(['lesson_id' => $detail->id])));

    expect($data['project'])->toBe('my-project')
        ->and($data['detail']['id'])->toBe($detail->id)
        ->and($data['detail']['title'])->toBe('Auth setup');
});

test('returns error for detail from another project', function () {
    $detail = Lesson::factory()->create([
        'source_project' => 'other-project',
        'is_generic' => false,
    ]);

    $tool = new GetProjectDetailById;
    $response = $tool->handle(new Request(['lesson_id' => $detail->id]));

    expect(getResponseText($response))->toContain('not found');
});

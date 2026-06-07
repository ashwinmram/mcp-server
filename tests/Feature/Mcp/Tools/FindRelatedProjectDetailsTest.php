<?php

use App\Mcp\Tools\FindRelatedProjectDetails;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
    app()->instance('mcp.project', 'rel-project');
});

test('finds related project details', function () {
    $main = Lesson::factory()->create([
        'source_project' => 'rel-project',
        'is_generic' => false,
    ]);
    $related = Lesson::factory()->create([
        'source_project' => 'rel-project',
        'is_generic' => false,
    ]);

    DB::table('lesson_relationships')->insert([
        'id' => Str::uuid(),
        'lesson_id' => $main->id,
        'related_lesson_id' => $related->id,
        'relationship_type' => 'related',
        'relevance_score' => 0.8,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tool = new FindRelatedProjectDetails;
    $data = getResponseData($tool->handle(new Request(['lesson_id' => $main->id])));

    expect($data['count'])->toBe(1)
        ->and($data['related_details'][0]['id'])->toBe($related->id);
});

test('returns error when detail not in project', function () {
    $detail = Lesson::factory()->create([
        'source_project' => 'other-project',
        'is_generic' => false,
    ]);

    $tool = new FindRelatedProjectDetails;
    $response = $tool->handle(new Request(['lesson_id' => $detail->id]));

    expect(getResponseText($response))->toContain('not found');
});

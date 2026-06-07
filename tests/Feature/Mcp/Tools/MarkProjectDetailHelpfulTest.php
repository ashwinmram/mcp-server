<?php

use App\Mcp\Tools\MarkProjectDetailHelpful;
use App\Models\Lesson;
use App\Models\LessonUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
    app()->instance('mcp.project', 'feedback-project');
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_01_18_095617_create_lesson_usages_table.php'])->assertSuccessful();
});

test('marks project detail as helpful', function () {
    $detail = Lesson::factory()->create([
        'source_project' => 'feedback-project',
        'is_generic' => false,
    ]);

    $tool = new MarkProjectDetailHelpful;
    $data = getResponseData($tool->handle(new Request([
        'lesson_id' => $detail->id,
        'was_helpful' => true,
    ])));

    expect($data['success'])->toBeTrue()
        ->and($data['project'])->toBe('feedback-project');

    $usage = LessonUsage::where('lesson_id', $detail->id)->first();
    expect($usage)->not->toBeNull()
        ->and($usage->was_helpful)->toBeTrue();
});

test('returns error for detail from another project', function () {
    $detail = Lesson::factory()->create([
        'source_project' => 'other-project',
        'is_generic' => false,
    ]);

    $tool = new MarkProjectDetailHelpful;
    $response = $tool->handle(new Request(['lesson_id' => $detail->id]));

    expect(getResponseText($response))->toContain('not found');
});

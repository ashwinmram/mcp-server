<?php

use Illuminate\Support\Facades\Artisan;

test('service provider registers mcp knowledge commands', function () {
    $commands = Artisan::all();

    expect(array_key_exists('mcp:append', $commands))->toBeTrue()
        ->and(array_key_exists('mcp:push', $commands))->toBeTrue()
        ->and(array_key_exists('mcp:extract-session', $commands))->toBeTrue()
        ->and(array_key_exists('mcp:install-cursor-rules', $commands))->toBeTrue()
        ->and(array_key_exists('mcp:push-lessons', $commands))->toBeFalse()
        ->and(array_key_exists('mcp:push-project-details', $commands))->toBeFalse();
});

test('service provider merges mcp-pusher config paths', function () {
    expect(config('mcp-pusher.lessons_learned_path'))->toBe(base_path('docs/lessons-learned.md'))
        ->and(config('mcp-pusher.lessons_learned_json_path'))->toBe(base_path('docs/lessons_learned.json'))
        ->and(config('mcp-pusher.generic_draft_jsonl'))->toBe(base_path('docs/.mcp-session/lessons-draft.jsonl'))
        ->and(config('mcp-pusher.project_draft_jsonl'))->toBe(base_path('docs/.mcp-session/project-details-draft.jsonl'));
});

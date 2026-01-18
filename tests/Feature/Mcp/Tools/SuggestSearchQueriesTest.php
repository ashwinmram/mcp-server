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
});

test('suggests related queries for a topic', function () {
    Lesson::factory()->create([
        'content' => 'Always use form request classes for validation',
        'title' => 'Form Request Validation',
        'category' => 'validation',
        'tags' => ['laravel', 'validation', 'form-request'],
        'is_generic' => true,
    ]);

    Lesson::factory()->create([
        'content' => 'Use validation rules in Laravel',
        'title' => 'Validation Rules',
        'category' => 'validation',
        'tags' => ['laravel', 'validation'],
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\SuggestSearchQueries;
    $request = new Request(['topic' => 'validation']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\Response::class)
        ->and($data)->toHaveKey('suggested_queries')
        ->and($data)->toHaveKey('related_categories')
        ->and($data)->toHaveKey('related_tags')
        ->and($data['original_topic'])->toBe('validation')
        ->and($data['suggested_queries'])->toBeArray()
        ->and(count($data['suggested_queries']))->toBeGreaterThan(0);
});

test('suggests expanded queries based on topic keywords', function () {
    $tool = new \App\Mcp\Tools\SuggestSearchQueries;
    $request = new Request(['topic' => 'validation']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    $queries = collect($data['suggested_queries'])->pluck('query')->filter()->toArray();

    // Should suggest related terms like "form request", "validation rules"
    expect($queries)->toContain('validation');
    // Should have expanded suggestions
    expect(count($queries))->toBeGreaterThan(1);
});

test('includes category-based suggestions when lessons match', function () {
    Lesson::factory()->create([
        'content' => 'Test validation lesson',
        'category' => 'validation',
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\SuggestSearchQueries;
    $request = new Request(['topic' => 'validation']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    // Should suggest category-based queries
    $categoryQueries = collect($data['suggested_queries'])
        ->where('type', 'category')
        ->values()
        ->toArray();

    expect(count($categoryQueries))->toBeGreaterThan(0)
        ->and($categoryQueries[0])->toHaveKey('category')
        ->and($categoryQueries[0]['category'])->toBe('validation');
});

test('includes tag-based suggestions when lessons match', function () {
    Lesson::factory()->create([
        'content' => 'Test lesson about Laravel validation',
        'tags' => ['laravel', 'validation'],
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\SuggestSearchQueries;
    $request = new Request(['topic' => 'validation']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    // Should suggest tag-based queries
    $tagQueries = collect($data['suggested_queries'])
        ->where('type', 'tag')
        ->values()
        ->toArray();

    expect(count($tagQueries))->toBeGreaterThan(0)
        ->and($tagQueries[0])->toHaveKey('tags')
        ->and($tagQueries[0]['tags'])->toBeArray();
});

test('returns error when topic and query are both missing', function () {
    $tool = new \App\Mcp\Tools\SuggestSearchQueries;
    $request = new Request([]);

    $response = $tool->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Topic or query is required');
});

test('works with query parameter instead of topic', function () {
    Lesson::factory()->create([
        'content' => 'Testing lesson',
        'category' => 'testing',
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\SuggestSearchQueries;
    $request = new Request(['query' => 'testing']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    expect($data['original_topic'])->toBe('testing')
        ->and($data['suggested_queries'])->toBeArray();
});

test('prioritizes topic over query when both provided', function () {
    Lesson::factory()->create([
        'content' => 'Testing lesson',
        'category' => 'testing',
        'is_generic' => true,
    ]);

    $tool = new \App\Mcp\Tools\SuggestSearchQueries;
    $request = new Request(['topic' => 'validation', 'query' => 'testing']);

    $response = $tool->handle($request);
    $data = getResponseData($response);

    // Should use topic, not query
    expect($data['original_topic'])->toBe('validation');
});

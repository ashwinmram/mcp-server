<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
});

test('returns search guide content', function () {
    $resource = new \App\Mcp\Resources\LessonsSearchGuide;
    $request = new Request([]);

    $response = $resource->handle($request);
    $content = getResponseText($response);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\Response::class)
        ->and($content)->toContain('Lessons Learned Search Guide')
        ->and($content)->toContain('Understanding Relevance Scoring')
        ->and($content)->toContain('Search Strategies')
        ->and($content)->toContain('When to Query Lessons');
});

test('includes relevance scoring explanation', function () {
    $resource = new \App\Mcp\Resources\LessonsSearchGuide;
    $request = new Request([]);

    $response = $resource->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Usage Frequency')
        ->and($content)->toContain('Helpfulness Rate')
        ->and($content)->toContain('Recency');
});

test('includes search strategy examples', function () {
    $resource = new \App\Mcp\Resources\LessonsSearchGuide;
    $request = new Request([]);

    $response = $resource->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Keyword Search')
        ->and($content)->toContain('Category Browse')
        ->and($content)->toContain('Tag Filtering')
        ->and($content)->toContain('Related Lessons');
});

test('includes query examples', function () {
    $resource = new \App\Mcp\Resources\LessonsSearchGuide;
    $request = new Request([]);

    $response = $resource->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Example Queries')
        ->and($content)->toContain('Pest mocking')
        ->and($content)->toContain('Inertia forms');
});

test('includes best practices section', function () {
    $resource = new \App\Mcp\Resources\LessonsSearchGuide;
    $request = new Request([]);

    $response = $resource->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Best Practices')
        ->and($content)->toContain('Use specific keywords')
        ->and($content)->toContain('Provide feedback');
});

test('explains automatic usage tracking', function () {
    $resource = new \App\Mcp\Resources\LessonsSearchGuide;
    $request = new Request([]);

    $response = $resource->handle($request);
    $content = getResponseText($response);

    expect($content)->toContain('Automatic Usage Tracking')
        ->and($content)->toContain('automatically tracked');
});

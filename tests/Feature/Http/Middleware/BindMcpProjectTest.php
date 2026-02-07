<?php

use App\Http\Middleware\BindMcpProject;
use Illuminate\Http\Request;

test('returns 422 when project query param is missing', function () {
    $request = Request::create('/mcp/project-details', 'GET');
    $middleware = new BindMcpProject;

    $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(422);
    $data = json_decode($response->getContent(), true);
    expect($data)->toHaveKey('error')
        ->and($data['error'])->toContain('project');
});

test('returns 422 when project query param is empty string', function () {
    $request = Request::create('/mcp/project-details?project=', 'GET');
    $middleware = new BindMcpProject;

    $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(422);
});

test('returns 422 when project contains invalid characters', function () {
    $request = Request::create('/mcp/project-details?project=invalid!!', 'GET');
    $middleware = new BindMcpProject;

    $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(422);
    $data = json_decode($response->getContent(), true);
    expect($data['error'])->toContain('Invalid project');
});

test('passes through and binds project when valid', function () {
    $request = Request::create('/mcp/project-details?project=my-app', 'GET');
    $middleware = new BindMcpProject;

    $response = $middleware->handle($request, function () {
        expect(app('mcp.project'))->toBe('my-app');

        return response()->json(['ok' => true]);
    });

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true)['ok'])->toBeTrue();
});

test('accepts project with hyphens and underscores', function () {
    $request = Request::create('/mcp/project-details?project=my_project-app', 'GET');
    $middleware = new BindMcpProject;

    $response = $middleware->handle($request, function () {
        expect(app('mcp.project'))->toBe('my_project-app');

        return response()->json(['ok' => true]);
    });

    expect($response->getStatusCode())->toBe(200);
});

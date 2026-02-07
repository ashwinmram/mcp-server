<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BindMcpProject
{
    /**
     * Allowed project identifier pattern (alphanumeric, hyphens, underscores).
     */
    private const PROJECT_PATTERN = '/^[a-zA-Z0-9_-]{1,255}$/';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->query('project');

        if ($project === null || $project === '') {
            return response()->json([
                'error' => 'Missing required query parameter: project',
            ], 422);
        }

        if (! is_string($project) || ! preg_match(self::PROJECT_PATTERN, $project)) {
            return response()->json([
                'error' => 'Invalid project identifier. Use alphanumeric characters, hyphens, or underscores (max 255).',
            ], 422);
        }

        app()->instance('mcp.project', $project);

        return $next($request);
    }
}

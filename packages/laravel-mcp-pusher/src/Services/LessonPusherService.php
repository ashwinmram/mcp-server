<?php

namespace LaravelMcpPusher\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class LessonPusherService
{
    /**
     * Push lessons to the MCP server.
     */
    public function pushLessons(array $lessons, string $sourceProject): Response
    {
        // Use package config with fallback to services.mcp config
        $serverUrl = config('mcp-pusher.server_url') ?? config('services.mcp.server_url');
        $apiToken = config('mcp-pusher.api_token') ?? config('services.mcp.api_token');

        if (empty($serverUrl) || empty($apiToken)) {
            throw new \RuntimeException('MCP server URL and API token must be configured');
        }

        $url = rtrim($serverUrl, '/').'/api/lessons';

        return Http::withToken($apiToken)
            ->acceptJson()
            ->post($url, [
                'source_project' => $sourceProject,
                'lessons' => $lessons,
            ]);
    }
}

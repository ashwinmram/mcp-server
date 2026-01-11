<?php

namespace LaravelMcpPusher\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Service for pushing lessons to a remote MCP server via HTTP API.
 *
 * Note: This service pushes lessons to a remote MCP server endpoint,
 * not to a local database. The remote server handles all database
 * operations, validation, and Lesson model management.
 */
class LessonPusherService
{
    /**
     * Push lessons to the remote MCP server via HTTP API.
     *
     * This method sends lessons to the remote MCP server's /api/lessons endpoint.
     * The remote server handles database storage, deduplication, and validation.
     *
     * @param  array  $lessons  Array of normalized lesson data (with categories/tags)
     * @param  string  $sourceProject  Source project identifier
     * @return Response HTTP response from the remote MCP server
     *
     * @throws \RuntimeException If server URL or API token is not configured
     */
    public function pushLessons(array $lessons, string $sourceProject): Response
    {
        // Use package config with fallback to services.mcp config
        $serverUrl = config('mcp-pusher.server_url') ?? config('services.mcp.server_url');
        $apiToken = config('mcp-pusher.api_token') ?? config('services.mcp.api_token');

        if (empty($serverUrl) || empty($apiToken)) {
            throw new \RuntimeException('MCP server URL and API token must be configured');
        }

        // Push to remote MCP server endpoint (not local database)
        $url = rtrim($serverUrl, '/').'/api/lessons';

        return Http::withToken($apiToken)
            ->acceptJson()
            ->post($url, [
                'source_project' => $sourceProject,
                'lessons' => $lessons,
            ]);
    }
}

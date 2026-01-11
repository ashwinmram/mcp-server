<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Server URL
    |--------------------------------------------------------------------------
    |
    | The URL of your MCP server where lessons will be pushed.
    |
    */
    'server_url' => env('MCP_SERVER_URL'),

    /*
    |--------------------------------------------------------------------------
    | API Token
    |--------------------------------------------------------------------------
    |
    | The API token for authenticating with the MCP server.
    | This will fallback to services.mcp.api_token in the service if not set.
    |
    */
    'api_token' => env('MCP_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | File Paths
    |--------------------------------------------------------------------------
    |
    | Paths to the files that will be read for lessons.
    |
    */
    'lessons_learned_path' => base_path('lessons-learned.md'),
    'ai_json_directory' => base_path('docs'),
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Server URL
    |--------------------------------------------------------------------------
    */
    'server_url' => env('MCP_SERVER_URL'),

    /*
    |--------------------------------------------------------------------------
    | API Token
    |--------------------------------------------------------------------------
    */
    'api_token' => env('MCP_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Legacy file paths (generic lessons)
    |--------------------------------------------------------------------------
    */
    'lessons_learned_path' => base_path('docs/lessons-learned.md'),
    'lessons_learned_json_path' => base_path('docs/lessons_learned.json'),

    /*
    |--------------------------------------------------------------------------
    | Legacy file paths (project details)
    |--------------------------------------------------------------------------
    */
    'project_details_path' => base_path('docs/project-details.md'),
    'project_details_json_dir' => base_path('docs'),

    /*
    |--------------------------------------------------------------------------
    | Session draft paths (append-only JSONL, survive compaction)
    |--------------------------------------------------------------------------
    */
    'session_dir' => base_path('docs/.mcp-session'),
    'generic_draft_jsonl' => base_path('docs/.mcp-session/lessons-draft.jsonl'),
    'project_draft_jsonl' => base_path('docs/.mcp-session/project-details-draft.jsonl'),
];

<?php

use App\Mcp\Servers\LessonsServer;
use App\Mcp\Servers\ProjectDetailsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/lessons', LessonsServer::class)
    ->middleware('auth:sanctum');

Mcp::web('/mcp/project-details', ProjectDetailsServer::class)
    ->middleware(['auth:sanctum', 'mcp.project']);

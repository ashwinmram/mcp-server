<?php

use App\Mcp\Servers\LessonsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/lessons', LessonsServer::class)
    ->middleware('auth:sanctum');

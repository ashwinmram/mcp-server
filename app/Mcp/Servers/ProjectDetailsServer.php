<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetProjectDetailsByCategory;
use App\Mcp\Tools\GetProjectDetailsOverview;
use App\Mcp\Tools\SearchProjectDetails;
use Laravel\Mcp\Server;

class ProjectDetailsServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Project Details MCP Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        This server provides access to **project-specific** implementation details for the current project. Use it when working in a codebase that has pushed project details (file locations, env vars, conventions, APIs).

        **Connection:** The project is determined by the URL query parameter `project` (e.g. `/mcp/project-details?project=my-app`). The same value must be used as `--source` when pushing project details.

        **When to use:**
        - "How is X done in this project?" / "Where is Y in this codebase?"
        - Before changing project-specific paths, config, or conventions
        - When you need implementation details that are not generic best practices

        **Tools:**
        - SearchProjectDetails - Search by keyword, category, or tags within this project's details
        - GetProjectDetailsByCategory - List all details in a category for this project
        - GetProjectDetailsOverview - Get counts by category for this project (useful at session start)

        All results are scoped to the project specified in the connection URL.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        SearchProjectDetails::class,
        GetProjectDetailsByCategory::class,
        GetProjectDetailsOverview::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [];
}

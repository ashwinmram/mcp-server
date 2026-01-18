<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\LessonsByCategory;
use App\Mcp\Prompts\LessonsLearnedOverview;
use App\Mcp\Resources\LessonsOverviewResource;
use App\Mcp\Tools\FindRelatedLessons;
use App\Mcp\Tools\GetLessonByCategory;
use App\Mcp\Tools\GetLessonTags;
use App\Mcp\Tools\SearchLessons;
use Laravel\Mcp\Server;

class LessonsServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Lessons Learned MCP Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '1.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        This server provides access to lessons learned from previous coding sessions.
        Lessons are generic best practices, patterns, and knowledge extracted from various projects.

        **IMPORTANT:** At the start of each AI agent session, you should:
        1. Read the `lessons://overview` resource to get an overview of available lessons
        2. Use the LessonsLearnedOverview prompt to understand what knowledge is available
        3. Query relevant lessons based on the current coding task using SearchLessons or GetLessonByCategory

        Use the available tools to:
        - SearchLessons - Search for lessons by keyword, category, or tags (uses MySQL FULLTEXT search)
        - GetLessonByCategory - Browse lessons by category
        - GetLessonTags - Discover available tags
        - FindRelatedLessons - Find lessons related to a specific lesson

        Use the available resources to:
        - lessons://overview - Read overview of all lessons (automatically available as context)

        Use the prompts to:
        - LessonsLearnedOverview - Get an updated overview of available lessons
        - LessonsByCategory - See lessons grouped by a specific category

        All lessons are validated to be generic and reusable across projects, avoiding project-specific implementation details.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        SearchLessons::class,
        GetLessonByCategory::class,
        GetLessonTags::class,
        FindRelatedLessons::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        LessonsOverviewResource::class,
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        LessonsLearnedOverview::class,
        LessonsByCategory::class,
    ];
}

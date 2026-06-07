<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\LessonsByCategory;
use App\Mcp\Prompts\LessonsLearnedOverview;
use App\Mcp\Resources\LessonsOverviewResource;
use App\Mcp\Resources\LessonsRecentResource;
use App\Mcp\Resources\LessonsSearchGuide;
use App\Mcp\Tools\FindRelatedLessons;
use App\Mcp\Tools\GetCategoryStatistics;
use App\Mcp\Tools\GetLatestCaptureSummary;
use App\Mcp\Tools\GetLessonByCategory;
use App\Mcp\Tools\GetLessonById;
use App\Mcp\Tools\GetLessonTags;
use App\Mcp\Tools\GetRecentLessons;
use App\Mcp\Tools\GetTopLessons;
use App\Mcp\Tools\MarkLessonHelpful;
use App\Mcp\Tools\SearchLessons;
use App\Mcp\Tools\SuggestSearchQueries;
use Laravel\Mcp\Server;

class LessonsServer extends Server
{
    protected string $name = 'Lessons Learned MCP Server';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'MARKDOWN'
        This server provides access to lessons learned from previous coding sessions.
        Lessons are generic best practices, patterns, and knowledge extracted from various projects.

        **IMPORTANT:** At the start of each AI agent session, you should:
        1. Read the `lessons://overview` resource (optional: `lessons://search-guide`, `lessons://recent`)
        2. Use the LessonsLearnedOverview prompt to understand what knowledge is available
        3. Query relevant lessons based on the current coding task

        **For "latest" or chronological queries**, use GetRecentLessons or GetLatestCaptureSummary — not GetTopLessons (most relevant) or SearchLessons without a query (relevance-ranked browse).

        Use the available tools to:
        - SearchLessons - Search by keyword, category, or tags (default sort: relevance)
        - GetRecentLessons - Latest generic lessons in chronological order
        - GetLessonById - Fetch a single lesson by UUID
        - GetLatestCaptureSummary - Latest generic lesson (+ project detail when source_project provided)
        - GetLessonByCategory - Browse lessons by category
        - GetLessonTags - Discover available tags
        - FindRelatedLessons - Find lessons related to a specific lesson
        - MarkLessonHelpful - Mark a lesson as helpful or not helpful
        - SuggestSearchQueries - Expand search terms to discover related lessons
        - GetTopLessons - Most relevant lessons by score (NOT most recent)
        - GetCategoryStatistics - Category stats, usage, and top lessons

        Use the available resources to:
        - lessons://overview - Summary of categories, tags, and recent lessons
        - lessons://recent - Chronological recent lessons with ids
        - lessons://search-guide - How to search effectively (relevance vs recency)

        Use the prompts to:
        - LessonsLearnedOverview - Updated overview of available lessons
        - LessonsByCategory - Lessons grouped by a specific category

        All lessons are validated to be generic and reusable across projects.
    MARKDOWN;

    protected array $tools = [
        SearchLessons::class,
        GetRecentLessons::class,
        GetLessonById::class,
        GetLatestCaptureSummary::class,
        GetLessonByCategory::class,
        GetLessonTags::class,
        FindRelatedLessons::class,
        MarkLessonHelpful::class,
        SuggestSearchQueries::class,
        GetTopLessons::class,
        GetCategoryStatistics::class,
    ];

    protected array $resources = [
        LessonsOverviewResource::class,
        LessonsRecentResource::class,
        LessonsSearchGuide::class,
    ];

    protected array $prompts = [
        LessonsLearnedOverview::class,
        LessonsByCategory::class,
    ];
}

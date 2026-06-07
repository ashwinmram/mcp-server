<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\ProjectDetailsByCategory;
use App\Mcp\Prompts\ProjectDetailsOverview;
use App\Mcp\Prompts\WhenToUseProjectDetails;
use App\Mcp\Resources\ProjectDetailsOverviewResource;
use App\Mcp\Resources\ProjectDetailsRecentResource;
use App\Mcp\Tools\FindRelatedProjectDetails;
use App\Mcp\Tools\GetLatestCaptureSummary;
use App\Mcp\Tools\GetProjectDetailById;
use App\Mcp\Tools\GetProjectDetailsByCategory;
use App\Mcp\Tools\GetProjectDetailsCategoryStatistics;
use App\Mcp\Tools\GetProjectDetailsOverview;
use App\Mcp\Tools\GetRecentProjectDetails;
use App\Mcp\Tools\MarkProjectDetailHelpful;
use App\Mcp\Tools\SearchProjectDetails;
use App\Mcp\Tools\SuggestProjectDetailSearchQueries;
use Laravel\Mcp\Server;

class ProjectDetailsServer extends Server
{
    protected string $name = 'Project Details MCP Server';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'MARKDOWN'
        This server provides access to **project-specific** implementation details for the current project.

        **Connection:** The project is determined by the URL query parameter `project` (e.g. `/mcp/project-details?project=my-app`). The same value must be used as `--source` when pushing project details.

        **For "latest" queries**, use GetRecentProjectDetails or GetLatestCaptureSummary — not SearchProjectDetails without a query.

        **Resources:**
        - project-details://overview - Categories, tags, recent details
        - project-details://recent - Chronological recent details with ids

        **Tools:**
        - SearchProjectDetails - Search by keyword, category, or tags
        - GetRecentProjectDetails - Latest project details in chronological order
        - GetProjectDetailById - Fetch a single detail by UUID
        - GetLatestCaptureSummary - Latest generic + latest project detail for this project
        - GetProjectDetailsByCategory - List details in a category
        - GetProjectDetailsOverview - Counts by category and recent entries (JSON)
        - FindRelatedProjectDetails - Related details for a given entry
        - MarkProjectDetailHelpful - Feedback for relevance scoring
        - SuggestProjectDetailSearchQueries - Expand search terms within this project
        - GetProjectDetailsCategoryStatistics - Category stats for this project

        **Prompts:**
        - ProjectDetailsOverview - Quick summary
        - ProjectDetailsByCategory - Details for a specific category
        - WhenToUseProjectDetails - Project Details vs Lessons Learned

        All results are scoped to the project specified in the connection URL.
    MARKDOWN;

    protected array $tools = [
        SearchProjectDetails::class,
        GetRecentProjectDetails::class,
        GetProjectDetailById::class,
        GetLatestCaptureSummary::class,
        GetProjectDetailsByCategory::class,
        GetProjectDetailsOverview::class,
        FindRelatedProjectDetails::class,
        MarkProjectDetailHelpful::class,
        SuggestProjectDetailSearchQueries::class,
        GetProjectDetailsCategoryStatistics::class,
    ];

    protected array $resources = [
        ProjectDetailsOverviewResource::class,
        ProjectDetailsRecentResource::class,
    ];

    protected array $prompts = [
        ProjectDetailsOverview::class,
        ProjectDetailsByCategory::class,
        WhenToUseProjectDetails::class,
    ];
}

---
name: mcp-session-startup
description: Query Lessons Learned and Project Details MCP servers at session start. Use when beginning work on a Laravel project with ashwinmram/mcp-pusher connected.
---

# MCP session startup

At the start of each new agent session:

1. Read **`lessons://overview`** from the Lessons Learned MCP server (optional: `lessons://search-guide`, `lessons://recent`).
2. Use the **LessonsLearnedOverview** prompt when available.
3. **Query relevant lessons** for the current task: SearchLessons, GetLessonByCategory, GetTopLessons, SuggestSearchQueries.
4. For **project-specific** paths, env, or conventions: use the Project Details MCP server (`/mcp/project-details?project=<source>`). The `project` param must match `php artisan mcp:push --source=…`. Call **GetProjectDetailsOverview** when connected (optional: `project-details://recent`).
5. **Apply lessons** to coding decisions; query again when new topics arise.
6. Call **MarkLessonHelpful** when a lesson clearly helped or hurt (feeds relevance scoring).

**For "latest" queries:** use **GetRecentLessons** / **GetRecentProjectDetails** or **GetLatestCaptureSummary** — not **GetTopLessons** or **SearchLessons** without a query.

During development, query before new features, errors, refactors, and architecture decisions. SearchLessons automatically tracks usage; deprecated lessons are filtered unless `include_deprecated=true`.

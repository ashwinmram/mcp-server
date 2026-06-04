# MCP session startup instructions

Copy this content into your AI client's project instructions (see package README for where to place it per IDE).

## Leverage the MCP servers

**At the start of each new AI agent session:**

1. **Read the `lessons://overview` resource** from the Lessons Learned MCP server
   - Provides an overview of lessons, categories, tags, and recent lessons
   - **Optional:** Read `lessons://search-guide` for search strategies and query examples

2. **Use the LessonsLearnedOverview prompt** for an updated overview of available lessons

3. **Query relevant lessons** for the current task using:
   - **SearchLessons** — keyword, category, or tags (FULLTEXT + relevance scoring)
   - **GetLessonByCategory** — browse by category
   - **GetLessonTags** — discover tags
   - **FindRelatedLessons** — related lessons by topic
   - **SuggestSearchQueries** — expand search terms
   - **GetTopLessons** — highest relevance lessons (optionally by category)
   - **GetCategoryStatistics** — category usage and value stats

4. **Apply lessons** to coding decisions; query again when topics arise

5. **Keep lesson summaries succinct** (bullet lists when discussing lessons)

## Project implementation details

For **project-specific** details (paths, env, conventions for the current repo):

- Use the **Project Details** MCP server (`/mcp/project-details?project=<source>`)
- The `project` query param must match `--source` when running `php artisan mcp:push`
- Tools: **SearchProjectDetails**, **GetProjectDetailsByCategory**, **GetProjectDetailsOverview**
- At session start, call **GetProjectDetailsOverview** when that server is connected

Do not use a Project Details server scoped to a different project than the codebase you are editing.

## During development

**When to query lessons:** new features, errors, refactors, architecture decisions, best-practice questions.

**How to query:** SearchLessons (set `include_related=true` when helpful), GetLessonByCategory, FindRelatedLessons, SuggestSearchQueries, GetTopLessons, GetCategoryStatistics, MarkLessonHelpful for feedback.

**Usage tracking:** Retrieving lessons via SearchLessons improves future relevance ranking automatically.

**Search tips:** specific technical terms; combine category and query; use tags; deprecated lessons are filtered unless `include_deprecated=true`.

## Capturing session knowledge (mcp-pusher 3.0)

- Use **`php artisan mcp:append`** during the session (not hand-typed JSON in chat only — run the command)
- Paste the **knowledge capture prompt** from the mcp-pusher README before context compaction or anytime you want to capture learnings
- End of session: review `docs/.mcp-session/*-draft.jsonl`, then **`php artisan mcp:push --source=<project>`** once
- Use **`mcp:extract-session`** only if drafts are thin after compaction

<mcp-server-instructions>
# Lessons Learned MCP Server - CRITICAL SESSION STARTUP INSTRUCTIONS

## Leverage the MCP servers

**CRITICAL: At the start of each new AI agent session, you MUST:**

1. **Read the `lessons://overview` resource** from the Lessons Learned MCP Server
   - This resource is automatically available via the MCP server
   - Use `fetch_mcp_resource` with server `user-lessons-learned-local` and URI `lessons://overview`
   - This provides an overview of all available lessons, categories, tags, and recent lessons
   - **Optional:** Read `lessons://search-guide` resource for comprehensive search strategies and query examples

2. **Use the LessonsLearnedOverview prompt** to understand what knowledge is available
    - This gives you an updated overview of available lessons

3. **Query relevant lessons** based on the current coding task
   - Use `mcp_lessons-learned-local_search-lessons` to search by keyword, category, or tags (uses MySQL FULLTEXT search + relevance scoring for better results)
   - Use `mcp_lessons-learned-local_get-lesson-by-category` to browse lessons by category
   - Use `mcp_lessons-learned-local_get-lesson-tags` to discover available tags
   - Use `mcp_lessons-learned-local_find-related-lessons` to find lessons related to a specific lesson
   - Use `mcp_lessons-learned-local_suggest-search-queries` when unsure about search terms - expands a topic into multiple related searches
   - Use `mcp_lessons-learned-local_get-top-lessons` to get lessons with highest relevance scores (optionally by category) - surfaces most valuable lessons when you want quality without specific search terms
   - Use `mcp_lessons-learned-local_get-category-statistics` to discover which categories have the most valuable lessons - helps identify best sources of knowledge
   - **Note:** Lessons are automatically ranked by relevance score (usage frequency, helpfulness, recency) - most useful lessons appear first

4. **Apply lessons learned** to guide your coding decisions
    - Reference lessons when making coding decisions
    - Query additional lessons when encountering related topics
    - Apply best practices from lessons to avoid repeating past mistakes

5. **Keep responses succinct** and in ordered or unordered bullet format when discussing lessons

## Query Lessons During Development

**When to Query Lessons:**
- Before implementing a new feature or pattern
- When encountering an error or unexpected behavior
- When refactoring code
- When making architectural decisions
- When unsure about best practices

**How to Query:**
- Use `SearchLessons` with specific keywords (uses MySQL FULLTEXT search + relevance scoring - most helpful lessons appear first)
- Use `GetLessonByCategory` to browse by topic
- Use `FindRelatedLessons` to discover connected lessons and explore lesson relationships
- Set `include_related=true` in SearchLessons to automatically include related lessons in results
- Use `SuggestSearchQueries` when unsure about search terms - provides expanded query suggestions based on a topic
- Use `GetTopLessons` to discover the most valuable lessons overall or within a category - perfect when you want quality lessons without specific search terms
- Use `GetCategoryStatistics` to identify which categories have the most valuable lessons and see usage patterns - helps discover the best sources of knowledge
- Use `MarkLessonHelpful` to provide feedback when a lesson is particularly helpful or not helpful (improves future relevance scoring)
- Read `lessons://search-guide` resource for comprehensive search strategies, query examples, and best practices

**Automatic Usage Tracking:**
- Lessons are automatically tracked when you retrieve them via `SearchLessons`
- This helps improve relevance scoring over time - frequently used lessons rise to the top
- No action needed - usage tracking happens automatically

**Search Tips:**
- Use specific technical terms (e.g., "Pest mocking", "Inertia forms", "Laravel migrations")
- Results are ranked by relevance score (combines usage frequency, helpfulness rate, and recency) - most useful lessons first
- MySQL FULLTEXT search provides better relevance ranking than simple LIKE queries
- Use `SuggestSearchQueries` when unsure about search terms - it expands topics into related searches automatically
- Combine category and query parameters for focused results
- Use tags array to filter by specific technologies or patterns
- Review related lessons to discover additional context and connected knowledge
- Use `FindRelatedLessons` to explore prerequisite, alternative, or superseding lessons
- Deprecated lessons are automatically filtered out (unless `include_deprecated=true` is set)
- Provide feedback using `MarkLessonHelpful` when lessons are particularly useful or not useful (helps improve relevance for future searches)
- Consult `lessons://search-guide` resource for detailed search strategies and query examples when needed

## Relevance Scoring & Usage Tracking (Phase 3)

The lessons system now includes **automatic relevance scoring** to surface the most useful lessons:

**How Relevance Scoring Works:**
- Lessons are ranked by a combination of:
  - **Usage frequency** (40%) - How often the lesson is retrieved
  - **Helpfulness rate** (40%) - Percentage of positive feedback from users
  - **Recency** (20%) - Newer lessons get a slight boost
- Results are automatically sorted by relevance score - most helpful lessons appear first
- **Automatic usage tracking:** Every time you retrieve a lesson via `SearchLessons`, it's automatically tracked to improve future rankings

**Providing Feedback:**
- Use `mcp_lessons-learned-local_mark-lesson-helpful` to mark lessons as helpful (true) or not helpful (false)
- This explicit feedback improves relevance scoring and helps surface better lessons in future searches
- Feedback is especially valuable when a lesson saves time or solves a specific problem

**Deprecated Lessons:**
- Deprecated or superseded lessons are automatically filtered out from search results
- Use `include_deprecated=true` only when you need to see historical/outdated lessons

**Query Expansion:**
- Use `SuggestSearchQueries` tool when you're unsure about the best search terms
- Provides expanded query suggestions based on keyword matching and actual lessons in the database
- Suggests related categories and tags to help discover all relevant lessons
- Example: Query for "validation" expands to "form request", "validation rules", "error handling" plus relevant categories/tags

**Discovering Valuable Lessons:**
- Use `GetTopLessons` to surface the highest-quality lessons - perfect when you want the best lessons without specific search terms
  - Example: "What are the top lessons overall?" or "Show me the most valuable lessons in testing-patterns"
- Use `GetCategoryStatistics` to understand which categories have the most valuable lessons and identify best knowledge sources
  - Example: "Which categories have the most valuable lessons?" or "Show me statistics for the testing-patterns category"
- These tools leverage Phase 3 relevance scoring to show lessons ranked by actual usage and helpfulness

## Why This Matters

This project has **402 lessons** across **13 categories** with **42 tags** covering:

- Testing patterns (Laravel, Pest, PHPUnit)
- Architecture patterns (Inertia-first, API design)
- Laravel coding style and best practices
- Package development patterns
- And much more

These lessons are critical for maintaining code quality and avoiding past mistakes. **You must read them at session start.**

The relevance scoring system ensures that the **most useful and frequently referenced lessons rise to the top**, making it easier to find the right knowledge quickly. As you use lessons and provide feedback, the system learns which lessons are most valuable, creating a continuously improving knowledge base.
</mcp-server-instructions>

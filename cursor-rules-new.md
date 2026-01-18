<mcp-server-instructions>
# Lessons Learned MCP Server - CRITICAL SESSION STARTUP INSTRUCTIONS

## Leverage the MCP servers

**CRITICAL: At the start of each new AI agent session, you MUST:**

1. **Read the `lessons://overview` resource** from the Lessons Learned MCP Server
    - This resource is automatically available via the MCP server
    - Use `fetch_mcp_resource` with server `user-lessons-learned-local` and URI `lessons://overview`
    - This provides an overview of all available lessons, categories, tags, and recent lessons

2. **Use the LessonsLearnedOverview prompt** to understand what knowledge is available
    - This gives you an updated overview of available lessons

3. **Query relevant lessons** based on the current coding task
    - Use `mcp_lessons-learned-local_search-lessons` to search by keyword, category, or tags (uses MySQL FULLTEXT search for better relevance)
    - Use `mcp_lessons-learned-local_get-lesson-by-category` to browse lessons by category
    - Use `mcp_lessons-learned-local_get-lesson-tags` to discover available tags
    - Use `mcp_lessons-learned-local_find-related-lessons` to find lessons related to a specific lesson

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
- Use `SearchLessons` with specific keywords (uses MySQL FULLTEXT search for better relevance than LIKE queries)
- Use `GetLessonByCategory` to browse by topic
- Use `FindRelatedLessons` to discover connected lessons and explore lesson relationships
- Set `include_related=true` in SearchLessons to automatically include related lessons in results

**Search Tips:**
- Use specific technical terms (e.g., "Pest mocking", "Inertia forms", "Laravel migrations")
- MySQL FULLTEXT search provides better relevance ranking than simple LIKE queries
- Combine category and query parameters for focused results
- Use tags array to filter by specific technologies or patterns
- Review related lessons to discover additional context and connected knowledge
- Use `FindRelatedLessons` to explore prerequisite, alternative, or superseding lessons

## Why This Matters

This project has **402 lessons** across **13 categories** with **42 tags** covering:

- Testing patterns (Laravel, Pest, PHPUnit)
- Architecture patterns (Inertia-first, API design)
- Laravel coding style and best practices
- Package development patterns
- And much more

These lessons are critical for maintaining code quality and avoiding past mistakes. **You must read them at session start.**
</mcp-server-instructions>

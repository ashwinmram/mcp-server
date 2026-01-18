<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class LessonsSearchGuide extends Resource
{
    /**
     * The resource's URI.
     */
    protected string $uri = 'lessons://search-guide';

    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        Comprehensive guide on how to effectively search and use lessons learned. Provides query examples, relevance scoring explanation, and best practices for finding the right lessons.
    MARKDOWN;

    /**
     * The resource's MIME type.
     */
    protected string $mimeType = 'text/markdown';

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $content = <<<'MARKDOWN'
# Lessons Learned Search Guide

This guide helps AI agents effectively search and utilize lessons learned from previous coding sessions.

## Understanding Relevance Scoring

Lessons are automatically ranked by **relevance score** - a combination of:

- **Usage Frequency (40%)** - How often the lesson is retrieved (automatically tracked)
- **Helpfulness Rate (40%)** - Percentage of positive feedback from users
- **Recency (20%)** - Newer lessons get a slight boost

**Most useful lessons appear first in search results.** The system learns over time which lessons are most valuable based on usage patterns and explicit feedback.

## Search Strategies

### 1. Keyword Search (SearchLessons)

Best for finding lessons about specific topics, patterns, or technologies.

**Example Queries:**
- `"Pest mocking"` - Find lessons about mocking in Pest tests
- `"Inertia forms"` - Lessons about using Inertia form components
- `"Laravel migrations"` - Migration best practices
- `"validation rules"` - Form validation patterns
- `"type hints"` - PHP type hinting best practices

**Tips:**
- Use specific technical terms for better matches
- MySQL FULLTEXT search handles word variations (e.g., "test" matches "testing", "tests")
- Combine with category filter for focused results
- Results are ranked by relevance score automatically

### 2. Category Browse (GetLessonByCategory)

Best when you know the general topic category.

**When to Use:**
- When exploring a topic area (e.g., "testing-patterns", "laravel-coding-style")
- When you want to see all lessons in a specific domain
- When keyword search returns too many results

**Example Categories:**
- `testing-patterns` - Testing strategies and patterns
- `laravel-coding-style` - Laravel-specific coding conventions
- `mcp-configuration` - MCP server setup and configuration
- `package-development` - Package development patterns

### 3. Tag Filtering (SearchLessons with tags)

Best for finding lessons about specific technologies or patterns.

**Example Tags:**
- `php`, `pest`, `laravel` - Technology-specific lessons
- `best-practices`, `testing`, `architecture` - Pattern-based lessons

**Usage:**
```json
{
  "query": "validation",
  "tags": ["laravel", "best-practices"]
}
```

### 4. Related Lessons (FindRelatedLessons)

Best for discovering connected knowledge and exploring topic relationships.

**When to Use:**
- After finding a relevant lesson, discover related lessons
- Explore prerequisite, alternative, or superseding lessons
- Understand topic relationships and dependencies

## When to Query Lessons

**At Session Start:**
- Read `lessons://overview` resource for broad context
- Query lessons relevant to the current coding task
- Review related lessons to understand connections

**During Development:**
- Before implementing a new pattern or feature
- When encountering an error or unexpected behavior
- When refactoring code or making architectural decisions
- When unsure about best practices

**For Specific Tasks:**
- "How do I test [X]?" → Search for `"testing [X]"` or category `testing-patterns`
- "What's the best way to [Y]?" → Search for `"[Y] best practices"`
- "How to handle [Z] in Laravel?" → Search with tags `["laravel"]` and query `"[Z]"`

## Effective Query Examples

### Example 1: Finding Testing Patterns
```json
{
  "query": "Pest component testing",
  "category": "testing-patterns",
  "tags": ["pest", "vue"],
  "limit": 10
}
```

### Example 2: Laravel Best Practices
```json
{
  "query": "validation",
  "category": "laravel-coding-style",
  "tags": ["best-practices"],
  "limit": 5
}
```

### Example 3: Exploring Related Knowledge
1. Search for initial lesson: `{"query": "type hints"}`
2. Find related lessons: `{"lesson_id": "[found_lesson_id]", "limit": 5}`

## Providing Feedback

**Why Feedback Matters:**
- Improves relevance scoring for future searches
- Helps surface the most useful lessons
- Creates a continuously improving knowledge base

**How to Provide Feedback:**
- Use `MarkLessonHelpful` tool when a lesson is particularly useful
- Mark lessons as helpful (true) or not helpful (false)
- Especially valuable when a lesson saves time or solves a specific problem

**Example:**
```json
{
  "lesson_id": "[lesson_id]",
  "was_helpful": true
}
```

## Deprecated Lessons

- Deprecated or superseded lessons are **automatically filtered out** from search results
- Only use `include_deprecated=true` when you need historical/outdated lessons
- Superseded lessons reference newer alternatives via `superseded_by_lesson_id`

## Automatic Usage Tracking

- **Every lesson retrieval is automatically tracked** when using `SearchLessons`
- This improves relevance scoring over time without any action needed
- Frequently used lessons naturally rise to the top

## Best Practices

1. **Use specific keywords** - "Pest mocking" is better than "testing"
2. **Combine filters** - Use category + tags + query for focused results
3. **Explore related lessons** - Use `include_related=true` or `FindRelatedLessons`
4. **Provide feedback** - Mark helpful lessons to improve future searches
5. **Query early and often** - Don't wait until you're stuck, query proactively

## Common Patterns

**Pattern: Testing a Feature**
1. Search: `{"query": "[feature name] testing", "category": "testing-patterns"}`
2. Review results (ranked by relevance)
3. Check related lessons for additional context

**Pattern: Implementing Best Practices**
1. Search: `{"query": "[pattern]", "tags": ["best-practices"]}`
2. Filter by category if needed
3. Review top results (most relevant first)

**Pattern: Learning a New Pattern**
1. Browse category: `GetLessonByCategory` with relevant category
2. Review multiple lessons to understand the pattern
3. Find related lessons to explore connections

Remember: **The most useful lessons appear first** thanks to relevance scoring. Trust the ranking, but also explore related lessons for complete context.
MARKDOWN;

        return Response::text($content);
    }
}

# MCP Server Setup Guide

This guide explains how to set up and use the Lessons Learned MCP Server with Cursor IDE.

## Overview

This Laravel application serves as both:

1. **MCP Server** - Exposes lessons learned via MCP endpoint at `/mcp/lessons` for Cursor to query
2. **MCP Client** - Can push its own lessons using the `laravel-mcp-pusher` package

## Prerequisites

- Laravel Herd installed and running
- MySQL database configured and migrated
- Cursor IDE installed (v0.46 or later recommended)

## Initial Setup

### 1. Configure Environment Variables

Add the following to your `.env` file:

```env
# MCP Server Configuration (for Cursor)
APP_URL=https://mcp-server.test
MCP_SERVER_NAME="Lessons Learned MCP Server"

# MCP Client Configuration (for pushing lessons from this project)
MCP_SERVER_URL=https://mcp-server.test
MCP_API_TOKEN=  # Will be generated in step 2
```

### 2. Generate API Token

Generate a Sanctum API token for MCP authentication:

```bash
php artisan mcp:generate-token --name="cursor-mcp-token"
```

This command will:

- Create a user if none exists (or use existing user)
- Generate a new Sanctum token
- Display the token in a formatted output

**Copy the token** and add it to your `.env` file:

```env
MCP_API_TOKEN=your-token-here
```

**Note:** The token is only shown once. If you lose it, you'll need to generate a new one.

### 3. Verify Laravel Herd Setup

Ensure your site is accessible:

```bash
# Verify Herd is serving your site
curl https://mcp-server.test
```

If you haven't linked your project to Herd yet:

```bash
# Link the project (if needed)
cd /Users/ashwinmram/Herd/mcp-server
herd link mcp-server
```

### 4. Test MCP Endpoint

Test that the MCP endpoint is accessible:

```bash
# Test with authentication
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     https://mcp-server.test/mcp/lessons
```

You should receive a response indicating the MCP server is working.

## Configuring Cursor IDE

### Option 1: Using Cursor Settings UI

1. Open Cursor Settings:
    - Press `⇧+⌘+J` (Shift+Command+J on Mac)
    - Or go to `Settings` → `Features` → `MCP`

2. Click **"Add MCP Server"** or **"+"** button

3. Configure the server:
    - **Name:** `Lessons Learned MCP Server (Local)`
    - **Transport:** `SSE` (Server-Sent Events)
    - **URL:** `https://mcp-server.test/mcp/lessons`
    - **Headers:**
        ```json
        {
            "Authorization": "Bearer YOUR_SANCTUM_TOKEN_HERE"
        }
        ```
    - Replace `YOUR_SANCTUM_TOKEN_HERE` with the token you generated

4. Click **Save**

### Option 2: Using mcp.json Configuration File

Create a `mcp.json` file in your project root:

```json
{
    "mcpServers": {
        "lessons-learned-local": {
            "transport": "sse",
            "url": "https://mcp-server.test/mcp/lessons",
            "headers": {
                "Authorization": "Bearer YOUR_SANCTUM_TOKEN_HERE"
            }
        }
    }
}
```

Replace `YOUR_SANCTUM_TOKEN_HERE` with your actual token.

**Note:** Cursor may automatically detect `mcp.json` in your project root. If not, you may need to reference it in Cursor settings.

### Important: Global vs. Project-Level Configuration

Cursor supports MCP server configuration in two locations:

1. **Global Configuration** (`~/.cursor/mcp.json` or Cursor Settings UI) - Applies to all projects
2. **Project-Level Configuration** (`mcp.json` in project root) - Applies only to this project

For the Lessons Learned MCP Server, you can configure it either way. However, **project-level configuration** is recommended if you want this MCP server available only for this project.

## Automatic Lesson Loading at Session Start

**The MCP server is now configured to automatically load lessons at the start of each AI agent session.** This is accomplished through:

### 1. MCP Resource (Automatic)

The `lessons://overview` resource is registered and available to Cursor. According to the MCP specification, resources can be automatically loaded by AI clients as context. Cursor should automatically read this resource when the MCP server is connected.

**Resource URI:** `lessons://overview`
**MIME Type:** `text/markdown`
**Content:** Overview of all lessons, categories, tags, and recent lessons

### 2. Server Instructions (Guidance)

The MCP server's `instructions` property explicitly tells AI agents to:

- Read the `lessons://overview` resource at session start
- Use the `LessonsLearnedOverview` prompt to understand available knowledge
- Query relevant lessons using SearchLessons or GetLessonByCategory

### 3. .cursorrules Instructions (Explicit)

The project's `.cursorrules` file includes explicit instructions in the "lessons learned mcp rules" section that tell AI agents to:

- Always query the MCP server for lessons at the beginning of each new coding session
- Use SearchLessons, GetLessonByCategory, and GetLessonTags tools
- Apply lessons learned to guide coding decisions

### How It Works in Practice

When you start a new AI agent session in Cursor:

1. **Cursor connects to the MCP server** using the configured URL and authentication token
2. **MCP server advertises resources** - The `lessons://overview` resource becomes available
3. **AI agent reads `.cursorrules`** - Instructions tell it to query lessons at session start
4. **AI agent reads the resource** - The `lessons://overview` resource provides an overview
5. **AI agent queries relevant lessons** - Based on your coding task, it uses SearchLessons or GetLessonByCategory
6. **Lessons guide coding decisions** - Throughout the session, the agent applies lessons learned

## Using the MCP Server in Cursor

Once configured, the MCP server will be available in Cursor. The server provides tools, resources, and prompts for accessing lessons learned.

### Automatic Lesson Loading at Session Start

**The MCP server is configured to automatically load lessons at the start of each AI agent session.** This happens in two ways:

1. **MCP Resource** - The `lessons://overview` resource is automatically available to Cursor and can be read as context
2. **`.cursorrules` Instructions** - The project's `.cursorrules` file explicitly instructs AI agents to query the MCP server for lessons at session start

#### How It Works

When you start a new AI agent session in Cursor:

1. The AI agent reads the `lessons://overview` resource to understand available lessons
2. The `.cursorrules` file instructs the agent to use the `LessonsLearnedOverview` prompt
3. The agent queries relevant lessons based on your coding task using SearchLessons or GetLessonByCategory
4. Lessons are applied to guide coding decisions throughout the session

### Available Tools

- **SearchLessons** - Search for lessons learned by keyword, category, or tags (uses MySQL FULLTEXT search with relevance scoring)
- **GetLessonByCategory** - Get all lessons in a specific category (useful for browsing by topic)
- **GetLessonTags** - List all available tags used in lessons (helps discover available tags for filtering)
- **FindRelatedLessons** - Find lessons related to a specific lesson (explores topic relationships and dependencies)
- **MarkLessonHelpful** - Mark a lesson as helpful or not helpful (provides feedback for relevance scoring)
- **SuggestSearchQueries** - Suggest related search queries based on a topic (helps expand searches to find all relevant lessons)
- **GetTopLessons** - Get lessons with highest relevance scores (optionally by category) - surfaces most valuable lessons
- **GetCategoryStatistics** - Get statistics about lesson categories (avg relevance, top lessons, usage stats) - helps identify most valuable categories

### Available Resources

- **lessons://overview** - Automatically loaded overview of all lessons, categories, tags, and recent lessons
    - **MIME Type:** `text/markdown`
    - **Content:** Summary, categories list, popular tags, recent lessons preview

- **lessons://search-guide** - Comprehensive guide on how to effectively search and use lessons learned
    - **MIME Type:** `text/markdown`
    - **Content:** Query examples, relevance scoring explanation, search strategies, and best practices

### Available Prompts

- **LessonsLearnedOverview** - Provides an overview of available lessons learned from previous coding sessions (helps AI agents understand what knowledge is available)
- **LessonsByCategory** - Provides a summary of lessons available in a specific category (helps AI agents understand lessons for a particular topic)

### Example Usage

In Cursor, you can now ask AI agents:

- "What lessons do we have about Laravel validation?"
- "Show me lessons tagged with 'php' and 'best-practices'"
- "Give me an overview of available lessons"
- "What do we know about testing Laravel packages?"

The AI agent will automatically query the MCP server to find relevant lessons.

## Pushing Lessons from This Project

Since this project can also act as a client, you can push its own lessons using the `laravel-mcp-pusher` package:

```bash
php artisan mcp:push-lessons --source=mcp-server
```

This ensures lessons learned during development are captured and made available for future sessions.

## Project Details MCP Server

A second MCP server exposes **project-specific** implementation details (file locations, env vars, conventions) for a given project. Use it when working in a codebase that has pushed project details.

### Connection URL

The project is determined by the query parameter `project`. Use the same value as `--source` when pushing:

- **URL:** `https://mcp-server.test/mcp/project-details?project=<source_project>`
- Example: `https://mcp-server.test/mcp/project-details?project=my-app`

Use the same Authorization header (Bearer token) as the lessons server. Add a **separate** MCP server entry in Cursor for each project you want project details for (each with its own `?project=...`).

### Pushing project details

From a project that wants to expose implementation details to the AI:

```bash
php artisan mcp:push-project-details --source=my-app
```

Options:

- `--project-details-file=` — Path to markdown file (default: `docs/project-details.md`)
- `--project-details-json-dir=` — Directory for `project_details.json` or `project_details_*.json` (default: `docs`)

Files: `docs/project-details.md` (same structure as lessons-learned.md) and/or `docs/project_details.json` (JSON array of objects with title, summary, category, subcategory, type, tags, content, metadata). No generic validation is applied; project-specific paths and names are allowed.

## Managing Tokens

### List All MCP Tokens

```bash
php artisan mcp:list-tokens
```

This shows all tokens with their IDs, names, users, and usage information.

### Revoke a Token

```bash
php artisan mcp:list-tokens --revoke=<TOKEN_ID>
```

### Revoke All MCP Tokens

```bash
php artisan mcp:list-tokens --revoke-all
```

### Regenerate a Token

```bash
php artisan mcp:generate-token --name="cursor-mcp-token" --force
```

## Production Setup

For production deployment:

1. **Update Environment Variables:**

```env
APP_URL=https://your-production-domain.com
MCP_SERVER_URL=https://your-production-domain.com
MCP_API_TOKEN=your-production-token
```

2. **Generate Production Token:**

```bash
php artisan mcp:generate-token --name="cursor-mcp-production" --email="admin@yourdomain.com"
```

3. **Update Cursor Configuration:**
    - URL: `https://your-production-domain.com/mcp/lessons`
    - Use the production token in the Authorization header

4. **Update Sanctum Stateful Domains:**

    Ensure `config/sanctum.php` includes your production domain in the `stateful` array, or set it via `.env`:

```env
SANCTUM_STATEFUL_DOMAINS=your-production-domain.com,mcp-server.test,localhost
```

## Troubleshooting

### Token Not Working

- Verify the token exists: `php artisan mcp:list-tokens`
- Check that the token hasn't expired (if expiration is set)
- Ensure the token hasn't been revoked
- Regenerate if needed: `php artisan mcp:generate-token --force`

### Cursor Cannot Connect

- **Check URL:** Verify `https://mcp-server.test/mcp/lessons` is accessible in your browser
- **Check Herd:** Ensure Herd is running and the site is linked
- **Check SSL:** Herd uses `.test` domains with automatic SSL. If issues occur, check Herd's SSL certificate
- **Check Headers:** Ensure the Authorization header format is correct: `Bearer YOUR_TOKEN`

### CORS Issues

If you encounter CORS errors:

- Sanctum handles CORS for stateful domains
- Ensure your domain is in `config/sanctum.php` stateful domains
- For API-only authentication (Bearer tokens), CORS should not be an issue

### Authentication Errors (401)

- Verify the token is correct in your Cursor configuration
- Check that the `auth:sanctum` middleware is working
- Test manually: `curl -H "Authorization: Bearer TOKEN" https://mcp-server.test/mcp/lessons`

### Database Connection Issues

- Verify database is accessible: `php artisan migrate:status`
- Check `.env` database configuration
- Ensure migrations have been run: `php artisan migrate`

## Security Considerations

1. **Token Storage:** Never commit tokens to version control. Always use `.env` files.
2. **Token Rotation:** Regularly rotate tokens, especially if compromised.
3. **HTTPS:** Always use HTTPS in production to protect tokens in transit.
4. **Token Scope:** Current implementation uses `['*']` for full access. Consider restricting abilities for production use.
5. **Rate Limiting:** Consider adding rate limiting to MCP endpoints to prevent abuse.

## Additional Resources

- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [Model Context Protocol Specification](https://modelcontextprotocol.io)
- [Cursor MCP Documentation](https://docs.cursor.com/context/model-context-protocol)
- [Laravel Herd Documentation](https://herd.laravel.com/docs)

## Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review Laravel logs: `storage/logs/laravel.log`
3. Test endpoints manually with curl
4. Verify all environment variables are set correctly

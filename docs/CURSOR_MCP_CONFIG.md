# Configuring Cursor to Automatically Load Lessons Learned

This guide explains how to configure Cursor IDE to automatically load lessons learned from the MCP server at the start of each AI agent session.

## Overview

The Lessons Learned MCP Server is configured with:

- **MCP Resource** (`lessons://overview`) - Automatically available to Cursor
- **Server Instructions** - Guides AI agents to query lessons at session start
- **`.cursorrules` Instructions** - Explicitly tells AI agents to query lessons

## Step 1: Generate API Token

First, generate a Sanctum API token for authentication:

```bash
php artisan mcp:generate-token --name="cursor-mcp-token"
```

Copy the token that's displayed (you'll only see it once).

## Step 2: Configure Cursor

### Option A: Using Cursor Settings UI (Recommended)

1. Open Cursor Settings:
    - **Mac:** `⇧+⌘+J` (Shift+Command+J) or `Cursor` → `Settings`
    - **Windows/Linux:** `Ctrl+Shift+J` or `File` → `Preferences` → `Settings`
    - Navigate to: `Features` → `MCP` (or search for "MCP")

2. Click **"Add MCP Server"** or **"+"** button

3. Configure the server:
    - **Name:** `lessons-learned-local` (or any name you prefer)
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

5. **Restart Cursor** to ensure the MCP server connection is established

### Option B: Using mcp.json Configuration File

1. Create or update `mcp.json` in your project root:

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

2. Replace `YOUR_SANCTUM_TOKEN_HERE` with the token you generated

3. **Restart Cursor** if the MCP server doesn't connect automatically

## Step 3: Verify Configuration

After configuring, verify the MCP server is connected:

1. **Check Cursor's MCP Status:**
    - Open Cursor Settings → `Features` → `MCP`
    - Verify "lessons-learned-local" shows as **Connected** (green status)

2. **Test in a New Chat:**
    - Start a new chat session
    - Ask: "What lessons do we have about testing Laravel packages?"
    - The AI agent should automatically query the MCP server and return lessons

3. **Check Console/Logs:**
    - If using the browser console, you should see MCP requests
    - Check Laravel logs: `storage/logs/laravel.log` for any connection errors

## How Automatic Loading Works

### At Session Start

When you start a new AI agent session in Cursor:

1. **Cursor connects to the MCP server** using the configured URL and token
2. **MCP server advertises available resources** - The `lessons://overview` resource is listed
3. **AI agent reads `.cursorrules`** - Finds instructions in the "lessons learned mcp rules" section:
    ```
    CRITICAL: At the start of each new AI agent session, you MUST:
    1. Read the lessons://overview resource from the Lessons Learned MCP Server
    2. Use the LessonsLearnedOverview prompt to understand what knowledge is available
    3. Query relevant lessons based on the current coding task
    4. Apply lessons learned to guide your coding decisions
    ```
4. **AI agent reads the resource** - Accesses `lessons://overview` to get an overview
5. **AI agent queries relevant lessons** - Uses SearchLessons or GetLessonByCategory based on your task

### During Session

Throughout the session, the AI agent will:

- Reference lessons learned when making coding decisions
- Query additional lessons when encountering related topics
- Apply best practices from lessons to avoid repeating past mistakes

## Available MCP Components

### Resources (Automatically Loaded)

- **`lessons://overview`** - Markdown document with overview of all lessons, categories, tags, and recent lessons
    - **MIME Type:** `text/markdown`
    - **Content:** Summary, categories list, popular tags, recent lessons preview

### Tools (Query On-Demand)

- **SearchLessons** - Search by keyword, category, or tags
- **GetLessonByCategory** - Get all lessons in a specific category
- **GetLessonTags** - List all available tags

### Prompts (Manual Invocation)

- **LessonsLearnedOverview** - Get an updated overview (can be invoked manually)
- **LessonsByCategory** - Get lessons for a specific category (can be invoked manually)

## Troubleshooting

### MCP Server Not Connecting

**Symptoms:** Cursor shows the MCP server as disconnected or errors when querying

**Solutions:**

1. Verify the URL is correct: `https://mcp-server.test/mcp/lessons`
2. Check that Laravel Herd is running and the site is accessible
3. Verify the token is correct: `php artisan mcp:list-tokens`
4. Test manually: `curl -H "Authorization: Bearer YOUR_TOKEN" https://mcp-server.test/mcp/lessons`
5. Check Laravel logs: `tail -f storage/logs/laravel.log`

### Lessons Not Loading Automatically

**Symptoms:** AI agent doesn't query lessons at session start

**Solutions:**

1. **Verify `.cursorrules` is read** - Ensure `.cursorrules` contains the "lessons learned mcp rules" section
2. **Check MCP resource is registered** - Verify `LessonsOverviewResource` is in the server's `$resources` array
3. **Manually invoke the prompt** - Try: "Use the LessonsLearnedOverview prompt to show me available lessons"
4. **Check server instructions** - The server's `instructions` property should mention automatic loading
5. **Restart Cursor** - MCP connections are established on startup

### Authentication Errors (401)

**Symptoms:** `{"message":"Unauthenticated."}` errors in logs

**Solutions:**

1. Verify token exists: `php artisan mcp:list-tokens`
2. Check token format: Should be `Bearer YOUR_TOKEN` (include "Bearer" prefix in header)
3. Regenerate token if needed: `php artisan mcp:generate-token --name="cursor-mcp-token" --force`
4. Verify Sanctum middleware is applied to MCP routes in `routes/ai.php`

### SSL/Certificate Issues

**Symptoms:** Connection errors related to SSL certificates

**Solutions:**

1. Laravel Herd uses self-signed certificates for `.test` domains
2. If Cursor (Node-based MCP client) rejects the certificate, you may need to:
    - Switch to HTTP instead of HTTPS for local development (not recommended for production)
    - Or configure Cursor to accept the self-signed certificate
    - Or use a trusted certificate (e.g., via mkcert)

## Advanced Configuration

### Project-Level vs. Global Configuration

**Project-Level (`mcp.json` in project root):**

- Applies only to this project
- Committed to version control (token should be excluded via `.gitignore`)
- Good for project-specific MCP servers

**Global Configuration (`~/.cursor/mcp.json` or Settings UI):**

- Applies to all Cursor projects
- Stored in user settings
- Good for shared MCP servers used across projects

### Using Environment Variables in mcp.json

If you want to avoid hardcoding the token in `mcp.json`, you can:

1. Store the token in your system environment variables
2. Use Cursor's support for environment variable substitution (if available)
3. Or use a tool to inject the token at runtime

## End-of-Session Workflow

Lessons can be pushed to the MCP server using the `laravel-mcp-pusher` package. Refer to the package documentation for details on how to push lessons from your projects.

## Next Steps

After configuring Cursor:

1. **Start a new chat session** and verify lessons are automatically queried
2. **Test with a question** like "What lessons do we have about testing Laravel packages?"
3. **Check that lessons are applied** throughout the coding session
4. **Push new lessons** after each session using the `laravel-mcp-pusher` package

## Additional Resources

- [Model Context Protocol Specification](https://modelcontextprotocol.io)
- [Cursor MCP Documentation](https://docs.cursor.com/context/model-context-protocol)
- [Laravel MCP Package Documentation](https://laravel.com/docs/mcp)
- Project's `MCP_SETUP.md` for general setup instructions

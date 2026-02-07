# Lessons Learned MCP Server

Central MCP server for storing and querying lessons learned and project-specific implementation details. A Laravel-based application that exposes two MCP endpoints for [Cursor IDE](https://cursor.com) and other MCP clients.

## Use with ashwinmram/mcp-pusher

**This server works in conjunction with the [ashwinmram/mcp-pusher](https://github.com/ashwinmram/mcp-pusher) package** to push lessons learned and project implementation details from your Laravel projects to this server via HTTP API.

- **Install:** `composer require ashwinmram/mcp-pusher`
- **Links:** [GitHub](https://github.com/ashwinmram/mcp-pusher) | [Packagist](https://packagist.org/packages/ashwinmram/mcp-pusher)
- **Commands:** `php artisan mcp:push-lessons`, `php artisan mcp:push-project-details`

The mcp-pusher package reads specific files from your project and POSTs them to this server's API (`/api/lessons` and `/api/project-details`). AI agents can then query that data via the MCP endpoints. See [File layout for mcp-pusher](#file-layout-for-mcp-pusher) below for exact paths and formats.

## Key Features

- **Lessons Learned MCP** (`/mcp/lessons`) — Search, browse, and retrieve lessons with relevance scoring
- **Project Details MCP** (`/mcp/project-details?project=…`) — Expose project-specific implementation details (file locations, env vars, conventions)
- **Tools:** SearchLessons, GetLessonByCategory, GetLessonTags, FindRelatedLessons, MarkLessonHelpful, SuggestSearchQueries, GetTopLessons, GetCategoryStatistics
- **Resources:** `lessons://overview`, `lessons://search-guide`
- **Prompts:** LessonsLearnedOverview, LessonsByCategory

## Prerequisites

- PHP 8.2+, Laravel 12
- MySQL or SQLite
- [Laravel Herd](https://herd.laravel.com) (recommended) or equivalent local dev setup
- Cursor IDE (v0.46 or later) or another MCP client

## Initial Setup

### 1. Clone and Install

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### 2. Configure Environment Variables

Add the following to your `.env` file:

```env
# MCP Server Configuration (for Cursor)
APP_URL=https://mcp-server.test
MCP_SERVER_NAME="Lessons Learned MCP Server"

# MCP Client Configuration (for pushing lessons from this project)
MCP_SERVER_URL=https://mcp-server.test
MCP_API_TOKEN=  # Will be generated in step 3
```

### 3. Generate API Token

Generate a Sanctum API token for MCP authentication:

```bash
php artisan mcp:generate-token --name="cursor-mcp-token"
```

This command creates a user if none exists, generates a Sanctum token, and displays it. **Copy the token** (shown only once) and add it to `.env`:

```env
MCP_API_TOKEN=your-token-here
```

### 4. Verify Setup

Ensure your site is accessible. With Laravel Herd:

```bash
curl https://mcp-server.test
```

If needed, link the project to Herd:

```bash
herd link mcp-server
```

Test the MCP endpoint:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     https://mcp-server.test/mcp/lessons
```

## Configuring Cursor

### Option A: Cursor Settings UI

1. Open Cursor Settings: `⇧+⌘+J` (Mac) or `Ctrl+Shift+J` (Windows/Linux)
2. Navigate to **Features** → **MCP**
3. Click **Add MCP Server** or **+**
4. Configure:
   - **Name:** `lessons-learned-local` (or any name)
   - **Transport:** `SSE` (Server-Sent Events)
   - **URL:** `https://mcp-server.test/mcp/lessons`
   - **Headers:** `{"Authorization": "Bearer YOUR_SANCTUM_TOKEN_HERE"}`
5. Click **Save**
6. **Restart Cursor** to establish the connection

### Option B: mcp.json Configuration File

Create `mcp.json` in your project root:

```json
{
    "mcpServers": {
        "lessons-learned-local": {
            "transport": "sse",
            "url": "https://mcp-server.test/mcp/lessons",
            "headers": {
                "Authorization": "Bearer YOUR_SANCTUM_TOKEN_HERE"
            }
        },
        "project-details-my-app": {
            "transport": "sse",
            "url": "https://mcp-server.test/mcp/project-details?project=my-app",
            "headers": {
                "Authorization": "Bearer YOUR_SANCTUM_TOKEN_HERE"
            }
        }
    }
}
```

Add one **project-details** entry per project; replace `my-app` with the same value you use for `--source` when running `php artisan mcp:push-project-details`. See [mcp.json.example](mcp.json.example) for a minimal example.

### Global vs. Project-Level Configuration

- **Global** (`~/.cursor/mcp.json` or Cursor Settings UI) — Applies to all projects
- **Project-Level** (`mcp.json` in project root) — Applies only to this project. Token should be excluded via `.gitignore`.

### Verify Configuration

1. Open Cursor Settings → **Features** → **MCP** and verify the server shows as **Connected**
2. Start a new chat and ask: "What lessons do we have about testing Laravel packages?"

## How Automatic Loading Works

When you start a new AI agent session in Cursor:

1. Cursor connects to the MCP server using the configured URL and token
2. The MCP server advertises the `lessons://overview` resource
3. The AI agent reads `.cursorrules` (if configured) with instructions to query lessons at session start
4. The AI agent reads `lessons://overview` for an overview
5. The AI agent queries relevant lessons using SearchLessons or GetLessonByCategory
6. Lessons guide coding decisions throughout the session

**.cursorrules** instructions tell the agent to:

- Read the `lessons://overview` resource at session start
- Use the LessonsLearnedOverview prompt
- Query relevant lessons and apply them to coding decisions

## Available MCP Components

### Tools

- **SearchLessons** — Search by keyword, category, or tags (MySQL FULLTEXT + relevance scoring)
- **GetLessonByCategory** — Get all lessons in a category
- **GetLessonTags** — List available tags
- **FindRelatedLessons** — Find related lessons by topic relationships
- **MarkLessonHelpful** — Provide feedback for relevance scoring
- **SuggestSearchQueries** — Expand searches with related queries
- **GetTopLessons** — Get highest relevance lessons (optionally by category)
- **GetCategoryStatistics** — Category stats (avg relevance, top lessons, usage)

### Resources

- **lessons://overview** — Overview of lessons, categories, tags, and recent lessons
- **lessons://search-guide** — Search strategies, query examples, relevance scoring

### Prompts

- **LessonsLearnedOverview** — Overview of available lessons
- **LessonsByCategory** — Summary of lessons in a specific category

### Example Usage

Ask in Cursor:

- "What lessons do we have about Laravel validation?"
- "Show me lessons tagged with 'php' and 'best-practices'"
- "Give me an overview of available lessons"
- "What do we know about testing Laravel packages?"

## Pushing Lessons (Using ashwinmram/mcp-pusher)

**Workflow:** At the end of each coding session, generate and populate the lessons learned and project details files before pushing. You need content in at least one source file per command. **Source files are truncated (emptied) after each successful push** to avoid duplicates — regenerate them at the end of the next session and push again.

### File Layout for mcp-pusher

The mcp-pusher package reads files from your project and pushes them to this server. **Exact paths and names matter** — the API expects these defaults:

#### Lessons Learned (Lessons MCP)

| File | Location | Description |
|------|----------|-------------|
| `lessons-learned.md` | `docs/` | Markdown file with H2/H3/H4 headings, bullets, code blocks. Categorized as `guidelines`. |
| `lessons_learned.json` | `docs/` | JSON array of lesson objects. Each object can have: `title`, `summary`, `category`, `subcategory`, `type` (e.g. `"ai_output"`), `tags`, `content`, optional `metadata`. |

**Command:** `php artisan mcp:push-lessons --source=your-project`

**Options:**

- `--lessons-learned=` — Override path (default: `docs/lessons-learned.md`)
- `--lessons-json=` — Override path for `lessons_learned.json` (default: `docs/lessons_learned.json`)

**Lessons JSON format** — The `lessons_learned.json` file must be a JSON array. Each object should have: `title`, `summary`, `category`, `subcategory`, `type` (e.g. `"ai_output"`), `tags`, `content`, optional `metadata`.

#### Project Details (Project Details MCP)

| File | Location | Description |
|------|----------|-------------|
| `project-details.md` | `docs/` | Markdown with H2/H3/H4, bullets, code blocks. Same structure as lessons-learned.md. Categorized as `project-implementation`. |
| `project_details.json` or `project_details_*.json` | `docs/` | JSON array of objects. Same field order as lessons: `title`, `summary`, `category`, `subcategory`, `type` (`"project_detail"` or `"ai_output"`), `tags`, `content`, `metadata`. |

**Command:** `php artisan mcp:push-project-details --source=your-project`

**Options:**

- `--project-details-file=` — Override path (default: `docs/project-details.md`)
- `--project-details-json-dir=` — Override directory (default: `docs`)
- `--no-truncate` — Do not empty source files after a successful push

**Summary of defaults:**

```
your-project/
└── docs/
    ├── lessons-learned.md      ← Lessons
    ├── lessons_learned.json    ← Lessons
    ├── project-details.md      ← Project details
    └── project_details.json    ← Project details (or project_details_*.json)
```

### From This Server

This project can push its own lessons:

```bash
php artisan mcp:push-lessons --source=mcp-server
```

### From Other Laravel Projects

1. Install the package: `composer require ashwinmram/mcp-pusher`
2. Add to `config/services.php`:

```php
'mcp' => [
    'server_url' => env('MCP_SERVER_URL'),
    'api_token' => env('MCP_API_TOKEN'),
],
```

3. Configure `.env` with this server's URL and token
4. Create the files in the locations above (e.g. `lessons-learned.md`, `lessons_learned.json`, `project-details.md` in `docs/`)
5. Run:

```bash
php artisan mcp:push-lessons --source=your-project
php artisan mcp:push-project-details --source=your-project
```

See [ashwinmram/mcp-pusher](https://github.com/ashwinmram/mcp-pusher) for full documentation.

## Project Details MCP Server

A second MCP server exposes **project-specific** implementation details. Use it when working in a codebase that has pushed project details.

### Connection URL

- **URL:** `https://mcp-server.test/mcp/project-details?project=<source_project>`
- **Example:** `https://mcp-server.test/mcp/project-details?project=my-app`

Use the same Bearer token. Add a **separate** MCP server entry per project (each with its own `?project=...`).

### Pushing Project Details

From a project that wants to expose implementation details, use `php artisan mcp:push-project-details --source=my-app`. File layout: `docs/project-details.md` and/or `docs/project_details.json` (or `project_details_*.json`). See [File layout for mcp-pusher](#file-layout-for-mcp-pusher) for paths, names, and formats.

## Managing Tokens

```bash
# List all tokens
php artisan mcp:list-tokens

# Revoke a token
php artisan mcp:list-tokens --revoke=<TOKEN_ID>

# Revoke all tokens
php artisan mcp:list-tokens --revoke-all

# Regenerate a token
php artisan mcp:generate-token --name="cursor-mcp-token" --force
```

## Production Setup

1. Update `.env`:

```env
APP_URL=https://your-production-domain.com
MCP_SERVER_URL=https://your-production-domain.com
MCP_API_TOKEN=your-production-token
```

2. Generate a production token:

```bash
php artisan mcp:generate-token --name="cursor-mcp-production" --email="admin@yourdomain.com"
```

3. Update Cursor: use `https://your-production-domain.com/mcp/lessons` and the production token

4. Update Sanctum stateful domains (e.g. in `.env`):

```env
SANCTUM_STATEFUL_DOMAINS=your-production-domain.com,mcp-server.test,localhost
```

## Troubleshooting

### Token Not Working

- Verify: `php artisan mcp:list-tokens`
- Regenerate: `php artisan mcp:generate-token --force`

### Cursor Cannot Connect

- Verify URL: `https://mcp-server.test/mcp/lessons` is accessible
- Ensure Herd is running and the site is linked
- Check Authorization header: `Bearer YOUR_TOKEN`

### MCP Server Not Connecting / Lessons Not Loading

- Restart Cursor — MCP connections are established on startup
- Manually invoke: "Use the LessonsLearnedOverview prompt to show me available lessons"
- Check Laravel logs: `tail -f storage/logs/laravel.log`

### Authentication Errors (401)

- Verify token: `php artisan mcp:list-tokens`
- Ensure header format: `Bearer YOUR_TOKEN` (include "Bearer" prefix)
- Test: `curl -H "Authorization: Bearer TOKEN" https://mcp-server.test/mcp/lessons`

### SSL/Certificate Issues

Herd uses self-signed certificates for `.test` domains. If Cursor rejects the certificate:

- Configure Cursor to accept the self-signed certificate, or
- Use a trusted certificate (e.g. via [mkcert](https://github.com/FiloSottile/mkcert))

### Database Connection Issues

- Check: `php artisan migrate:status`
- Verify `.env` database configuration
- Run: `php artisan migrate`

### CORS Issues

- Sanctum handles CORS for stateful domains
- Ensure your domain is in `config/sanctum.php` stateful domains

## Security Considerations

1. **Token Storage** — Never commit tokens. Use `.env` files.
2. **Token Rotation** — Rotate tokens regularly, especially if compromised.
3. **HTTPS** — Always use HTTPS in production.
4. **Token Scope** — Consider restricting abilities for production.
5. **Rate Limiting** — Consider rate limiting MCP endpoints.

## Project Structure

- `routes/ai.php` — MCP route registration
- `app/Mcp/` — MCP server classes (LessonsServer, ProjectDetailsServer)
- `docs/` — `lessons-learned.md`, `lessons_learned.json`, `project-details.md`, `project_details.json` (sources for mcp-pusher; see [file layout](#file-layout-for-mcp-pusher))
- `packages/laravel-mcp-pusher` — Local development copy (published as [ashwinmram/mcp-pusher](https://packagist.org/packages/ashwinmram/mcp-pusher))

## Additional Resources

- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Model Context Protocol](https://modelcontextprotocol.io)
- [Cursor MCP Documentation](https://docs.cursor.com/context/model-context-protocol)
- [Laravel MCP Package](https://laravel.com/docs/mcp)
- [Laravel Herd](https://herd.laravel.com/docs)

## Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review Laravel logs: `storage/logs/laravel.log`
3. Test endpoints manually with curl
4. Verify all environment variables are set correctly

## License

MIT

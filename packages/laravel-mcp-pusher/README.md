# Laravel MCP Pusher

Laravel package to push lessons learned and project implementation details from your projects to a central MCP server via HTTP API. Works with the [Lessons Learned MCP Server](https://github.com/ashwinmram/mcp-server) so AI agents (e.g. Cursor) can query your knowledge base.

- [GitHub](https://github.com/ashwinmram/mcp-pusher)
- [Packagist](https://packagist.org/packages/ashwinmram/mcp-pusher)

**Version 3.0** — see [CHANGELOG.md](CHANGELOG.md) for migration from 1.x/2.0.x (`mcp:push-lessons` / `mcp:push-project-details` removed).

## Best practices (read this first)

| Command | Role | How often |
|---------|------|-----------|
| **`mcp:append`** | **Primary capture** — intentional notes on disk while context is fresh | **Frequently** after non-trivial work |
| **`mcp:push`** | **Publish** generic lessons + project details to the MCP server | **Once** per session |
| **`mcp:extract-session`** | **Fallback only** — salvage from transcript/git when append was skipped or drafts are thin | **Rarely** |

### Recommended workflow

```text
During session (often):
  php artisan mcp:append '<json entry>'

End of session (once):
  review docs/.mcp-session/*-draft.jsonl
  [only if drafts incomplete] php artisan mcp:extract-session
  php artisan mcp:push --source=your-project
```

### What `mcp:extract-session` is NOT

- Not a substitute for frequent `mcp:append`
- Not run automatically when Cursor compacts (unless you configure hooks yourself)
- Not guaranteed accurate — review before push
- Not required if drafts already capture the session

Use **`mcp:extract-session`** only when the session was long, compaction likely happened, few `mcp:append` calls were made, and you still want to capture learnings before push.

## Requirements

- PHP 8.2+
- Laravel 12.x or 13.x
- An MCP server that accepts pushes (e.g. [Lessons Learned MCP Server](https://github.com/ashwinmram/mcp-server))

## Installation

```bash
composer require ashwinmram/mcp-pusher:^3.0
```

## Configuration

Add to `config/services.php`:

```php
'mcp' => [
    'server_url' => env('MCP_SERVER_URL'),
    'api_token' => env('MCP_API_TOKEN'),
],
```

`.env`:

```
MCP_SERVER_URL=https://your-mcp-server.com
MCP_API_TOKEN=your-api-token-here
```

Add session drafts to `.gitignore` (see `stubs/gitignore-mcp-session.example`):

```gitignore
/docs/.mcp-session/
```

## Commands

### `mcp:append` (primary)

```bash
php artisan mcp:append '{"knowledge_scope":"generic","title":"...","summary":"...","category":"...","subcategory":"...","type":"ai_output","tags":[],"content":"..."}'
# or
php artisan mcp:append --file=entry.json
```

Routing:

- `knowledge_scope`: `"generic"` → `docs/.mcp-session/lessons-draft.jsonl`
- `knowledge_scope`: `"project"` → `docs/.mcp-session/project-details-draft.jsonl`
- Omitted: `type: "project_detail"` → project; otherwise generic

### `mcp:push` (publish once)

```bash
php artisan mcp:push --source=your-project [--no-truncate]
```

Merges **all** sources (drafts + legacy files), pushes to `/api/lessons` and `/api/project-details` when each bucket has content, truncates on success unless `--no-truncate`.

`--source` must match the `?project=` query param for Project Details MCP.

### `mcp:extract-session` (fallback)

```bash
php artisan mcp:extract-session [--transcript=/path/to.jsonl] [--since-git=main]
```

Appends heuristic candidates to draft JSONL. **Review** drafts, then `mcp:push`.

## Migration from 1.x

```bash
# Before
php artisan mcp:push-lessons --source=my-app
php artisan mcp:push-project-details --source=my-app

# After 3.0
php artisan mcp:push --source=my-app
```

## File layout

```
your-project/
└── docs/
    ├── lessons-learned.md              ← legacy (optional)
    ├── lessons_learned.json            ← legacy (optional)
    ├── project-details.md              ← legacy (optional)
    ├── project_details.json            ← legacy (optional)
    └── .mcp-session/
        ├── lessons-draft.jsonl         ← append during session (generic)
        └── project-details-draft.jsonl ← append during session (project)
```

## Optional: Cursor hooks

First-time setup: **[docs/CURSOR-HOOKS.md](docs/CURSOR-HOOKS.md)** — copy `stubs/cursor-hooks/` into `.cursor/hooks`, `chmod +x`, verify in Settings → Hooks.

Hooks encourage `mcp:append` before compaction and remind you to `mcp:push` at session end.

## Recommended AI prompt (end of session)

```text
Primary: use mcp:append during the session whenever you learn something reusable.

Before push:
1. Read docs/.mcp-session/*-draft.jsonl
2. ONLY IF drafts are missing important learnings: run mcp:extract-session, then review and dedupe
3. Run once: php artisan mcp:push --source=<project>
```

## How it works

- **Generic** lessons → `POST /api/lessons`
- **Project** details → `POST /api/project-details`
- Server deduplicates by content hash

## License

MIT

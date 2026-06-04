# Laravel MCP Pusher

Laravel package to push lessons learned and project implementation details from your projects to a central MCP server via HTTP API. Works with the [Lessons Learned MCP Server](https://github.com/ashwinmram/mcp-server) so AI agents (e.g. Cursor) can query your knowledge base.

- [GitHub](https://github.com/ashwinmram/mcp-pusher)
- [Packagist](https://packagist.org/packages/ashwinmram/mcp-pusher)

**Version 3.0** — see [CHANGELOG.md](CHANGELOG.md) for migration from 1.x/2.0.x (`mcp:push-lessons` / `mcp:push-project-details` removed).

## Best practice (read this first)

**Do not** hand-type `php artisan mcp:append` JSON during normal work. The recommended flow is:

1. **Install the [preCompact Cursor hook](#optional-cursor-hooks)** (one-time).
2. When Cursor is about to compact (or anytime you want to capture learnings), **paste the [Knowledge capture prompt](#knowledge-capture-prompt)** into your agent chat.
3. Let the agent run `mcp:append` for each lesson — entries land in `docs/.mcp-session/*-draft.jsonl`.
4. **End of session:** review drafts, then run `mcp:push` once (see [End of session](#end-of-session)).

The hook shows the same prompt automatically as `user_message` before compaction. You can also paste it manually after big tasks or before you close a session.

| Step | What you do | What runs on disk |
|------|-------------|-------------------|
| **Capture** | Submit the capture prompt to your agent | Agent executes `mcp:append` → draft JSONL |
| **Publish** | `php artisan mcp:push --source=your-project` | HTTP push to MCP server |
| **Fallback** | `mcp:extract-session` only if drafts are thin | Heuristic lines appended to drafts |

**Do not** update `docs/lessons-learned.md`, `lessons_learned.json`, `project-details.md`, or `project_details.json` during capture — legacy files are optional archives; duplicating the same lesson in drafts and legacy files can create duplicates at push.

## Knowledge capture prompt

Copy this entire block into your agent (Cursor, etc.). This is the **only** capture prompt you need.

```text
Context is about to compact — capture session knowledge NOW before it is lost.

Use php artisan mcp:append only (docs/.mcp-session/*-draft.jsonl). Do NOT edit docs/lessons-learned.md, lessons_learned.json, project-details.md, or project_details.json during capture.

For EACH distinct learning, run mcp:append with complete JSON. Execute commands; do not only describe entries.

Required on every entry: knowledge_scope ("generic"|"project"), title, summary, category, subcategory, type (ai_output|project_detail), tags (array), content.

Step A — Generic example:
php artisan mcp:append '{"knowledge_scope":"generic","title":"...","summary":"...","category":"...","subcategory":"...","type":"ai_output","tags":["..."],"content":"...","metadata":{"source":"agent"}}'

Step B — Project example:
php artisan mcp:append '{"knowledge_scope":"project","title":"...","summary":"...","category":"...","subcategory":"...","type":"project_detail","tags":["..."],"content":"...","metadata":{"source":"agent"}}'

Report: generic count, project count, every title appended.
```

With hooks installed, Cursor surfaces this text at compaction — submit it to the agent when shown.

## End of session

When you are ready to publish (manual — no Cursor hook):

```text
Session ending: review docs/.mcp-session/lessons-draft.jsonl and docs/.mcp-session/project-details-draft.jsonl. If drafts are thin, run: php artisan mcp:extract-session --since-git=main (fallback only). Then publish once: php artisan mcp:push --source=<your-project>
```

Replace `<your-project>` with your `--source` value (must match Project Details MCP `?project=`).

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

## Commands (reference)

These run **automatically when your agent follows the capture prompt**. You only need them directly for debugging or advanced use.

### `mcp:append`

Writes one entry to `docs/.mcp-session/lessons-draft.jsonl` (generic) or `project-details-draft.jsonl` (project). Routing uses `knowledge_scope` first, then `type: project_detail` for project.

```bash
# Advanced: single entry by hand or --file=entry.json
php artisan mcp:append --file=entry.json
```

### `mcp:push`

```bash
php artisan mcp:push --source=your-project [--no-truncate]
```

Merges drafts and any optional legacy `docs/` files, pushes to `/api/lessons` and `/api/project-details`, truncates sources on success unless `--no-truncate`.

### `mcp:extract-session` (fallback)

```bash
php artisan mcp:extract-session [--transcript=/path/to.jsonl] [--since-git=main]
```

Use only when drafts are thin after compaction. Review output, then `mcp:push`.

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
    ├── lessons-learned.md              ← legacy (optional, not used during capture)
    ├── lessons_learned.json            ← legacy (optional)
    ├── project-details.md              ← legacy (optional)
    ├── project_details.json            ← legacy (optional)
    └── .mcp-session/
        ├── lessons-draft.jsonl         ← capture prompt writes here (generic)
        └── project-details-draft.jsonl ← capture prompt writes here (project)
```

## Optional: Cursor hooks

Install once so Cursor shows the [Knowledge capture prompt](#knowledge-capture-prompt) as `user_message` before context compaction. Session-end `mcp:push` stays **manual** (no `stop` hook).

### Prerequisites

- Cursor with **Agent Hooks** (Settings → Features → Hooks)
- `composer require ashwinmram/mcp-pusher:^3.0`
- `jq` or `python3` on PATH (hook script emits JSON)

### Install hook stubs

From your **Laravel project root**:

**Composer install:**

```bash
mkdir -p .cursor/hooks
cp vendor/ashwinmram/mcp-pusher/stubs/cursor-hooks/hooks.json.example .cursor/hooks.json
cp vendor/ashwinmram/mcp-pusher/stubs/cursor-hooks/pre-compact-checkpoint.sh .cursor/hooks/
cp vendor/ashwinmram/mcp-pusher/stubs/cursor-hooks/pre-compact-prompt.txt .cursor/hooks/
chmod +x .cursor/hooks/pre-compact-checkpoint.sh
```

**Monorepo** (e.g. [mcp-server](https://github.com/ashwinmram/mcp-server)):

```bash
mkdir -p .cursor/hooks
cp packages/laravel-mcp-pusher/stubs/cursor-hooks/hooks.json.example .cursor/hooks.json
cp packages/laravel-mcp-pusher/stubs/cursor-hooks/pre-compact-checkpoint.sh .cursor/hooks/
cp packages/laravel-mcp-pusher/stubs/cursor-hooks/pre-compact-prompt.txt .cursor/hooks/
chmod +x .cursor/hooks/pre-compact-checkpoint.sh
```

| Stub | Purpose |
|------|---------|
| `hooks.json.example` | Wires **`preCompact` only** |
| `pre-compact-checkpoint.sh` | Reads `pre-compact-prompt.txt`, outputs `user_message` |
| `pre-compact-prompt.txt` | Same text as [Knowledge capture prompt](#knowledge-capture-prompt) above |

Example `.cursor/hooks.json`:

```json
{
  "version": 1,
  "hooks": {
    "preCompact": [
      {
        "command": ".cursor/hooks/pre-compact-checkpoint.sh"
      }
    ]
  }
}
```

### Verify

1. Save `.cursor/hooks.json` (restart Cursor if needed)
2. Settings → **Hooks** — confirm `preCompact` is listed
3. At compaction, confirm the capture prompt appears and submit it to the agent

### Optional Cursor rule

Copy `stubs/mcp-session-capture.mdc` to `.cursor/rules/mcp-session-capture.mdc` so agents follow the capture prompt pattern.

### Troubleshooting

- **Hook never runs** — `chmod +x` on the script; remove `matcher` from `hooks.json` temporarily
- **No prompt shown** — Keep `pre-compact-prompt.txt` next to the script in `.cursor/hooks/`, or paste the prompt from this README
- **preCompact never fires** — Depends on Cursor version; paste the [capture prompt](#knowledge-capture-prompt) manually
- **Removed `stop` hook** — Old `followup_message` on every loop was too noisy; use [End of session](#end-of-session) when you publish

### Security

Hooks run shell on your machine. Review scripts before enabling. Keep `docs/.mcp-session/` gitignored.

## How it works

- Agent runs `mcp:append` → draft JSONL on disk (survives compaction)
- **`mcp:push`** → `POST /api/lessons` and `/api/project-details`
- Server deduplicates by content hash

## License

MIT

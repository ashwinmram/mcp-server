# Laravel MCP Pusher

Laravel package to push lessons learned and project implementation details from your projects to a central MCP server via HTTP API. Works with the [Lessons Learned MCP Server](https://github.com/ashwinmram/mcp-server) so AI agents (e.g. Cursor) can query your knowledge base.

- [GitHub](https://github.com/ashwinmram/mcp-pusher)
- [Packagist](https://packagist.org/packages/ashwinmram/mcp-pusher)

**Version 3.0** — see [CHANGELOG.md](CHANGELOG.md) for migration from 1.x/2.0.x (`mcp:push-lessons` / `mcp:push-project-details` removed).

**Optional:** [Cursor hooks](#optional-cursor-hooks) — `preCompact` shows a capture prompt (`user_message`) before context compaction. Session end (`mcp:push`) is manual.

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

### Optional: automate with Cursor hooks

See [Optional: Cursor hooks](#optional-cursor-hooks) below for first-time setup (`stubs/cursor-hooks/` → `.cursor/hooks.json`). Hooks are optional; frequent `mcp:append` remains the primary capture path.

### What `mcp:extract-session` is NOT

- Not a substitute for frequent `mcp:append`
- Not run automatically when Cursor compacts (unless you configure [Cursor hooks](#optional-cursor-hooks))
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

Optional [Cursor Agent Hooks](https://docs.cursor.com) can show a **knowledge-capture prompt** when context is about to compact (`preCompact` → `user_message`). Hooks are **not required** for mcp-pusher to work. Session-end publish is **manual** (no `stop` hook — it auto-fired too often).

### Prerequisites

- Cursor with **Agent Hooks** (Settings → Features → Hooks)
- `composer require ashwinmram/mcp-pusher:^3.0` in your Laravel project
- `php` on your PATH (Herd users: hooks may need the full PHP path in scripts — see [Troubleshooting](#troubleshooting))
- `jq` only if you customize scripts to parse hook JSON

### Install hook stubs

Run from your **Laravel project root** (not inside `vendor/`).

**Installed via Composer:**

```bash
mkdir -p .cursor/hooks
cp vendor/ashwinmram/mcp-pusher/stubs/cursor-hooks/hooks.json.example .cursor/hooks.json
cp vendor/ashwinmram/mcp-pusher/stubs/cursor-hooks/pre-compact-checkpoint.sh .cursor/hooks/
cp vendor/ashwinmram/mcp-pusher/stubs/cursor-hooks/pre-compact-prompt.txt .cursor/hooks/
chmod +x .cursor/hooks/*.sh
```

**Local path repository** (e.g. [mcp-server](https://github.com/ashwinmram/mcp-server) monorepo):

```bash
mkdir -p .cursor/hooks
cp packages/laravel-mcp-pusher/stubs/cursor-hooks/hooks.json.example .cursor/hooks.json
cp packages/laravel-mcp-pusher/stubs/cursor-hooks/pre-compact-checkpoint.sh .cursor/hooks/
cp packages/laravel-mcp-pusher/stubs/cursor-hooks/pre-compact-prompt.txt .cursor/hooks/
chmod +x .cursor/hooks/*.sh
```

**Included stubs** (`stubs/cursor-hooks/`):

| File | Purpose |
|------|---------|
| `hooks.json.example` | Example `.cursor/hooks.json` with **`preCompact` only** |
| `pre-compact-checkpoint.sh` | `preCompact` — outputs `user_message` with capture prompt |
| `pre-compact-prompt.txt` | Prompt text (keep beside the script in `.cursor/hooks/`) |

Also see `stubs/mcp-capture-prompts.md` for copy-paste prompts (preCompact, session end manual, generic/project only).

Example `.cursor/hooks.json` (after copy):

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

Ensure session drafts are gitignored (see [Configuration](#configuration) or `stubs/gitignore-mcp-session.example`):

```gitignore
/docs/.mcp-session/
```

### Default hook

| Hook | Script | Behavior |
|------|--------|----------|
| `preCompact` | `pre-compact-checkpoint.sh` | Emits **`user_message`** with the capture prompt from `pre-compact-prompt.txt` — submit it to your agent when shown |

`preCompact` does **not** auto-run the agent; it only suggests the prompt. Hooks do **not** run `mcp:extract-session` or `mcp:push` automatically.

### Verify hooks loaded

1. Save `.cursor/hooks.json` (Cursor reloads hooks on save; restart Cursor if needed)
2. Open **Settings → Hooks** (or the **Hooks** output channel)
3. Confirm the `preCompact` entry appears
4. Trigger compaction and check that the capture prompt is shown

### Optional Cursor rule

Copy `stubs/mcp-session-capture.mdc` to `.cursor/rules/mcp-session-capture.mdc` so agents prefer frequent `mcp:append`.

### Project vs user hooks

| Location | Cwd | Path style |
|----------|-----|------------|
| **Project** `.cursor/hooks.json` (recommended) | Project root | `.cursor/hooks/script.sh` |
| **User** `~/.cursor/hooks.json` | `~/.cursor/` | `./hooks/script.sh` |

### Troubleshooting

- **Hook never runs** — Remove `matcher` from `hooks.json` temporarily; confirm script is executable (`chmod +x`)
- **No prompt shown** — Ensure `pre-compact-prompt.txt` is in `.cursor/hooks/` next to the script; script falls back to a short message if missing
- **preCompact never fires** — Compaction hooks depend on Cursor version/mode; use `stubs/mcp-capture-prompts.md` manually
- **JSON parse errors** — Install `jq` or ensure `python3` is on PATH (script uses one of them to emit valid JSON)
- **Removed `stop` hook** — Prior versions used `followup_message` on every agent loop end; use [Session end (manual)](#recommended-prompts) in `mcp-capture-prompts.md` when you want to push

### Security

Hooks execute shell commands on your machine. Review scripts before enabling. Draft files may contain code paths or snippets from `mcp:extract-session` — keep `docs/.mcp-session/` gitignored.

## Recommended prompts

Copy-paste prompts live in [`stubs/mcp-capture-prompts.md`](stubs/mcp-capture-prompts.md):

| When | Section |
|------|---------|
| Before compaction (hook `user_message`) | **preCompact** |
| Manual capture anytime | **Combined** |
| End of session (no hook) | **Session end (manual only)** |
| Generic or project only | **Generic only** / **Project only** |

**Session end (manual):**

```text
Session ending: review docs/.mcp-session/lessons-draft.jsonl and docs/.mcp-session/project-details-draft.jsonl. If drafts are thin, run: php artisan mcp:extract-session --since-git=main (fallback only). Then publish once: php artisan mcp:push --source=<project>
```

## How it works

- **Generic** lessons → `POST /api/lessons`
- **Project** details → `POST /api/project-details`
- Server deduplicates by content hash

## License

MIT

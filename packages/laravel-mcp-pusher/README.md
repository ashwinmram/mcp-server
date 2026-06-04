# Laravel MCP Pusher

Laravel package to push lessons learned and project implementation details from your projects to a central MCP server via HTTP API. Works with the [Lessons Learned MCP Server](https://github.com/ashwinmram/mcp-server) so AI agents in **Cursor**, **Claude Code**, **Google Antigravity**, and other MCP clients can query your knowledge base.

- [GitHub](https://github.com/ashwinmram/mcp-pusher)
- [Packagist](https://packagist.org/packages/ashwinmram/mcp-pusher)

## What's new in 3.0

Version 3.0 is a **capture-and-push workflow** built around session drafts on disk (survives context compaction in any AI client).

| Before (1.x / 2.x) | After (3.0) |
|--------------------|---------------|
| Edit `docs/lessons-learned.md` / JSON, then push | **`mcp:append`** during session → draft JSONL |
| `mcp:push-lessons` + `mcp:push-project-details` | Single **`mcp:push`** (generic + project) |
| Knowledge lost when chat compacts | Drafts in `docs/.mcp-session/` persist |

**Commands:**

- **`mcp:append`** — one structured entry → `lessons-draft.jsonl` or `project-details-draft.jsonl`
- **`mcp:push`** — publish drafts to the MCP server once per session
- **`mcp:extract-session`** — fallback only if drafts are thin after compaction

**Optional automation:** Cursor `preCompact` hook can show the [knowledge capture prompt](#knowledge-capture-prompt) before compaction. Claude Code and Antigravity have no equivalent hook — paste the prompt manually.

See [CHANGELOG.md](CHANGELOG.md) for full release notes.

```bash
composer require ashwinmram/mcp-pusher:^3.0

# Migration from 1.x / 2.x
# Before: mcp:push-lessons + mcp:push-project-details
# After:  mcp:push --source=your-project
```

## Best practice (read this first)

**Do not** hand-type `php artisan mcp:append` JSON during normal work.

1. **Paste the [Knowledge capture prompt](#knowledge-capture-prompt)** into your agent before context compaction (or anytime you want to capture learnings).
2. Let the agent run `mcp:append` for each lesson — entries land in `docs/.mcp-session/*-draft.jsonl`.
3. **End of session:** review drafts, then run `mcp:push` once (see [End of session](#end-of-session)).

**Cursor only (optional):** Install the [preCompact hook](#cursor-precompact-hook) so Cursor surfaces the same prompt automatically.

| Step | What you do | What runs on disk |
|------|-------------|-------------------|
| **Capture** | Submit the capture prompt to your agent | Agent executes `mcp:append` → draft JSONL |
| **Publish** | `php artisan mcp:push --source=your-project` | HTTP push to MCP server |
| **Fallback** | `mcp:extract-session` only if drafts are thin | Heuristic lines appended to drafts |

## Connect your AI client to the MCP server

Configure your client to reach the Lessons Learned server (and optional Project Details server). Example configs:

| Client | Example stub |
|--------|----------------|
| **Cursor** | `stubs/mcp-client-config/cursor-mcp.json.example` |
| **Claude Code** | `stubs/mcp-client-config/claude-mcp.json.example` |
| **Google Antigravity** | `stubs/mcp-client-config/antigravity-mcp_config.json.example` |

Full setup: [mcp-server README](https://github.com/ashwinmram/mcp-server#configuring-your-ai-client).

## Agent startup instructions

Copy `stubs/agent-instructions/mcp-session-startup.md` so agents query lessons at session start:

| IDE | Where to place |
|-----|----------------|
| **Cursor** | `.cursor/rules/mcp-session-capture.mdc` (from `stubs/mcp-session-capture.mdc`) plus optional project rules |
| **Claude Code** | `CLAUDE.md` in project root |
| **Google Antigravity** | Skill under `~/.gemini/skills/` |

## Knowledge capture prompt

Copy this entire block into your agent. This is the **only** capture prompt you need.

```text
Context is about to compact — capture session knowledge NOW before it is lost.

Use php artisan mcp:append only. Each entry is written to docs/.mcp-session/lessons-draft.jsonl (generic) or docs/.mcp-session/project-details-draft.jsonl (project).

For EACH distinct learning, run mcp:append with complete JSON. Execute commands; do not only describe entries.

Required on every entry: knowledge_scope ("generic"|"project"), title, summary, category, subcategory, type (ai_output|project_detail), tags (array), content.

Step A — Generic example:
php artisan mcp:append '{"knowledge_scope":"generic","title":"...","summary":"...","category":"...","subcategory":"...","type":"ai_output","tags":["..."],"content":"...","metadata":{"source":"agent"}}'

Step B — Project example:
php artisan mcp:append '{"knowledge_scope":"project","title":"...","summary":"...","category":"...","subcategory":"...","type":"project_detail","tags":["..."],"content":"...","metadata":{"source":"agent"}}'

Report: generic count, project count, every title appended.
```

**Cursor:** With the [preCompact hook](#cursor-precompact-hook) installed, submit this text when Cursor shows it as `user_message`.

## End of session

When you are ready to publish:

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

## Commands

### `mcp:append`

Appends one entry to the correct draft file. The [capture prompt](#knowledge-capture-prompt) tells your agent when and how to run this.

- `knowledge_scope: "generic"` → `docs/.mcp-session/lessons-draft.jsonl`
- `knowledge_scope: "project"` → `docs/.mcp-session/project-details-draft.jsonl`

Advanced (debugging):

```bash
php artisan mcp:append --file=entry.json
```

### `mcp:push`

```bash
php artisan mcp:push --source=your-project [--no-truncate]
```

Reads draft JSONL files, pushes to `/api/lessons` and `/api/project-details` when each bucket has content. Clears draft files on success unless `--no-truncate`.

### `mcp:extract-session` (fallback)

```bash
php artisan mcp:extract-session [--transcript=/path/to.jsonl] [--since-git=main]
```

Appends heuristic candidates to draft JSONL. Review output, then `mcp:push`.

## File layout

```
your-project/
└── docs/
    └── .mcp-session/
        ├── lessons-draft.jsonl         ← generic lessons (mcp:append)
        └── project-details-draft.jsonl ← project details (mcp:append)
```

## Optional automation

### Cursor (preCompact hook)

Install once so Cursor shows the [Knowledge capture prompt](#knowledge-capture-prompt) as `user_message` before context compaction.

**Prerequisites:** Cursor with **Agent Hooks**; `jq` or `python3` on PATH.

**Composer install:**

```bash
mkdir -p .cursor/hooks
cp vendor/ashwinmram/mcp-pusher/stubs/cursor-hooks/hooks.json.example .cursor/hooks.json
cp vendor/ashwinmram/mcp-pusher/stubs/cursor-hooks/pre-compact-checkpoint.sh .cursor/hooks/
cp vendor/ashwinmram/mcp-pusher/stubs/cursor-hooks/pre-compact-prompt.txt .cursor/hooks/
chmod +x .cursor/hooks/pre-compact-checkpoint.sh
```

**Monorepo** (e.g. [mcp-server](https://github.com/ashwinmram/mcp-server)): use `packages/laravel-mcp-pusher/stubs/cursor-hooks/` paths instead of `vendor/…`.

| Stub | Purpose |
|------|---------|
| `hooks.json.example` | Wires **`preCompact` only** |
| `pre-compact-checkpoint.sh` | Reads `pre-compact-prompt.txt`, outputs `user_message` |
| `pre-compact-prompt.txt` | Same text as [Knowledge capture prompt](#knowledge-capture-prompt) |

**Optional Cursor rule:** `stubs/mcp-session-capture.mdc` → `.cursor/rules/mcp-session-capture.mdc`

**Troubleshooting:** `chmod +x` the script; paste the capture prompt manually if `preCompact` never fires.

### Claude Code and Google Antigravity

No built-in pre-compaction hook. **Paste the [capture prompt](#knowledge-capture-prompt)** manually, or add a personal skill/reminder that includes the text from `stubs/pre-compact-prompt.txt`.

### Security

Hooks run shell on your machine. Review scripts before enabling. Keep `docs/.mcp-session/` gitignored.

## How it works

- Agent runs `mcp:append` → draft JSONL on disk (survives compaction)
- **`mcp:push`** → `POST /api/lessons` and `/api/project-details`
- Server deduplicates by content hash

## License

MIT

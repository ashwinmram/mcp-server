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
- **`mcp:extract-session`** — git-only fallback or historical seeding from commit history

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
2. The agent gathers recent **git log + diff**, synthesizes learnings from the session and commits, and runs `mcp:append` for each lesson — entries land in `docs/.mcp-session/*-draft.jsonl`.
3. **End of session:** review drafts, then run `mcp:push` once (see [End of session](#end-of-session)).

**Cursor only (optional):** Install the [preCompact hook](#cursor-precompact-hook) so Cursor surfaces the same prompt automatically.

| Step | What you do | What runs on disk |
|------|-------------|-------------------|
| **Capture** | Submit the capture prompt to your agent | Agent gathers git context, synthesizes lessons, runs `mcp:append` → draft JSONL |
| **Publish** | `php artisan mcp:push --source=your-project` | HTTP push to MCP server |
| **Fallback** | `mcp:extract-session` after you **commit** (default: latest commit) | Git log + diff stat → draft JSONL |
| **Seeding** | `mcp:extract-session --since-git=HEAD~N` on a mature repo | Deeper history; review drafts before push |

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

The agent gathers **git history + session context**, synthesizes reusable lessons, and runs `mcp:append` for each — never raw commit one-liners.

**Single stub (do not duplicate elsewhere):**

- Package path: `stubs/knowledge-capture-prompt.txt`
- Composer install: `vendor/ashwinmram/mcp-pusher/stubs/knowledge-capture-prompt.txt`

Open that file and paste its full contents into your agent before context compaction (or anytime you want to capture learnings).

**Cursor:** With the [preCompact hook](#cursor-precompact-hook) installed, the hook emits this file as `user_message` automatically.

## End of session

When you are ready to publish, review `docs/.mcp-session/lessons-draft.jsonl` and `project-details-draft.jsonl`. Delete any raw git-salvage lines (titles like `Git commit: …` or `metadata.source: git`) before pushing. If drafts are still thin and you have committed session work, run `php artisan mcp:extract-session` as a last resort (produces candidates only — edit or remove before push). Then publish once:

```bash
php artisan mcp:push --source=<your-project>
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

### `mcp:extract-session` (git → drafts)

Git-only salvage into the same draft JSONL files as `mcp:append`. Does **not** read uncommitted files — **commit first** (staging alone is not enough).

```bash
php artisan mcp:extract-session [--since-git=HEAD~1]
```

#### How `--since-git` controls depth

The option sets where the commit range **starts**. Everything from that ref up to `HEAD` is summarized into draft lines (`git log` one-liners + `git diff --stat` for the range).

| Command | Commits included (approx.) | Typical use |
|---------|---------------------------|-------------|
| `mcp:extract-session` | Latest commit only (`HEAD~1..HEAD`) | End-of-session salvage |
| `mcp:extract-session --since-git=HEAD~7` | Last 7 commits | Recent sprint / week of work |
| `mcp:extract-session --since-git=HEAD~30` | Last 30 commits | Broader backfill |
| `mcp:extract-session --since-git=main` | Commits on `HEAD` not already on `main` | Feature branch ahead of `main` |
| `mcp:extract-session --since-git=v1.0.0` | Since a tag or SHA | Release-to-HEAD snapshot |

- **Default `HEAD~1`** — only the **tip** commit (one commit).
- **`HEAD~N`** — increase `N` to go deeper into history (useful when installing mcp-pusher on a long-running repo).
- **`main`** — same as `git log main..HEAD` (see caveat below).

#### `--since-git=main` (commits only)

This does **not** read the staging area or working tree.

| Situation | Result |
|-----------|--------|
| Feature branch with commits ahead of `main` | Extracts those branch commits + diff stat |
| On `main`, only uncommitted/staged changes | **Empty range** — command fails; commit first or use `HEAD~N` |
| `main` and `HEAD` point at the same commit | **Empty range** — use `HEAD~N` or a tag/SHA instead |

For seeding history **on** `main`, use `HEAD~N` (e.g. `HEAD~50`), not `main..HEAD`.

The [knowledge capture prompt](#knowledge-capture-prompt) already instructs agents to gather git context during preCompact capture. Use `mcp:extract-session` only when that capture was skipped.

#### Use case A: session fallback

When git-informed capture and `mcp:append` were both skipped but you committed session work:

```bash
php artisan mcp:extract-session
# review docs/.mcp-session/*-draft.jsonl
php artisan mcp:push --source=your-project
```

#### Use case B: seeding an existing project

Installing mcp-pusher on a mature repo — bootstrap drafts from git, then publish:

```bash
php artisan mcp:extract-session --since-git=HEAD~50   # tune depth to your history
# edit drafts: replace heuristic lines with real lessons (or re-capture via agent + mcp:append)
php artisan mcp:push --source=your-project
```

Use `--since-git=main` only when you have a **feature branch with commits not merged to `main`**. Prefer editing drafts before push; extracted rows are **candidates**, not finished lessons.

#### Limits

- Output is heuristic (commit subjects + diff stats), not AI-summarized knowledge.
- Large ranges produce many draft lines — review before `mcp:push`.
- First commit in repo: `HEAD~1` may be invalid; use `HEAD~N` or an explicit SHA/tag.

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
cp vendor/ashwinmram/mcp-pusher/stubs/knowledge-capture-prompt.txt .cursor/hooks/
chmod +x .cursor/hooks/pre-compact-checkpoint.sh
```

**Monorepo** (e.g. [mcp-server](https://github.com/ashwinmram/mcp-server)): use `packages/laravel-mcp-pusher/stubs/cursor-hooks/` paths instead of `vendor/…`.

| Stub | Purpose |
|------|---------|
| `hooks.json.example` | Wires **`preCompact` only** |
| `pre-compact-checkpoint.sh` | Reads `knowledge-capture-prompt.txt`, outputs `user_message` |
| `knowledge-capture-prompt.txt` | [Knowledge capture prompt](#knowledge-capture-prompt) — single canonical file |

**Optional Cursor rule:** `stubs/mcp-session-capture.mdc` → `.cursor/rules/mcp-session-capture.mdc`

**Troubleshooting:** `chmod +x` the script; paste the capture prompt manually if `preCompact` never fires.

### Claude Code and Google Antigravity

No built-in pre-compaction hook. **Paste the [capture prompt](#knowledge-capture-prompt)** manually from `stubs/knowledge-capture-prompt.txt`, or add a personal skill/reminder that points to that file.

### Security

Hooks run shell on your machine. Review scripts before enabling. Keep `docs/.mcp-session/` gitignored.

## How it works

- Agent runs `mcp:append` → draft JSONL on disk (survives compaction)
- **`mcp:push`** → `POST /api/lessons` and `/api/project-details`
- Server deduplicates by content hash

## License

MIT

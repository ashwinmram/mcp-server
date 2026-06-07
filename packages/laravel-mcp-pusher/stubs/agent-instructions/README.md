# Agent instructions for MCP + Lessons Learned

Install startup and capture instructions for your AI client with Artisan (requires `ashwinmram/mcp-pusher`).

## Quick install

| Client | Command |
|--------|---------|
| **Cursor** | `php artisan mcp:install-cursor-rules` |
| **Claude Code** | `php artisan mcp:install-claude-instructions` |
| **Google Antigravity** | `php artisan mcp:install-antigravity-skills` |
| **All clients** | `php artisan mcp:install-agent-instructions` |

## Cursor

```bash
php artisan mcp:install-cursor-rules [--force] [--with-hooks] [--with-cursorrules]
```

Installs `.cursor/rules/mcp-session-startup.mdc` (always apply) and `mcp-session-capture.mdc` (on demand).

## Claude Code

```bash
php artisan mcp:install-claude-instructions [--force] [--with-claude-md]
```

Installs `.claude/rules/mcp-session-startup.md` and `mcp-session-capture.md`. Optional `--with-claude-md` adds a short root `CLAUDE.md` index.

## Google Antigravity

```bash
php artisan mcp:install-antigravity-skills [--force] [--global]
```

Installs workspace skills under `.agent/skills/mcp-session-startup/` and `mcp-session-capture/` (each with `SKILL.md`). Use `--global` for `~/.gemini/antigravity/global_skills/` on your machine.

Commit `.agent/skills/` to share Antigravity workflow with your team.

## Install all

```bash
php artisan mcp:install-agent-instructions [--force] [--with-hooks] [--with-cursorrules] [--with-claude-md] [--global]
```

Pass client names to limit scope: `php artisan mcp:install-agent-instructions cursor claude`

## Manual install

Copy stubs from `vendor/ashwinmram/mcp-pusher/stubs/` (monorepo: `packages/laravel-mcp-pusher/stubs/`):

| Client | Stubs directory | Destination |
|--------|-----------------|-------------|
| Cursor | `cursor-rules/` | `.cursor/rules/` |
| Claude Code | `claude-instructions/` | `.claude/rules/` (+ optional `CLAUDE.md`) |
| Antigravity | `antigravity-skills/` | `.agent/skills/` or global path above |

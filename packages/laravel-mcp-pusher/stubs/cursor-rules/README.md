# Cursor rules for MCP + Lessons Learned

Copy these into consumer projects so agents query lessons at session start and capture knowledge before compaction.

## Quick install (recommended)

From a project with `ashwinmram/mcp-pusher` installed:

```bash
php artisan mcp:install-cursor-rules
```

Optional flags:

```bash
# Overwrite existing rule files
php artisan mcp:install-cursor-rules --force

# Also install preCompact hook + capture prompt
php artisan mcp:install-cursor-rules --with-hooks

# Also create .cursorrules from cursorrules.example (if missing)
php artisan mcp:install-cursor-rules --with-cursorrules

# All of the above
php artisan mcp:install-cursor-rules --force --with-hooks --with-cursorrules
```

## Manual install

```bash
mkdir -p .cursor/rules
cp vendor/ashwinmram/mcp-pusher/stubs/cursor-rules/*.mdc .cursor/rules/
# optional short pointer in project root:
cp vendor/ashwinmram/mcp-pusher/stubs/cursor-rules/cursorrules.example .cursorrules
```

**Monorepo** (path inside mcp-server): use `packages/laravel-mcp-pusher/stubs/cursor-rules/` instead of `vendor/…`.

## What gets installed

| File | Purpose |
|------|---------|
| `mcp-session-startup.mdc` | **Always apply** — read lessons overview, query lessons, use Project Details MCP |
| `mcp-session-capture.mdc` | **On demand** — end-of-session `mcp:append` + `mcp:push` workflow |
| `cursorrules.example` | Optional short index (copy to `.cursorrules` if you use it) |

## Team sharing

Many projects gitignore `.cursor/`. To share rules with the team, either:

- Stop ignoring `.cursor/rules/` in `.gitignore`, or
- Commit stubs via a setup script / README step that runs `mcp:install-cursor-rules` after `composer install`

## Other IDEs

| IDE | Use instead |
|-----|-------------|
| **Claude Code** | Copy `stubs/agent-instructions/mcp-session-startup.md` into `CLAUDE.md` |
| **Google Antigravity** | Copy into a skill under `~/.gemini/skills/` |

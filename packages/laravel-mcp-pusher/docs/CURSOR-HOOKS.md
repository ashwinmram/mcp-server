# Cursor hooks for mcp-pusher (first-time setup)

Optional hooks remind you to **`mcp:append`** before compaction and to **`mcp:push`** at session end. Hooks are **not required** for mcp-pusher to work.

## Prerequisites

- Cursor with **Agent Hooks** (Settings → Features → Hooks)
- `composer require ashwinmram/mcp-pusher:^3.0` in your Laravel project
- `php` on your PATH (Herd users: hooks may need the full PHP path in scripts — see troubleshooting)
- `jq` only if you customize scripts to parse hook JSON

## 1. Copy stub files into your project

Run from your **Laravel project root** (not inside `vendor/`):

**Installed via Composer:**

```bash
mkdir -p .cursor/hooks
cp vendor/ashwinmram/mcp-pusher/stubs/cursor-hooks/hooks.json.example .cursor/hooks.json
cp vendor/ashwinmram/mcp-pusher/stubs/cursor-hooks/*.sh .cursor/hooks/
chmod +x .cursor/hooks/*.sh
```

**Local path repository (this monorepo):**

```bash
mkdir -p .cursor/hooks
cp packages/laravel-mcp-pusher/stubs/cursor-hooks/hooks.json.example .cursor/hooks.json
cp packages/laravel-mcp-pusher/stubs/cursor-hooks/*.sh .cursor/hooks/
chmod +x .cursor/hooks/*.sh
```

## 2. Gitignore session drafts

Add to `.gitignore` (see `stubs/gitignore-mcp-session.example`):

```gitignore
/docs/.mcp-session/
```

## 3. What the default hooks do

| Hook | Script | Behavior |
|------|--------|----------|
| `preCompact` | `pre-compact-checkpoint.sh` | Runs `mcp:append` with a short checkpoint entry (reminder to capture learnings) |
| `stop` | `session-end-reminder.sh` | Returns a `followup_message` to review drafts, optionally run **`mcp:extract-session`** if thin, then **`mcp:push`** |

Hooks do **not** run `mcp:extract-session` automatically — extract remains a manual fallback.

## 4. Verify hooks loaded

1. Save `.cursor/hooks.json` (Cursor reloads hooks on save; restart Cursor if needed)
2. Open **Settings → Hooks** (or the **Hooks** output channel)
3. Confirm `preCompact` and `stop` entries appear
4. End an agent session or trigger compaction and check hook output

## 5. Optional Cursor rule

Copy `stubs/mcp-session-capture.mdc` to `.cursor/rules/mcp-session-capture.mdc` so agents prefer frequent `mcp:append`.

## Project vs user hooks

| Location | Cwd | Path style |
|----------|-----|------------|
| **Project** `.cursor/hooks.json` (recommended) | Project root | `.cursor/hooks/script.sh` |
| **User** `~/.cursor/hooks.json` | `~/.cursor/` | `./hooks/script.sh` |

## Troubleshooting

- **Hook never runs** — Remove `matcher` from `hooks.json` temporarily; confirm script is executable (`chmod +x`)
- **php: command not found** — Edit scripts to use full PHP path, e.g. `~/.config/herd-lite/bin/php` or `which php` from your terminal
- **preCompact never fires** — Compaction hooks depend on Cursor version/mode; still use frequent `mcp:append` manually
- **Checkpoint append fails silently** — `pre-compact-checkpoint.sh` uses `|| true` so compaction is not blocked; check `php artisan mcp:append` works in your project

## Security

Hooks execute shell commands on your machine. Review scripts before enabling. Draft files may contain code paths or snippets from `mcp:extract-session` — keep `docs/.mcp-session/` gitignored.

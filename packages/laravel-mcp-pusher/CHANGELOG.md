# Changelog

## 3.0.0 - 2026-06-04

Compaction-resilient knowledge capture: append during session, unified push at end, optional extract fallback.

### Breaking changes

- Removed `mcp:push-lessons` and `mcp:push-project-details`
- Use **`mcp:push`** to publish generic lessons and project details in one command

### Added

- **`mcp:append`** — append one knowledge entry to session draft JSONL (primary capture during coding)
- **`mcp:extract-session`** — fallback salvage from git and/or agent transcript into drafts (review before push)
- Draft paths: `docs/.mcp-session/lessons-draft.jsonl`, `docs/.mcp-session/project-details-draft.jsonl`
- `--no-truncate` on `mcp:push` for both buckets
- Cursor hook stubs; setup documented in [README.md](README.md#optional-cursor-hooks)
- **`stubs/mcp-capture-prompts.md`** — session-end capture prompts with required `title` and `summary` for each `mcp:append`

### Migration

```bash
# Before (1.x / 2.0.x on Packagist through 2.0.1)
php artisan mcp:push-lessons --source=my-app
php artisan mcp:push-project-details --source=my-app

# After (3.0.0)
php artisan mcp:push --source=my-app
```

## 2.0.1 - 2026-04-26

- Laravel 13 compatibility (`illuminate/*` ^12.0|^13.0)

## 2.0.0 - 2026-02-07

- Lessons files moved under `docs/`; `lessons_learned.json`; project details truncate after push

### Best practices

- **Frequent** `mcp:append` during the session (survives context compaction)
- **Once** `mcp:push` at end of session
- **`mcp:extract-session` only when needed** — drafts thin after compaction and append was skipped

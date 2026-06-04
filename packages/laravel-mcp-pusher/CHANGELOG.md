# Changelog

## 2.0.0 - 2026-06-04

### Breaking changes

- Removed `mcp:push-lessons` and `mcp:push-project-details`
- Use **`mcp:push`** to publish generic lessons and project details in one command

### Added

- **`mcp:append`** — append one knowledge entry to session draft JSONL (primary capture during coding)
- **`mcp:extract-session`** — fallback salvage from git and/or agent transcript into drafts (review before push)
- Draft paths: `docs/.mcp-session/lessons-draft.jsonl`, `docs/.mcp-session/project-details-draft.jsonl`
- `--no-truncate` on `mcp:push` for both buckets
- Cursor hook stubs and [docs/CURSOR-HOOKS.md](docs/CURSOR-HOOKS.md)

### Migration

```bash
# Before (1.x)
php artisan mcp:push-lessons --source=my-app
php artisan mcp:push-project-details --source=my-app

# After (2.0.0)
php artisan mcp:push --source=my-app
```

### Best practices

- **Frequent** `mcp:append` during the session (survives context compaction)
- **Once** `mcp:push` at end of session
- **`mcp:extract-session` only when needed** — drafts thin after compaction and append was skipped

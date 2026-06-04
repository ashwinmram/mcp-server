# Changelog

## [1.2.0] - 2026-06-04

Documentation release aligned with **mcp-pusher 2.0.0**. No API or database changes in this app.

### Changed

- End-of-session workflow: use **`mcp:append`** frequently during coding, **`mcp:push`** once at session end
- **`mcp:extract-session`** documented as **fallback only** when drafts are thin after compaction
- Removed references to `mcp:push-lessons` and `mcp:push-project-details` (replaced by `mcp:push`)
- Composer constraint: `ashwinmram/mcp-pusher:^2.0`

### Added

- Link to package [CURSOR-HOOKS.md](packages/laravel-mcp-pusher/docs/CURSOR-HOOKS.md) for optional compaction hooks

### Package migration (consumer Laravel projects)

```bash
composer update ashwinmram/mcp-pusher
# Replace separate push commands with:
php artisan mcp:push --source=your-project
```

See [packages/laravel-mcp-pusher/CHANGELOG.md](packages/laravel-mcp-pusher/CHANGELOG.md).

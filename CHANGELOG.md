# Changelog

## [1.2.4] - 2026-06-05

### Changed

- README: mcp-pusher **3.0-only** workflow and file layout (draft JSONL); removed legacy md/json documentation

## [1.2.3] - 2026-06-05

### Changed

- README: best practice is the inline **knowledge capture prompt** (not hand-typed `mcp:append`); links to `mcp-capture-prompts.md` removed
- Embedded mcp-pusher **3.0.4**

## [1.2.2] - 2026-06-05

### Changed

- Cursor hooks: **`preCompact` only** — shows capture prompt via `user_message`; removed `stop` session-end hook
- README: 3.0 append-first workflow and links to `mcp-capture-prompts.md` (replaces legacy md/json-only prompts)

### Added

- Embedded **mcp-pusher 3.0.2** stubs: `pre-compact-prompt.txt`, updated hook script

## [1.2.1] - 2026-06-05

### Fixed

- Generic lessons API now persists **`title`**, **`summary`**, and explicit **`subcategory`** from `POST /api/lessons` (`StoreLessonsRequest` validation + `LessonImportService`)

### Added

- Feature test: generic lesson title, summary, and subcategory from API payload
- Cursor hook stubs under `.cursor/hooks/` for this monorepo

## [1.2.0] - 2026-06-04

Documentation release aligned with **mcp-pusher 3.0.0**. No API or database changes in this app.

### Changed

- End-of-session workflow: use **`mcp:append`** frequently during coding, **`mcp:push`** once at session end
- **`mcp:extract-session`** documented as **fallback only** when drafts are thin after compaction
- Removed references to `mcp:push-lessons` and `mcp:push-project-details` (replaced by `mcp:push`)
- Composer constraint: `ashwinmram/mcp-pusher:^3.0`

### Added

- Cursor hooks setup documented in [packages/laravel-mcp-pusher/README.md](packages/laravel-mcp-pusher/README.md#optional-cursor-hooks)

### Package migration (consumer Laravel projects)

```bash
composer update ashwinmram/mcp-pusher
# Replace separate push commands with:
php artisan mcp:push --source=your-project
```

See [packages/laravel-mcp-pusher/CHANGELOG.md](packages/laravel-mcp-pusher/CHANGELOG.md).

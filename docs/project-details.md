## Project Details MCP Extension (2026-02-07)

### Overview

This app runs two MCP servers: **Lessons Learned** (generic lessons) and **Project Details** (project-specific implementation details). Project details use the same `lessons` table with `is_generic = false` and are scoped by `source_project`.

### Connection and project context

- **Project Details URL:** `/mcp/project-details?project=<source_project>`
- The `project` query param is **required**. It is read by the `BindMcpProject` middleware and bound as `app('mcp.project')` so tools do not need a project argument.
- Allowed project identifier: alphanumeric, hyphens, underscores, max 255 chars. Missing or invalid `project` returns 422 before the MCP handler runs.
- The same value must be used as `--source` when pushing project details.

### Push flow

- **Command:** `php artisan mcp:push-project-details --source=<project>`
- **Options:** `--project-details-file=` (default `docs/project-details.md`), `--project-details-json-dir=` (default `docs`)
- **API:** `POST /api/project-details` with body `{ "source_project": "...", "lessons": [ ... ] }`. Same auth (Sanctum) as `POST /api/lessons`.
- Project details are **not** validated for generic content; project-specific paths and names are allowed.
- Deduplication is **only within the same** `source_project` (by `content_hash` + `source_project`). No cross-project merge.

### File locations in this project

- **Generic lessons:** `docs/lessons-learned.md`, `docs/AI_*.json` → `mcp:push-lessons`
- **Project details:** `docs/project-details.md`, `docs/project_details.json` (or `project_details_*.json`) → `mcp:push-project-details`

### Database

- **Enum:** `lessons.type` includes `project_detail` (migration `add_project_detail_type_to_lessons_table`).
- **Scope:** `Lesson::scopeProjectDetails()` = `where('is_generic', false)`.
- Project-details import never touches generic lessons; generic import never creates or updates rows with `is_generic = false`.

### Package (laravel-mcp-pusher)

- Local package lives at `packages/laravel-mcp-pusher`. The app uses it via a Composer path repository (`repositories` + `@dev`) so the `pushProjectDetails` method and `mcp:push-project-details` command are available.

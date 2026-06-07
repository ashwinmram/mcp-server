# Changelog

## [1.6.0] - 2026-06-07

### Added

- Document multi-IDE agent instruction install commands in README

### Changed

- Embedded mcp-pusher **3.0.12** with Claude Code and Google Antigravity install commands

## [1.5.0] - 2026-06-07

### Added

- Document `mcp:install-cursor-rules` in README for one-command Cursor rule setup in consumer projects

### Changed

- Slim `.cursorrules` to a short index pointing at `.cursor/rules/` (detailed MCP instructions moved to installable rule stubs)
- Embedded mcp-pusher **3.0.11** with `mcp:install-cursor-rules` command and `stubs/cursor-rules/` templates

## [1.4.0] - 2026-06-07

### Added

- Dashboard stats with 30-day period-over-period comparisons for knowledge base and project details
- `DashboardStatsService` aggregating lesson counts, retrievals, helpfulness rate, and per-source-project metrics
- `DashboardController` and `DashboardStatsSection.vue` reusable stat card component
- Per-source-project stat cards (one card per distinct `source_project`)
- Dashboard i18n keys (`dashboard.*`) and TypeScript types for stat props
- Feature tests for dashboard stats structure, seeded values, and empty state

### Changed

- Replaced dashboard placeholder UI with live stats from the database
- Dashboard route now uses `DashboardController` instead of an inline closure

## [1.3.0] - 2026-06-07

### Added

- Public landing page with MCP feature cards and YouTube explainer video embed
- Public `/documentation` page with MCP setup and usage content
- Shared `MarketingSiteHeader` for guest marketing pages
- `config/landing.php` with hardcoded explainer video ID (`PNt151KVCO0`)
- MCP Server logo (`public/logo.png`) and favicon (`public/favicon.png`)
- Feature tests: `WelcomeTest`, `DocumentationTest`

### Changed

- App branding defaults to **Lessons Learned MCP Server** (`APP_NAME`, page title, shared Inertia `name` prop)
- Sidebar logo label displays **MCP Server**
- Auth pages (Login, Register, password reset, verify email, 2FA) use Credit Tracker–style layout with horizontal logo and `LoaderCircle` submit spinners
- GitHub links point to [ashwinmram/mcp-server](https://github.com/ashwinmram/mcp-server)
- README: **Explainer video** section with clickable YouTube thumbnail

## [1.2.8] - 2026-06-06

### Changed

- Embedded mcp-pusher **3.0.10** with short text-replacement-friendly capture prompt

## [1.2.7] - 2026-06-06

### Changed

- Embedded mcp-pusher **3.0.8** with consolidated `knowledge-capture-prompt.txt` stub (git-informed capture)
- Root README, `.cursor/hooks`, and `.cursorrules` updated for single-stub workflow (link, don't duplicate)

## [1.2.6] - 2026-06-05

### Changed

- Embedded mcp-pusher **3.0.7**: `mcp:extract-session` is git-only (default `HEAD~1`); transcript extraction removed

## [1.2.5] - 2026-06-05

### Added

- README: **Configuring your AI client** for Cursor, Claude Code, and Google Antigravity
- Package stubs for per-client MCP config and IDE-neutral agent startup instructions

### Changed

- `mcp:generate-token` default name is `mcp-client-token`; CLI output is client-agnostic
- `mcp:list-tokens` filters tokens by `mcp` in the name (includes legacy `cursor-mcp-*` names)
- Embedded mcp-pusher **3.0.6**

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

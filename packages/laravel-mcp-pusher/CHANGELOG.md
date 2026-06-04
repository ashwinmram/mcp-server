# Changelog

## 3.0.6 - 2026-06-05

### Added

- **Multi-IDE docs:** Cursor, Claude Code, and Google Antigravity MCP setup in README
- **`stubs/mcp-client-config/`** — example configs per client (`cursor-mcp.json`, `claude-mcp.json`, `antigravity-mcp_config.json`)
- **`stubs/agent-instructions/mcp-session-startup.md`** — IDE-neutral agent startup instructions (copy into CLAUDE.md, Antigravity skills, etc.)

### Changed

- Capture workflow documented for all IDEs; Cursor `preCompact` hook moved under **Optional automation**
- Stubs `mcp-capture-prompts.md` and `mcp-session-capture.mdc` — wording applies to any agent, not Cursor only

## 3.0.5 - 2026-06-05

### Changed

- README is **3.0-only**: session drafts under `docs/.mcp-session/`; removed legacy `lessons-learned.md` / JSON file layout from docs
- **What's new in 3.0** section at top; capture prompt no longer references legacy files

## 3.0.4 - 2026-06-05

### Changed

- README documents a single **Knowledge capture prompt** as best practice (paste to agent or via `preCompact` hook); manual `mcp:append` JSON demoted to advanced/reference
- Removed README links to `mcp-capture-prompts.md`; `pre-compact-prompt.txt` aligned with the canonical prompt text

## 3.0.3 - 2026-06-05

### Changed

- Capture prompts (`pre-compact-prompt.txt`, `mcp-capture-prompts.md`) now require **`mcp:append` to draft JSONL only** — no longer instruct agents to update legacy `docs/*.md` / `docs/*.json` during capture (avoids duplicate lessons at push)
- Prompts include explicit `knowledge_scope` and full `mcp:append` examples per step

## 3.0.2 - 2026-06-05

### Changed

- **`preCompact` hook** now emits `user_message` with full capture prompt (from `pre-compact-prompt.txt`) instead of silent `mcp:append` checkpoint
- **Removed `stop` hook** and `session-end-reminder.sh` — `followup_message` fired too often on agent loop end; use manual session-end prompt in `mcp-capture-prompts.md`

### Added

- **`stubs/pre-compact-prompt.txt`** — single source for hook `user_message` text
- **`mcp-capture-prompts.md`** — preCompact, combined, and session-end (manual) sections with legacy four-file update step

## 3.0.1 - 2026-06-05

Documentation and capture prompts for Cursor hooks (no command or API changes).

### Changed

- Consolidated Cursor hooks setup into [README.md](README.md#optional-cursor-hooks); removed `docs/CURSOR-HOOKS.md`

### Added

- **`stubs/mcp-capture-prompts.md`** — session-end prompts with required `title` and `summary` for each `mcp:append`

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

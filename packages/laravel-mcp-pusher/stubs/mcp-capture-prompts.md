# MCP capture prompts (copy into Cursor)

Use these when Cursor shows a **preCompact** reminder, or copy manually at session end. **Every entry must include a non-empty `title`** (5–12 words, specific, searchable) and a **`summary`** (1–2 sentences: problem + approach + when to use).

Required fields on each `mcp:append` payload: `title`, `summary`, `category`, `subcategory`, `type`, `tags` (array), `content`.

Full prompt file for hooks: `stubs/pre-compact-prompt.txt` (Composer: `vendor/ashwinmram/mcp-pusher/stubs/pre-compact-prompt.txt`; monorepo: `packages/laravel-mcp-pusher/stubs/pre-compact-prompt.txt`).

---

## preCompact (before context loss)

Used by the optional `preCompact` Cursor hook (`user_message`). Submit this to your agent when Cursor shows it at compaction.

```text
Context is about to compact — capture session knowledge NOW before it is lost.

For EACH distinct learning, run php artisan mcp:append with a complete JSON object. Execute the commands; do not only describe entries.

Field rules (every entry):
- title: REQUIRED. 5–12 words, specific, AI-searchable. Never empty.
- summary: REQUIRED. Problem + approach + when a future AI should use this (1–2 sentences).
- category / subcategory: REQUIRED strings (e.g. mcp-development, cursor-hooks, workflow).
- type: "ai_output" (generic) or "project_detail" (project).
- tags: JSON array, 2–5 keywords.
- content: Full explanation.
- knowledge_scope: "generic" OR "project".

Step A — Generic: one mcp:append per cross-project reusable lesson.
Step B — Project: one mcp:append per fact specific to this repository (paths, env, MCP URLs, conventions).

Also update legacy docs (merged at mcp:push):
- docs/lessons-learned.md and docs/lessons_learned.json (generic)
- docs/project-details.md and docs/project_details.json (project)
JSON objects: field order title, summary, category, subcategory, type, tags, content, metadata.

When finished, report: generic count, project count, every title appended, and confirm legacy files were updated.
```

---

## Combined (generic + project)

Same as preCompact — use when capturing manually without the hook.

```text
Capture session knowledge NOW before context is lost.

For EACH distinct learning, run php artisan mcp:append with a complete JSON object. Do not only describe entries—execute the commands.

Field rules (every entry):
- title: REQUIRED. Short, specific, AI-searchable. Never omit or leave empty.
- summary: REQUIRED. States the problem, the approach, and trigger keywords so a future agent can decide relevance.
- category / subcategory: REQUIRED strings (use mcp-development, cursor-hooks, workflow, documentation, etc.).
- type: "ai_output" for generic lessons; "project_detail" for repo-specific facts.
- tags: JSON array of 2–5 lowercase keywords.
- content: Detailed explanation; code paths and examples allowed for project scope.
- knowledge_scope: "generic" OR "project" (or use type project_detail for project).

Step A — Generic: one mcp:append per cross-project reusable lesson.
Step B — Project: one mcp:append per fact specific to this repository (paths, env, package layout, MCP URLs).

Also update legacy docs (merged at mcp:push):
- docs/lessons-learned.md and docs/lessons_learned.json (generic)
- docs/project-details.md and docs/project_details.json (project)
JSON objects: field order title, summary, category, subcategory, type, tags, content, metadata.

When finished, report: generic count, project count, every title you appended, and confirm legacy files were updated.
```

---

## Session end (manual only)

**Not** wired to a Cursor hook (the old `stop` hook was removed — it fired too often). Copy this when you are ready to publish.

```text
Session ending: review docs/.mcp-session/lessons-draft.jsonl and docs/.mcp-session/project-details-draft.jsonl. If drafts are thin, run: php artisan mcp:extract-session --since-git=main (fallback only). Then publish once: php artisan mcp:push --source=<your-project>
```

Replace `<your-project>` with your `--source` value (e.g. `mcp-server`). Legacy `docs/*.md` and `docs/*.json` files are merged into the push automatically when present.

---

## Generic only

```text
Capture reusable lessons from this session. For EACH lesson, run:

php artisan mcp:append '{"knowledge_scope":"generic","title":"<REQUIRED: 5-12 word specific title>","summary":"<REQUIRED: problem + when to use>","category":"<category>","subcategory":"<subcategory>","type":"ai_output","tags":["tag1","tag2"],"content":"<detailed lesson>","metadata":{"source":"agent"}}'

Rules: title and summary are mandatory and must stand alone for search. One append per lesson. Skip trivial chatter.
```

---

## Project only

```text
Capture project-specific implementation details for this repo. For EACH detail, run:

php artisan mcp:append '{"knowledge_scope":"project","title":"<REQUIRED: 5-12 word specific title>","summary":"<REQUIRED: problem + when to use>","category":"<category>","subcategory":"<subcategory>","type":"project_detail","tags":["tag1","tag2"],"content":"<paths, conventions, commands for THIS repo>","metadata":{"source":"agent"}}'

Rules: title and summary are mandatory. Content must reference this codebase, not generic Laravel advice. One append per detail.
```

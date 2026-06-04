# MCP capture prompts (copy into your agent)

Use **mcp:append** frequently during the session (primary). Drafts live in `docs/.mcp-session/lessons-draft.jsonl` and `project-details-draft.jsonl`. **Do not** update `lessons-learned.md`, `lessons_learned.json`, `project-details.md`, or `project_details.json` during capture — those legacy files are optional and merging them with drafts at `mcp:push` can duplicate lessons on the server.

**Every entry must include** non-empty `title` (5–12 words) and `summary` (1–2 sentences), plus **`knowledge_scope`**: `"generic"` or `"project"`.

Hook prompt file: `stubs/pre-compact-prompt.txt` (Composer: `vendor/ashwinmram/mcp-pusher/stubs/pre-compact-prompt.txt`).

---

## preCompact (before context loss)

Shown by the optional Cursor `preCompact` hook (`user_message`). Submit to your agent when your IDE displays it, or paste manually in Claude Code / Antigravity.

```text
Context is about to compact — capture session knowledge NOW before it is lost.

Use php artisan mcp:append only (docs/.mcp-session/*-draft.jsonl). Do NOT edit docs/lessons-learned.md, lessons_learned.json, project-details.md, or project_details.json during capture.

For EACH distinct learning, run mcp:append with complete JSON. Execute commands; do not only describe entries.

Required on every entry: knowledge_scope ("generic"|"project"), title, summary, category, subcategory, type (ai_output|project_detail), tags (array), content.

Step A — Generic example:
php artisan mcp:append '{"knowledge_scope":"generic","title":"...","summary":"...","category":"...","subcategory":"...","type":"ai_output","tags":["..."],"content":"...","metadata":{"source":"agent"}}'

Step B — Project example:
php artisan mcp:append '{"knowledge_scope":"project","title":"...","summary":"...","category":"...","subcategory":"...","type":"project_detail","tags":["..."],"content":"...","metadata":{"source":"agent"}}'

Report: generic count, project count, every title appended.
```

---

## Combined (generic + project)

Manual capture anytime (same rules as preCompact).

```text
Capture session knowledge from this session. Use mcp:append only — do NOT update legacy docs/*.md or docs/*.json.

For EACH lesson, run php artisan mcp:append with knowledge_scope, title, summary, category, subcategory, type, tags, content. One append per distinct learning.

Generic: knowledge_scope "generic", type "ai_output"
Project: knowledge_scope "project", type "project_detail"

When finished, report counts and every title appended.
```

---

## Session end (manual only)

Not wired to any hook. Run when you are ready to publish (all IDEs).

```text
Session ending: review docs/.mcp-session/lessons-draft.jsonl and docs/.mcp-session/project-details-draft.jsonl. If drafts are thin, run: php artisan mcp:extract-session --since-git=main (fallback only). Then publish once: php artisan mcp:push --source=<your-project>
```

---

## Generic only

```text
Capture reusable lessons. For EACH lesson, run:

php artisan mcp:append '{"knowledge_scope":"generic","title":"<REQUIRED: 5-12 word specific title>","summary":"<REQUIRED: problem + when to use>","category":"<category>","subcategory":"<subcategory>","type":"ai_output","tags":["tag1","tag2"],"content":"<detailed lesson>","metadata":{"source":"agent"}}'

Do not update lessons-learned.md or lessons_learned.json unless you intentionally maintain a separate human-readable archive.
```

---

## Project only

```text
Capture project-specific details for this repo. For EACH detail, run:

php artisan mcp:append '{"knowledge_scope":"project","title":"<REQUIRED: 5-12 word specific title>","summary":"<REQUIRED: problem + when to use>","category":"<category>","subcategory":"<subcategory>","type":"project_detail","tags":["tag1","tag2"],"content":"<paths, conventions, commands for THIS repo>","metadata":{"source":"agent"}}'

Do not update project-details.md or project_details.json unless you intentionally maintain a separate human-readable archive.
```

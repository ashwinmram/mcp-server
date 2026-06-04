# MCP capture prompts (copy into Cursor)

Use these before compaction or at session end. **Every entry must include a non-empty `title`** (5–12 words, specific, searchable) and a **`summary`** (1–2 sentences: what problem this solves + when an AI should use it).

Required fields on each `mcp:append` payload: `title`, `summary`, `category`, `subcategory`, `type`, `tags` (array), `content`.

---

## Combined (generic + project)

```text
Capture session knowledge NOW before context is lost.

For EACH distinct learning, run php artisan mcp:append with a complete JSON object. Do not only describe entries—execute the commands.

Field rules (every entry):
- title: REQUIRED. Short, specific, AI-searchable (e.g. "Cursor preCompact hooks need agent_message not bash LLM"). Never omit or leave empty.
- summary: REQUIRED. States the problem, the approach, and trigger keywords so a future agent can decide relevance.
- category / subcategory: REQUIRED strings (use mcp-development, cursor-hooks, workflow, documentation, etc.).
- type: "ai_output" for generic lessons; "project_detail" for repo-specific facts.
- tags: JSON array of 2–5 lowercase keywords.
- content: Detailed explanation; code paths and examples allowed for project scope.
- knowledge_scope: "generic" OR "project" (or use type project_detail for project).

Step A — Generic: one mcp:append per cross-project reusable lesson.
Step B — Project: one mcp:append per fact specific to this repository (paths, env, package layout, MCP URLs).

When finished, report: generic count, project count, and every title you appended.
```

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

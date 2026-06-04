#!/usr/bin/env bash
set -euo pipefail

GENERIC_DRAFT="docs/.mcp-session/lessons-draft.jsonl"
PROJECT_DRAFT="docs/.mcp-session/project-details-draft.jsonl"
SOURCE="$(basename "$(pwd)")"

echo "{
  \"followup_message\": \"Session ending: review ${GENERIC_DRAFT} and ${PROJECT_DRAFT}. If drafts are thin, run: php artisan mcp:extract-session --since-git=main (fallback only). Then publish once: php artisan mcp:push --source=${SOURCE}\"
}"

exit 0

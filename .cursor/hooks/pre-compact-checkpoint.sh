#!/usr/bin/env bash
set -euo pipefail

# preCompact hook: suggest knowledge-capture prompt via user_message (Cursor docs).
# Consumes stdin JSON from Cursor; does not block compaction.

cat >/dev/null

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROMPT_FILE="${SCRIPT_DIR}/pre-compact-prompt.txt"

if [[ ! -f "${PROMPT_FILE}" ]]; then
  PROMPT_FILE="${SCRIPT_DIR}/../pre-compact-prompt.txt"
fi

if [[ ! -f "${PROMPT_FILE}" ]]; then
  echo '{"user_message":"Context is about to compact. Run mcp:append for each lesson (see vendor/ashwinmram/mcp-pusher/stubs/mcp-capture-prompts.md)."}'
  exit 0
fi

PROMPT="$(cat "${PROMPT_FILE}")"

if command -v jq >/dev/null 2>&1; then
  jq -n --arg msg "${PROMPT}" '{user_message: $msg}'
else
  python3 -c 'import json,sys; print(json.dumps({"user_message": sys.stdin.read()}))' <<<"${PROMPT}"
fi

exit 0

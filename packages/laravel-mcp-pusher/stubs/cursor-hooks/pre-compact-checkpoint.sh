#!/usr/bin/env bash
set -euo pipefail

# preCompact hook: suggest knowledge-capture prompt via user_message (Cursor docs).
# Consumes stdin JSON from Cursor; does not block compaction.

cat >/dev/null

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROMPT_FILE="${SCRIPT_DIR}/../knowledge-capture-prompt.txt"

if [[ ! -f "${PROMPT_FILE}" ]]; then
  echo '{"user_message":"Context is about to compact. Open vendor/ashwinmram/mcp-pusher/stubs/knowledge-capture-prompt.txt and paste it to your agent."}'
  exit 0
fi

PROMPT="$(cat "${PROMPT_FILE}")"

if command -v jq >/dev/null 2>&1; then
  jq -n --arg msg "${PROMPT}" '{user_message: $msg}'
else
  python3 -c 'import json,sys; print(json.dumps({"user_message": sys.stdin.read()}))' <<<"${PROMPT}"
fi

exit 0

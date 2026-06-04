#!/usr/bin/env bash
set -euo pipefail

# Append a checkpoint reminder before Cursor compacts context.
# Requires: php, project root as cwd (project hooks).

php artisan mcp:append "$(cat <<'EOF'
{"knowledge_scope":"generic","title":"Compaction checkpoint","summary":"Cursor is about to compact context; capture open learnings with mcp:append.","category":"development-workflow","subcategory":"compaction","type":"ai_output","tags":["mcp-pusher","compaction"],"content":"Compaction imminent. Run mcp:append for any unresolved learnings before continuing.","metadata":{"source":"hook","captured_at":""}}
EOF
)" 2>/dev/null || true

exit 0

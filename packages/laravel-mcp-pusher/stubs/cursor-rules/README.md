# Cursor rules for MCP + Lessons Learned

See [`../agent-instructions/README.md`](../agent-instructions/README.md) for install commands for **Cursor**, **Claude Code**, and **Google Antigravity**.

## Quick install (Cursor)

```bash
php artisan mcp:install-cursor-rules
```

## Manual install

```bash
mkdir -p .cursor/rules
cp vendor/ashwinmram/mcp-pusher/stubs/cursor-rules/*.mdc .cursor/rules/
```

**Monorepo:** use `packages/laravel-mcp-pusher/stubs/cursor-rules/` instead of `vendor/…`.

## Team sharing

Stop ignoring `.cursor/rules/` in `.gitignore`, or run `mcp:install-cursor-rules` after `composer install`.

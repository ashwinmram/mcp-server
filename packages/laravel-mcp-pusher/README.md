# Laravel MCP Pusher

Laravel package to push lessons learned from local projects to a central MCP server.

## Installation

Add the package to your local Laravel project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../mcp-server/packages/laravel-mcp-pusher"
        }
    ],
    "require": {
        "laravel-mcp/mcp-pusher": "*"
    }
}
```

Then run:

```bash
composer require laravel-mcp/mcp-pusher
```

## Configuration

Add MCP configuration to your project's `config/services.php`:

```php
'mcp' => [
    'server_url' => env('MCP_SERVER_URL'),
    'api_token' => env('MCP_API_TOKEN'),
],
```

Add environment variables to your `.env`:

```
MCP_SERVER_URL=https://your-mcp-server.com
MCP_API_TOKEN=your-api-token-here
```

## Usage

Push lessons from your project:

```bash
php artisan mcp:push-lessons
```

### Options

- `--source=project-name` - Override the source project name (default: directory name)
- `--cursorrules=path` - Path to .cursorrules file (default: project root)
- `--ai-json-dir=path` - Directory containing AI_*.json files (default: docs)

## How It Works

The command reads:
1. `.cursorrules` file from project root (if exists)
2. All `AI_*.json` files from `docs/` directory (or specified directory)

These files are normalized and pushed to the MCP server via HTTP API.

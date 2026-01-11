# Laravel MCP Pusher

Laravel package to push lessons learned from local projects to a central MCP server via HTTP API.

**Important**: This package pushes lessons to a **remote MCP server** via HTTP API. It does **not** interact with local database or include Lesson model classes. The remote MCP server handles storage and database operations.

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
- `--lessons-learned=path` - Path to lessons-learned.md file (default: project root)
- `--ai-json-dir=path` - Directory containing AI_*.json files (default: docs)

## How It Works

The command reads and normalizes lessons from:
1. `lessons-learned.md` file from project root (if exists)
2. All `AI_*.json` files from `docs/` directory (or specified directory)

### Normalization

The package automatically normalizes lessons by extracting categories and tags:

- **`lessons-learned.md` files**: Automatically categorized as `guidelines` with tags: `laravel`, `lessons-learned`, `guidelines`, `best-practices`, `markdown`
- **`AI_*.json` files**: 
  - Category extracted from filename (e.g., `AI_testing_config.json` → category: `testing-config`)
  - Base tags generated from filename parts (e.g., `testing`, `config`, `laravel`)
  - Additional tags extracted from content keywords (e.g., `pest`, `phpunit`, `facades`)

### Examples

**Filename-based categorization:**
- `AI_testing_config.json` → category: `testing-config`, tags: `['testing', 'config', 'laravel']`
- `AI_package_development.json` → category: `package-development`, tags: `['package', 'development', 'laravel', 'package-development']`

**Content-based tag extraction:**
- Content containing "HTTP::fake" → adds tag: `http-mocking`
- Content containing "Pest" → adds tag: `pest`
- Content containing "service provider" → adds tag: `service-provider`

### HTTP API Push

Normalized lessons are pushed to the remote MCP server via HTTP POST request to `/api/lessons` endpoint. The remote server handles:
- Database storage
- Deduplication (by content hash)
- Validation
- Lesson model management

**Note**: This package does **not** include Lesson model classes or interact with local databases. It is designed to push to a remote MCP server endpoint only.

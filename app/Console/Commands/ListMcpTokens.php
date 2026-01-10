<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class ListMcpTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:list-tokens
                            {--revoke= : Revoke a token by ID}
                            {--revoke-all : Revoke all MCP tokens}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all Sanctum API tokens used for MCP authentication';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $revokeId = $this->option('revoke');
        $revokeAll = $this->option('revoke-all');

        if ($revokeId) {
            return $this->revokeToken($revokeId);
        }

        if ($revokeAll) {
            return $this->revokeAllTokens();
        }

        return $this->listTokens();
    }

    /**
     * List all MCP tokens.
     */
    protected function listTokens(): int
    {
        $tokens = PersonalAccessToken::query()
            ->with('tokenable')
            ->whereIn('tokenable_type', [User::class])
            ->get()
            ->filter(function ($token) {
                // Filter tokens that look like MCP tokens (optional: check name pattern)
                return $token->name && (
                    str_contains(strtolower($token->name), 'mcp') ||
                    str_contains(strtolower($token->name), 'cursor')
                );
            });

        if ($tokens->isEmpty()) {
            $this->info('No MCP tokens found.');
            $this->newLine();
            $this->comment('Generate a new token with: php artisan mcp:generate-token');
            return Command::SUCCESS;
        }

        $this->info('MCP API Tokens:');
        $this->newLine();

        $headers = ['ID', 'Name', 'User', 'Last Used', 'Expires At', 'Created At'];
        $rows = [];

        foreach ($tokens as $token) {
            $user = $token->tokenable;
            $rows[] = [
                $token->id,
                $token->name,
                $user ? $user->email : 'N/A',
                $token->last_used_at ? $token->last_used_at->format('Y-m-d H:i:s') : 'Never',
                $token->expires_at ? $token->expires_at->format('Y-m-d H:i:s') : 'Never',
                $token->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
        $this->comment('To revoke a token: php artisan mcp:list-tokens --revoke=<ID>');
        $this->comment('To revoke all tokens: php artisan mcp:list-tokens --revoke-all');

        return Command::SUCCESS;
    }

    /**
     * Revoke a specific token.
     */
    protected function revokeToken(string $id): int
    {
        $token = PersonalAccessToken::find($id);

        if (! $token) {
            $this->error("Token with ID {$id} not found.");
            return Command::FAILURE;
        }

        if ($this->confirm("Are you sure you want to revoke token '{$token->name}' (ID: {$id})?", true)) {
            $token->delete();
            $this->info("✓ Token '{$token->name}' has been revoked.");
            return Command::SUCCESS;
        }

        $this->info('Token revocation cancelled.');
        return Command::SUCCESS;
    }

    /**
     * Revoke all MCP tokens.
     */
    protected function revokeAllTokens(): int
    {
        $tokens = PersonalAccessToken::query()
            ->whereIn('tokenable_type', [User::class])
            ->get()
            ->filter(function ($token) {
                return $token->name && (
                    str_contains(strtolower($token->name), 'mcp') ||
                    str_contains(strtolower($token->name), 'cursor')
                );
            });

        if ($tokens->isEmpty()) {
            $this->info('No MCP tokens found to revoke.');
            return Command::SUCCESS;
        }

        $count = $tokens->count();
        $this->warn("This will revoke {$count} MCP token(s).");

        if ($this->confirm('Are you sure you want to revoke all MCP tokens?', false)) {
            foreach ($tokens as $token) {
                $token->delete();
            }
            $this->info("✓ Revoked {$count} MCP token(s).");
            return Command::SUCCESS;
        }

        $this->info('Token revocation cancelled.');
        return Command::SUCCESS;
    }
}

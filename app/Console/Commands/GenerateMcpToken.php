<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateMcpToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:generate-token
                            {--name=cursor-mcp-token : The name for the token}
                            {--email= : Email for the user (creates user if not exists)}
                            {--force : Regenerate token if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a Sanctum API token for MCP server authentication';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tokenName = $this->option('name');
        $email = $this->option('email');

        // Get or create user
        if ($email) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'MCP Server User',
                    'password' => bcrypt(Str::random(32)), // Random password for API-only user
                ]
            );
        } else {
            // Use first user or create a default one
            $user = User::first();
            if (! $user) {
                $user = User::factory()->create([
                    'name' => 'MCP Server User',
                    'email' => 'mcp@example.com',
                ]);
                $this->info('Created default user: '.$user->email);
            }
        }

        // Check if token already exists
        $existingToken = $user->tokens()->where('name', $tokenName)->first();

        if ($existingToken && ! $this->option('force')) {
            $this->warn("Token '{$tokenName}' already exists for user {$user->email}.");
            if (! $this->confirm('Do you want to regenerate it? This will invalidate the existing token.', false)) {
                $this->info('Existing token: '.$existingToken->token);
                return Command::SUCCESS;
            }

            // Revoke existing token
            $existingToken->delete();
        } elseif ($existingToken && $this->option('force')) {
            $existingToken->delete();
        }

        // Create new token
        $token = $user->createToken($tokenName, ['*']);

        $this->newLine();
        $this->info('✓ Token generated successfully!');
        $this->newLine();

        // Display token in a highlighted box
        $this->line('┌─────────────────────────────────────────────────────────┐');
        $this->line('│  Token Name: '.str_pad($tokenName, 44).'│');
        $this->line('│  User: '.str_pad($user->email, 51).'│');
        $this->line('├─────────────────────────────────────────────────────────┤');
        $this->line('│  API Token (copy this):                                │');
        $this->line('│                                                         │');
        $this->line('│  '.str_pad($token->plainTextToken, 55).'│');
        $this->line('│                                                         │');
        $this->line('└─────────────────────────────────────────────────────────┘');
        $this->newLine();

        $this->info('Add this to your .env file:');
        $this->line('MCP_API_TOKEN='.$token->plainTextToken);
        $this->newLine();

        $this->info('Use this token in Cursor MCP configuration:');
        $this->line('Authorization: Bearer '.$token->plainTextToken);
        $this->newLine();

        return Command::SUCCESS;
    }
}

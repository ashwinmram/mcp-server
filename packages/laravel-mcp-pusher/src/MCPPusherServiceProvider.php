<?php

namespace LaravelMcpPusher;

use Illuminate\Support\ServiceProvider;
use LaravelMcpPusher\Commands\AppendKnowledge;
use LaravelMcpPusher\Commands\ExtractSession;
use LaravelMcpPusher\Commands\InstallAgentInstructions;
use LaravelMcpPusher\Commands\InstallAntigravitySkills;
use LaravelMcpPusher\Commands\InstallClaudeInstructions;
use LaravelMcpPusher\Commands\InstallCursorRules;
use LaravelMcpPusher\Commands\PushKnowledge;

class MCPPusherServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/mcp-pusher.php',
            'mcp-pusher'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AppendKnowledge::class,
                PushKnowledge::class,
                ExtractSession::class,
                InstallCursorRules::class,
                InstallClaudeInstructions::class,
                InstallAntigravitySkills::class,
                InstallAgentInstructions::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/mcp-pusher.php' => config_path('mcp-pusher.php'),
            ], 'mcp-pusher-config');
        }
    }
}

<?php

namespace LaravelMcpPusher;

use Illuminate\Support\ServiceProvider;
use LaravelMcpPusher\Commands\PushLessons;

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
                PushLessons::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/mcp-pusher.php' => config_path('mcp-pusher.php'),
            ], 'mcp-pusher-config');
        }
    }
}

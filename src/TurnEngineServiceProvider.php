<?php

namespace TurnEngine\Laravel;

use Illuminate\Support\ServiceProvider;
use TurnEngine\Laravel\Console\ListGamesCommand;
use TurnEngine\Laravel\Console\PurgeGameCommand;
use TurnEngine\Laravel\Console\PurgeGamesCommand;

final class TurnEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/turn-engine.php', 'turn-engine');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ListGamesCommand::class,
                PurgeGameCommand::class,
                PurgeGamesCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/turn-engine.php' => config_path('turn-engine.php'),
        ], 'turn-engine-config');
    }
}

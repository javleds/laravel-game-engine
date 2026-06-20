<?php

namespace TurnEngine\Laravel;

use Illuminate\Support\ServiceProvider;

final class TurnEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/turn-engine.php', 'turn-engine');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/turn-engine.php' => config_path('turn-engine.php'),
        ], 'turn-engine-config');
    }
}

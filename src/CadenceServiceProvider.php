<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence;

use Illuminate\Support\ServiceProvider;

class CadenceServiceProvider extends ServiceProvider
{
    /**
     * Register the Cadence services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cadence.php', 'cadence');

        $this->app->singleton(
            CadenceManager::class,
            fn ($app) => new CadenceManager($app),
        );

        $this->app->alias(CadenceManager::class, 'cadence');
    }

    /**
     * Bootstrap the Cadence services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cadence.php' => $this->app->configPath('cadence.php'),
            ], 'cadence-config');
        }
    }
}

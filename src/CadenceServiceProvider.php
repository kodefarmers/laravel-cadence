<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Kodefarmers\Cadence\Contracts\StateRepository;
use Kodefarmers\Cadence\Repositories\CacheStateRepository;
use Kodefarmers\Cadence\ValueObjects\CadenceConfig;

class CadenceServiceProvider extends ServiceProvider
{
    /**
     * Register the Cadence services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cadence.php', 'cadence');

        $this->app->singleton(CadenceConfig::class, fn (Application $app) => new CadenceConfig(
            freeAttempts: (int) $app['config']->get('cadence.free_attempts'),
            idleTimeout: (int) $app['config']->get('cadence.idle_timeout'),
        ));

        $this->app->singleton(StateRepository::class, fn (Application $app) => new CacheStateRepository(
            cache: $app[CacheFactory::class]->store(),
            config: $app[CadenceConfig::class],
        ));

        $this->app->singleton(
            CadenceManager::class,
            fn (Application $app) => new CadenceManager($app),
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

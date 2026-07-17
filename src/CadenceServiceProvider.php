<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
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

        $this->app->singleton(CadenceConfig::class, function (Application $app): CadenceConfig {
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);

            /** @var int $freeAttempts */
            $freeAttempts = $config->get('cadence.free_attempts');

            /** @var int $idleTimeout */
            $idleTimeout = $config->get('cadence.idle_timeout');

            return new CadenceConfig(
                freeAttempts: $freeAttempts,
                idleTimeout: $idleTimeout,
            );
        });


        $this->app->singleton(StateRepository::class, function (Application $app): CacheStateRepository {
            /** @var CacheFactory $cacheFactory */
            $cacheFactory = $app->make(CacheFactory::class);

            /** @var CadenceConfig $config */
            $config = $app->make(CadenceConfig::class);

            return new CacheStateRepository(
                cache: $cacheFactory->store(),
                config: $config
            );
        });

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

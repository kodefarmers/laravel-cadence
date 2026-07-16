<?php

declare(strict_types=1);

use Kodefarmers\Cadence\CadenceManager;
use Kodefarmers\Cadence\Contracts\StateRepository;
use Kodefarmers\Cadence\Repositories\CacheStateRepository;
use Kodefarmers\Cadence\ValueObjects\CadenceConfig;

it('registers the cadence configuration', function (): void {
    expect(config('cadence'))->toBeArray();
});

it('registers the cadence config singleton', function (): void {
    $config = app(CadenceConfig::class);

    expect($config)
        ->toBeInstanceOf(CadenceConfig::class)
        ->and($config->freeAttempts)
        ->toBe(config('cadence.free_attempts'))
        ->and($config->idleTimeout)
        ->toBe(config('cadence.idle_timeout'));
});

it('registers the state repository', function (): void {
    expect(app(StateRepository::class))
        ->toBeInstanceOf(CacheStateRepository::class);
});

it('registers the cadence manager as a singleton', function (): void {
    expect(app(CadenceManager::class))
        ->toBe(app(CadenceManager::class));
});

it('registers the cadence alias', function (): void {
    expect(app('cadence'))
        ->toBe(app(CadenceManager::class));
});

it('publishes the configuration file', function (): void {
    expect(
        $this->app->providerIsLoaded(Kodefarmers\Cadence\CadenceServiceProvider::class)
    )->toBeTrue();
});

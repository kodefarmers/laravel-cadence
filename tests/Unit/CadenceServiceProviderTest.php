<?php

declare(strict_types=1);

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
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

it('uses the default cache store when no cache store is configured', function (): void {
    config()->set('cadence.cache.store', null);

    /** @var Mockery\MockInterface&CacheFactory $cache */
    $cache = Mockery::mock(CacheFactory::class);

    /** @var Mockery\Expectation $expectation */
    $expectation = $cache->shouldReceive('store');

    $expectation
        ->once()
        ->withNoArgs()
        ->andReturn(new Repository(new ArrayStore()));

    app()->instance(CacheFactory::class, $cache);
    app()->forgetInstance(StateRepository::class);

    expect(app(StateRepository::class))
        ->toBeInstanceOf(CacheStateRepository::class);
});

it('resolves the configured cache store from the cache factory', function (): void {
    config()->set('cadence.cache.store', 'redis');

    /** @var Mockery\MockInterface&CacheFactory $cache */
    $cache = Mockery::mock(CacheFactory::class);

    /** @var Mockery\Expectation $expectation */
    $expectation = $cache->shouldReceive('store');

    $expectation->once()
        ->with('redis')
        ->andReturn(new Repository(new ArrayStore()));

    app()->instance(CacheFactory::class, $cache);
    app()->forgetInstance(StateRepository::class);

    expect(app(StateRepository::class))
        ->toBeInstanceOf(CacheStateRepository::class);
});

it('supports alternative cache stores such as memcached', function (): void {
    config()->set('cadence.cache.store', 'memcached');

    /** @var Mockery\MockInterface&CacheFactory $cache */
    $cache = Mockery::mock(CacheFactory::class);

    /** @var Mockery\Expectation $expectation */
    $expectation = $cache->shouldReceive('store');

    $expectation->once()
        ->with('memcached')
        ->andReturn(new Repository(new ArrayStore()));

    app()->instance(CacheFactory::class, $cache);
    app()->forgetInstance(StateRepository::class);

    expect(app(StateRepository::class))
        ->toBeInstanceOf(CacheStateRepository::class);
});

it('registers the cadence manager as a singleton', function (): void {
    expect(app(CadenceManager::class))
        ->toBe(app(CadenceManager::class));
});

it('registers the cadence alias', function (): void {
    /** @var CadenceManager $cadence */
    $cadence = app('cadence');

    /** @var CadenceManager $manager */
    $manager = app(CadenceManager::class);

    expect($cadence)->toBe($manager);
});

it('publishes the configuration file', function (): void {
    expect(
        app()->providerIsLoaded(Kodefarmers\Cadence\CadenceServiceProvider::class)
    )->toBeTrue();
});

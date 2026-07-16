<?php

declare(strict_types=1);

use Illuminate\Config\Repository as ConfigRepository;
use Kodefarmers\Cadence\CadenceEngine;
use Kodefarmers\Cadence\CadenceManager;
use Kodefarmers\Cadence\Contracts\StateRepository;
use Kodefarmers\Cadence\Tests\Fakes\FakeStateRepository;
use Kodefarmers\Cadence\ValueObjects\CadenceConfig;

beforeEach(function (): void {
    $this->app->instance('config', new ConfigRepository([
        'cadence.default' => 'exponential',
        'cadence.free_attempts' => 3,
        'cadence.idle_timeout' => 3600,
        'cadence.drivers.exponential.base_delay' => 2,
    ]));

    $this->app->singleton(CadenceConfig::class, fn () => new CadenceConfig(
        freeAttempts: 3,
        idleTimeout: 3600,
    ));

    $this->app->singleton(StateRepository::class, fn () => new FakeStateRepository());
});

it('creates the default driver', function (): void {
    $manager = new CadenceManager($this->app);

    expect($manager->driver())
        ->toBeInstanceOf(CadenceEngine::class);
});

it('uses the configured default driver when no name is provided', function (): void {
    $manager = new CadenceManager($this->app);

    expect($manager->driver())
        ->toBe($manager->driver($manager->getDefaultDriver()));
});

it('returns the same driver instance', function (): void {
    $manager = new CadenceManager($this->app);

    expect($manager->driver())
        ->toBe($manager->driver());
});

it('creates a named driver', function (): void {
    $manager = new CadenceManager($this->app);

    expect($manager->driver('exponential'))
        ->toBeInstanceOf(CadenceEngine::class);
});

it('throws for an unknown driver', function (): void {
    $manager = new CadenceManager($this->app);

    $manager->driver('unknown');
})->throws(
    InvalidArgumentException::class,
    'Driver [unknown] not supported.'
);

it('uses the configured default driver', function (): void {
    $this->app['config']->set('cadence.default', 'exponential');

    $manager = new CadenceManager($this->app);

    expect($manager->driver())
        ->toBeInstanceOf(CadenceEngine::class);
});

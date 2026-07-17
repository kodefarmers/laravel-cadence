<?php

declare(strict_types=1);

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Kodefarmers\Cadence\Repositories\CacheStateRepository;
use Kodefarmers\Cadence\ValueObjects\CadenceConfig;

function makeRepository(): CacheStateRepository
{
    return new CacheStateRepository(
        cache: new Repository(new ArrayStore()),
        config: new CadenceConfig(
            freeAttempts: 3,
            idleTimeout: 3600
        ),
    );
}

it('returns an empty initial state', function (): void {
    $repository = makeRepository();

    $state = $repository->state('login:127.0.0.1');

    expect($state->attempts)->toBe(0)
        ->and($state->isLocked)->toBeFalse()
        ->and($state->remainingLockSeconds)->toBe(0);
});

it('increments the attempt count', function (): void {
    $repository = makeRepository();

    expect($repository->incrementAttempts('login'))
        ->toBe(1);

    expect($repository->state('login')->attempts)
        ->toBe(1);

    expect($repository->incrementAttempts('login'))
        ->toBe(2);

    expect($repository->state('login')->attempts)
        ->toBe(2);
});

it('resets the attempt count', function (): void {
    $repository = makeRepository();

    $repository->incrementAttempts('login');
    $repository->incrementAttempts('login');

    $repository->resetAttempts('login');

    expect($repository->state('login')->attempts)
        ->toBe(0);
});

it('locks a key', function (): void {
    $repository = makeRepository();

    $repository->lock('login', 10);

    $state = $repository->state('login');

    expect($state->isLocked)->toBeTrue()
        ->and($state->remainingLockSeconds)
        ->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(10);
});

it('unlocks a key', function (): void {
    $repository = makeRepository();

    $repository->lock('login', 10);

    $repository->unlock('login');

    $state = $repository->state('login');

    expect($state->isLocked)->toBeFalse()
        ->and($state->remainingLockSeconds)->toBe(0);
});

it('keeps attempts separate for different keys', function (): void {
    $repository = makeRepository();

    $repository->incrementAttempts('login');
    $repository->incrementAttempts('register');

    expect($repository->state('login')->attempts)
        ->toBe(1);

    expect($repository->state('register')->attempts)
        ->toBe(1);
});

it('keeps lock state separate for different keys', function (): void {
    $repository = makeRepository();

    $repository->lock('login', 10);

    expect($repository->state('login')->isLocked)
        ->toBeTrue();

    expect($repository->state('register')->isLocked)
        ->toBeFalse();
});

it('does not reset attempts when locking and unlocking', function (): void {
    $repository = makeRepository();

    $repository->incrementAttempts('login');
    $repository->incrementAttempts('login');

    $repository->lock('login', 10);
    $repository->unlock('login');

    expect($repository->state('login')->attempts)
        ->toBe(2);
});

it('casts string values from the cache backend to integers', function (): void {
    /** @var Repository&Mockery\MockInterface $cache */
    $cache = Mockery::mock(Repository::class);

    /** @var Mockery\Expectation $expectation */
    $expectation = $cache->shouldReceive('get');

    $expectation
        ->with('cadence:lock:login:expires_at', 0)
        ->once()
        ->andReturn('10');

    $expectation
        ->with('cadence:attempts:login', 0)
        ->once()
        ->andReturn('2');

    /** @var Mockery\Expectation $expectation */
    $expectation = $cache->shouldReceive('has');

    $expectation
        ->with('cadence:lock:login')
        ->once()
        ->andReturnFalse();

    $repository = new CacheStateRepository(
        cache: $cache,
        config: new CadenceConfig(
            freeAttempts: 3,
            idleTimeout: 3600,
        ),
    );

    $state = $repository->state('login');

    expect($state->attempts)->toBe(2)
        ->and($state->isLocked)->toBeFalse()
        ->and($state->remainingLockSeconds)->toBe(0);
});

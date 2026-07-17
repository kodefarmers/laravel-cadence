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

beforeEach(function (): void {
    $repository = makeRepository();

    $repository = new CacheStateRepository(
        cache: new Repository(new ArrayStore()),
        config: new CadenceConfig(
            freeAttempts: 3,
            idleTimeout: 3600
        ),
    );
});

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

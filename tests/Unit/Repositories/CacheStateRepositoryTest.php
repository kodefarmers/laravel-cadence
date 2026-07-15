<?php

declare(strict_types=1);

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Kodefarmers\Cadence\Repositories\CacheStateRepository;
use Kodefarmers\Cadence\ValueObjects\CadenceConfig;

beforeEach(function (): void {
    $this->repository = new CacheStateRepository(
        cache: new Repository(new ArrayStore()),
        config: new CadenceConfig(),
    );
});

it('returns an empty initial state', function (): void {
    $state = $this->repository->state('login:127.0.0.1');

    expect($state->attempts)->toBe(0)
        ->and($state->locked)->toBeFalse()
        ->and($state->remainingLockSeconds)->toBe(0);
});

it('increments the attempt count', function (): void {
    expect($this->repository->incrementAttempts('login'))
        ->toBe(1);

    expect($this->repository->state('login')->attempts)
        ->toBe(1);

    expect($this->repository->incrementAttempts('login'))
        ->toBe(2);

    expect($this->repository->state('login')->attempts)
        ->toBe(2);
});

it('resets the attempt count', function (): void {
    $this->repository->incrementAttempts('login');
    $this->repository->incrementAttempts('login');

    $this->repository->resetAttempts('login');

    expect($this->repository->state('login')->attempts)
        ->toBe(0);
});

it('locks a key', function (): void {
    $this->repository->lock('login', 10);

    $state = $this->repository->state('login');

    expect($state->locked)->toBeTrue()
        ->and($state->remainingLockSeconds)
        ->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(10);
});

it('unlocks a key', function (): void {
    $this->repository->lock('login', 10);

    $this->repository->unlock('login');

    $state = $this->repository->state('login');

    expect($state->locked)->toBeFalse()
        ->and($state->remainingLockSeconds)->toBe(0);
});

it('keeps attempts separate for different keys', function (): void {
    $this->repository->incrementAttempts('login');
    $this->repository->incrementAttempts('register');

    expect($this->repository->state('login')->attempts)
        ->toBe(1);

    expect($this->repository->state('register')->attempts)
        ->toBe(1);
});

it('keeps lock state separate for different keys', function (): void {
    $this->repository->lock('login', 10);

    expect($this->repository->state('login')->locked)
        ->toBeTrue();

    expect($this->repository->state('register')->locked)
        ->toBeFalse();
});

it('does not reset attempts when locking and unlocking', function (): void {
    $this->repository->incrementAttempts('login');
    $this->repository->incrementAttempts('login');

    $this->repository->lock('login', 10);
    $this->repository->unlock('login');

    expect($this->repository->state('login')->attempts)
        ->toBe(2);
});

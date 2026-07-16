<?php

declare(strict_types=1);

use Kodefarmers\Cadence\CadenceEngine;
use Kodefarmers\Cadence\Exceptions\CadenceLockedException;
use Kodefarmers\Cadence\Strategies\ExponentialStrategy;
use Kodefarmers\Cadence\Tests\Fakes\FakeStateRepository;
use Kodefarmers\Cadence\ValueObjects\CadenceConfig;

beforeEach(function (): void {
    $this->repository = new FakeStateRepository();

    $this->engine = new CadenceEngine(
        strategy: new ExponentialStrategy(),
        repository: $this->repository,
        config: new CadenceConfig(
            freeAttempts: 3,
            idleTimeout: 3600
        ),
    );
});

it('allows failures within the free attempt threshold', function (): void {
    expect($this->engine->recordFailure('login')->isLocked)->toBeFalse()
        ->and($this->engine->recordFailure('login')->isLocked)->toBeFalse()
        ->and($this->engine->recordFailure('login')->isLocked)->toBeFalse();

    expect($this->engine->attempts('login'))->toBe(3);
});

it('locks on the first violation', function (): void {
    $this->engine->recordFailure('login');
    $this->engine->recordFailure('login');
    $this->engine->recordFailure('login');

    $result = $this->engine->recordFailure('login');

    expect($result->attempt)->toBe(4)
        ->and($result->violationCount)->toBe(1)
        ->and($result->delay)->toBe(2)
        ->and($result->isLocked)->toBeTrue();
});

it('increases the delay for subsequent violations', function (): void {
    for ($i = 1; $i <= 5; $i++) {
        $result = $this->engine->recordFailure('login');
    }

    expect($result->attempt)->toBe(5)
        ->and($result->violationCount)->toBe(2)
        ->and($result->delay)->toBe(4)
        ->and($result->isLocked)->toBeTrue();
});

it('resets attempts and unlocks after a successful operation', function (): void {
    for ($i = 1; $i <= 4; $i++) {
        $this->engine->recordFailure('login');
    }

    $this->engine->recordSuccess('login');

    expect($this->engine->attempts('login'))->toBe(0)
        ->and($this->engine->isLocked('login'))->toBeFalse()
        ->and($this->engine->remainingBackoff('login'))->toBe(0);
});

it('continues counting failures after a temporary lock expires', function (): void {
    for ($i = 1; $i <= 4; $i++) {
        $this->engine->recordFailure('login');
    }

    $this->repository->expireLock('login');

    $result = $this->engine->recordFailure('login');

    expect($result->attempt)->toBe(5)
        ->and($result->violationCount)->toBe(2)
        ->and($result->delay)->toBe(4);
});

it('throws a cadence locked exception when the key is locked', function (): void {
    for ($i = 1; $i <= 4; $i++) {
        $this->engine->recordFailure('login');
    }

    expect(fn () => $this->engine->ensureNotLocked('login'))
        ->toThrow(CadenceLockedException::class);
});

it('throws a cadence locked exception with lock details when a key is currently locked', function (): void {
    $this->engine->recordFailure('login');
    $this->engine->recordFailure('login');
    $this->engine->recordFailure('login');
    $this->engine->recordFailure('login');

    try {
        $this->engine->ensureNotLocked('login');
    } catch (CadenceLockedException $exception) {
        expect($exception->retryAfter())->toBe(2)
            ->and($exception->attempts())->toBe(4)
            ->and($exception->violationCount())->toBe(1)
            ->and($exception->getMessage())->toBe('The key [login] is currently in backoff. Retry after 2 seconds.');

        return;
    }

    throw new Exception('Expected CadenceLockedException to be thrown.');
});

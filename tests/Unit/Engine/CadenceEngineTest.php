<?php

declare(strict_types=1);

use Kodefarmers\Cadence\CadenceEngine;
use Kodefarmers\Cadence\Exceptions\CadenceLockedException;
use Kodefarmers\Cadence\Strategies\ExponentialStrategy;
use Kodefarmers\Cadence\Tests\Fakes\FakeStateRepository;
use Kodefarmers\Cadence\ValueObjects\CadenceConfig;

/**
 * @return array{CadenceEngine, FakeStateRepository}
 */
function makeEngine(): array
{
    $repository = new FakeStateRepository();

    $engine = new CadenceEngine(
        strategy: new ExponentialStrategy(),
        repository: $repository,
        config: new CadenceConfig(
            freeAttempts: 3,
            idleTimeout: 3600,
        ),
    );

    return [$engine, $repository];
}

it('allows failures within the free attempt threshold', function (): void {
    [$engine] = makeEngine();

    expect($engine->recordFailure('login')->isLocked)->toBeFalse()
        ->and($engine->recordFailure('login')->isLocked)->toBeFalse()
        ->and($engine->recordFailure('login')->isLocked)->toBeFalse();

    expect($engine->attempts('login'))->toBe(3);
});

it('locks on the first violation', function (): void {
    [$engine] = makeEngine();

    $engine->recordFailure('login');
    $engine->recordFailure('login');
    $engine->recordFailure('login');

    $result = $engine->recordFailure('login');

    expect($result->attempt)->toBe(4)
        ->and($result->violationCount)->toBe(1)
        ->and($result->delay)->toBe(2)
        ->and($result->isLocked)->toBeTrue();
});

it('increases the delay for subsequent violations', function (): void {
    [$engine] = makeEngine();

    for ($i = 1; $i <= 5; $i++) {
        $result = $engine->recordFailure('login');
    }

    expect($result->attempt)->toBe(5)
        ->and($result->violationCount)->toBe(2)
        ->and($result->delay)->toBe(4)
        ->and($result->isLocked)->toBeTrue();
});

it('resets attempts and unlocks after a successful operation', function (): void {
    [$engine] = makeEngine();

    for ($i = 1; $i <= 4; $i++) {
        $engine->recordFailure('login');
    }

    $engine->recordSuccess('login');

    expect($engine->attempts('login'))->toBe(0)
        ->and($engine->isLocked('login'))->toBeFalse()
        ->and($engine->remainingBackoff('login'))->toBe(0);
});

it('continues counting failures after a temporary lock expires', function (): void {
    [$engine, $repository] = makeEngine();

    for ($i = 1; $i <= 4; $i++) {
        $engine->recordFailure('login');
    }

    $repository->expireLock('login');

    $result = $engine->recordFailure('login');

    expect($result->attempt)->toBe(5)
        ->and($result->violationCount)->toBe(2)
        ->and($result->delay)->toBe(4);
});

it('throws a cadence locked exception when the key is locked', function (): void {
    [$engine] = makeEngine();

    for ($i = 1; $i <= 4; $i++) {
        $engine->recordFailure('login');
    }

    expect(fn () => $engine->ensureNotLocked('login'))
        ->toThrow(CadenceLockedException::class);
});

it('throws a cadence locked exception with lock details when a key is currently locked', function (): void {
    [$engine] = makeEngine();

    $engine->recordFailure('login');
    $engine->recordFailure('login');
    $engine->recordFailure('login');
    $engine->recordFailure('login');

    try {
        $engine->ensureNotLocked('login');
    } catch (CadenceLockedException $exception) {
        expect($exception->retryAfter())->toBe(2)
            ->and($exception->attempts())->toBe(4)
            ->and($exception->violationCount())->toBe(1)
            ->and($exception->getMessage())->toBe('The key [login] is currently in backoff. Retry after 2 seconds.');

        return;
    }

    throw new Exception('Expected CadenceLockedException to be thrown.');
});

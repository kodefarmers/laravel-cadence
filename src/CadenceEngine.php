<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence;

use Kodefarmers\Cadence\Contracts\DelayStrategy;
use Kodefarmers\Cadence\Contracts\StateRepository;
use Kodefarmers\Cadence\ValueObjects\CadenceConfig;
use Kodefarmers\Cadence\ValueObjects\CadenceResult;

/**
 * Coordinates backoff state and delay calculations.
 */
final readonly class CadenceEngine
{
    public function __construct(
        private DelayStrategy $strategy,
        private StateRepository $repository,
        private CadenceConfig $config,
    ) {}

    /**
     * Record a failed attempt and apply backoff when required.
     */
    public function recordFailure(string $key): CadenceResult
    {
        $attempt = $this->repository->incrementAttempts($key);

        if ($attempt <= $this->config->freeAttempts) {
            return new CadenceResult(
                attempt: $attempt,
                violation: 0,
                delay: 0,
                isLocked: false,
            );
        }

        $violation = $attempt - $this->config->freeAttempts;

        $delay = $this->strategy->delayFor($violation);

        $this->repository->lock($key, $delay);

        return new CadenceResult(
            attempt: $attempt,
            violation: $violation,
            delay: $delay,
            isLocked: true,
        );
    }

    /**
     * Reset the backoff state for the given key.
     */
    public function recordSuccess(string $key): void
    {
        $this->repository->resetAttempts($key);
        $this->repository->unlock($key);
    }

    /**
     * Determine whether the given key is currently in backoff.
     */
    public function isLocked(string $key): bool
    {
        return $this->repository->state($key)->isLocked;
    }

    /**
     * Get the remaining backoff duration, in seconds, for the given key.
     */
    public function remainingBackoff(string $key): int
    {
        return $this->repository->state($key)->remainingLockSeconds;
    }

    /**
     * Get the current attempt count for the given key.
     */
    public function attempts(string $key): int
    {
        return $this->repository->state($key)->attempts;
    }
}

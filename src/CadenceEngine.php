<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence;

use Kodefarmers\Cadence\Contracts\DelayStrategy;
use Kodefarmers\Cadence\Contracts\StateRepository;
use Kodefarmers\Cadence\Enums\JitterType;
use Kodefarmers\Cadence\Exceptions\CadenceLockedException;
use Kodefarmers\Cadence\Strategies\JitterStrategy;
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
                violationCount: 0,
                delay: 0,
                isLocked: false,
            );
        }

        $violation = $attempt - $this->config->freeAttempts;

        $delay = $this->strategy->delayFor($violation);

        $this->repository->lock($key, $delay);

        return new CadenceResult(
            attempt: $attempt,
            violationCount: $violation,
            delay: $delay,
            isLocked: true,
        );
    }

    /**
     * Return a new engine that applies jitter to the configured delay strategy.
     *
     * Jitter decorates the current delay strategy by introducing controlled
     * randomness to the calculated delay. This helps distribute retries over time
     * and reduces synchronized retry bursts.
     *
     * If the strategy is already wrapped with jitter, the existing decorator is
     * replaced with one using the specified jitter algorithm.
     *
     * @param  JitterType  $type  The jitter algorithm to apply.
     * @return self A new engine instance with the configured jitter applied.
     */
    public function jitter(
        JitterType $type = JitterType::FULL,
    ): self {
        $strategy = $this->strategy instanceof JitterStrategy
            ? $this->strategy->strategy()
            : $this->strategy;

        return new self(
            strategy: new JitterStrategy($strategy, $type),
            repository: $this->repository,
            config: $this->config,
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
     * Ensure the given key is not currently in backoff.
     *
     * @throws CadenceLockedException
     */
    public function ensureNotLocked(string $key): void
    {
        $state = $this->repository->state($key);

        if ($state->isLocked) {
            throw new CadenceLockedException(
                key: $key,
                retryAfter: $state->remainingLockSeconds,
                attempts: $state->attempts,
                violationCount: max(0, $state->attempts - $this->config->freeAttempts),
            );
        }
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

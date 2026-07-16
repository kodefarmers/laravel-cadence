<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Exceptions;

/**
 * Thrown when an operation is attempted while the key is in a backoff period.
 */
final class CadenceLockedException extends CadenceException
{
    /**
     * Create a new locked exception instance.
     */
    public function __construct(
        private readonly string $key,
        private readonly int $retryAfter,
        private readonly int $attempts,
        private readonly int $violationCount,
    ) {
        parent::__construct(
            sprintf(
                'The key [%s] is currently in backoff. Retry after %d seconds.',
                $this->key,
                $this->retryAfter,
            ),
        );
    }

    /**
     * Get the key that is currently in backoff.
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * Get the number of seconds remaining until the key is unlocked.
     */
    public function retryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Get the total number of recorded attempts.
     */
    public function attempts(): int
    {
        return $this->attempts;
    }

    /**
     * Get the current backoff violation count.
     */
    public function violationCount(): int
    {
        return $this->violationCount;
    }
}

<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\ValueObjects;

/**
 * Represents the backoff state for a given key.
 */
final readonly class State
{
    /**
     * Create a new state instance.
     *
     * @param  int  $attempts  The number of recorded attempts.
     * @param  bool  $locked  Whether the key is currently in a backoff period.
     * @param  int  $remainingLockSeconds  The number of seconds remaining until the backoff expires.
     */
    public function __construct(
        public int $attempts,
        public bool $locked,
        public int $remainingLockSeconds,
    ) {}
}

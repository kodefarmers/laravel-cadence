<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Contracts;

use Kodefarmers\Cadence\ValueObjects\State;

/**
 * Defines how Cadence state is persisted and retrieved.
 */
interface StateRepository
{
    /**
     * Retrieve the current state for the given key.
     */
    public function state(string $key): State;

    /**
     * Increment and return the attempt count for the given key.
     */
    public function incrementAttempts(string $key): int;

    /**
     * Reset the attempt count for the given key.
     */
    public function resetAttempts(string $key): void;

    /**
     * Lock the given key for the specified number of seconds.
     */
    public function lock(string $key, int $seconds): void;

    /**
     * Remove the active lock for the given key.
     */
    public function unlock(string $key): void;
}

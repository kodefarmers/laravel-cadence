<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Tests\Fakes;

use Kodefarmers\Cadence\Contracts\StateRepository;
use Kodefarmers\Cadence\ValueObjects\State;

/**
 * A fake state repository for testing.
 */
final class FakeStateRepository implements StateRepository
{
    /**
     * @var array<string, int>
     */
    private array $attempts = [];

    /**
     * @var array<string, int>
     */
    private array $locks = [];

    /**
     * Retrieve the current state for the given key.
     */
    public function state(string $key): State
    {
        return new State(
            attempts: $this->attempts[$key] ?? 0,
            isLocked: isset($this->locks[$key]),
            remainingLockSeconds: $this->locks[$key] ?? 0,
        );
    }

    /**
     * Increment and return the attempt count for the given key.
     */
    public function incrementAttempts(string $key): int
    {
        return $this->attempts[$key] = ($this->attempts[$key] ?? 0) + 1;
    }

    /**
     * Reset the attempt count for the given key.
     */
    public function resetAttempts(string $key): void
    {
        unset($this->attempts[$key]);
    }

    /**
     * Place the given key into backoff for the specified number of seconds.
     */
    public function lock(string $key, int $seconds): void
    {
        $this->locks[$key] = $seconds;
    }

    /**
     * Remove the active lock for the given key.
     */
    public function unlock(string $key): void
    {
        unset($this->locks[$key]);
    }

    /**
     * Expire the active lock for the given key.
     *
     * This method exists only to support unit tests.
     */
    public function expireLock(string $key): void
    {
        unset($this->locks[$key]);
    }
}

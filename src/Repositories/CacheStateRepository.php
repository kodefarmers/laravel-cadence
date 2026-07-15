<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Repositories;

use Illuminate\Contracts\Cache\Repository;
use Kodefarmers\Cadence\Contracts\StateRepository;
use Kodefarmers\Cadence\ValueObjects\CadenceConfig;
use Kodefarmers\Cadence\ValueObjects\State;

/**
 * Persists Cadence state using Laravel's cache repository.
 */
final readonly class CacheStateRepository implements StateRepository
{
    public function __construct(
        private readonly Repository $cache,
        private readonly CadenceConfig $config,
    ) {}

    /**
     * Retrieve the current state for the given key.
     */
    public function state(string $key): State
    {
        $expiresAt = (int) $this->cache->get($this->lockExpiresAtKey($key), 0);

        return new State(
            attempts: (int) $this->cache->get($this->attemptsKey($key), 0),
            locked: $this->cache->has($this->lockKey($key)),
            remainingLockSeconds: max(0, $expiresAt - time()),
        );
    }

    /**
     * Increment and return the attempt count for the given key.
     */
    public function incrementAttempts(string $key): int
    {
        $cacheKey = $this->attemptsKey($key);

        $attempts = $this->cache->increment($cacheKey);

        if ($attempts === false) {
            $currentAttempts = (int) $this->cache->get($cacheKey, 0);
            $attempts = $currentAttempts + 1;
        }

        $this->cache->put($cacheKey, $attempts, $this->config->idleTimeout);

        return $attempts;
    }

    /**
     * Reset the attempt count for the given key.
     */
    public function resetAttempts(string $key): void
    {
        $this->cache->forget($this->attemptsKey($key));
    }

    /**
     * Place the given key into backoff for the specified number of seconds.
     */
    public function lock(string $key, int $seconds): void
    {
        $expiresAt = time() + $seconds;

        $this->cache->put($this->lockKey($key), true, $seconds);
        $this->cache->put($this->lockExpiresAtKey($key), $expiresAt, $seconds);
    }

    /**
     * Remove the active lock for the given key.
     */
    public function unlock(string $key): void
    {
        $this->cache->forget($this->lockKey($key));
        $this->cache->forget($this->lockExpiresAtKey($key));
    }

    /**
     * Get the cache key for the attempt counter.
     */
    private function attemptsKey(string $key): string
    {
        return "cadence:attempts:{$key}";
    }

    /**
     * Get the cache key for the active lock.
     */
    private function lockKey(string $key): string
    {
        return "cadence:lock:{$key}";
    }

    /**
     * Get the cache key for the lock expiration timestamp.
     */
    private function lockExpiresAtKey(string $key): string
    {
        return "cadence:lock:{$key}:expires_at";
    }
}

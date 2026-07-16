<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\ValueObjects;

/**
 * Represents the outcome of a Cadence backoff evaluation.
 */
final readonly class CadenceResult
{
    public function __construct(
        /**
         * The current attempt count.
         */
        public int $attempt,

        /**
         * The current backoff violation count.
         */
        public int $violationCount,

        /**
         * The applied backoff delay, in seconds.
         */
        public int $delay,

        /**
         * Indicates whether the key is currently in backoff.
         */
        public bool $isLocked,
    ) {}
}

<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\ValueObjects;

/**
 * Configuration values for the Cadence backoff engine.
 */
final readonly class CadenceConfig
{
    public function __construct(
        /**
         * The number of attempts allowed before backoff is applied.
         */
        public int $freeAttempts = 3,

        /**
         * The number of seconds to retain attempt state after inactivity.
         */
        public int $idleTimeout = 3600,
    ) {}
}

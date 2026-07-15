<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Strategies;

use InvalidArgumentException;
use Kodefarmers\Cadence\Contracts\DelayStrategy;

/**
 * Calculates delays using an exponential backoff strategy.
 */
final readonly class ExponentialStrategy implements DelayStrategy
{
    /**
     * Create a new exponential strategy instance.
     *
     * @param  int  $baseDelay  The base delay, in seconds, used for exponential growth.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        private int $baseDelay = 2,
    ) {
        if ($baseDelay < 1) {
            throw new InvalidArgumentException('The base delay must be at least 1 second.');
        }
    }

    /**
     * Determine the delay, in seconds, for the given violation.
     *
     * @param  int  $violation  The current violation count, starting at 1.
     * @return int The calculated delay in seconds.
     */
    public function delayFor(int $violation): int
    {
        if ($violation < 1) {
            return 0;
        }

        return $this->baseDelay ** $violation;
    }
}

<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Strategies;

use InvalidArgumentException;
use Kodefarmers\Cadence\Contracts\DelayStrategy;

/**
 * Calculates delays using a linear backoff strategy.
 */
final readonly class LinearStrategy implements DelayStrategy
{
    /**
     * Create a new linear strategy instance.
     *
     * @param  int  $baseDelay  The delay added for each violation.
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
     */
    public function delayFor(int $violation): int
    {
        if ($violation < 1) {
            return 0;
        }

        return $this->baseDelay * $violation;
    }
}

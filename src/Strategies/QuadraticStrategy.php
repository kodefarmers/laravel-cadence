<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Strategies;

use InvalidArgumentException;
use Kodefarmers\Cadence\Contracts\DelayStrategy;

/**
 * Calculates delays using a quadratic backoff strategy.
 */
final readonly class QuadraticStrategy implements DelayStrategy
{
    /**
     * Create a new quadratic strategy instance.
     *
     * @param  int  $baseDelay  The multiplier applied to the squared violation count.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        private int $baseDelay = 1,
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

        return $this->baseDelay * ($violation ** 2);
    }
}

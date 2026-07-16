<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Strategies;

use InvalidArgumentException;
use Kodefarmers\Cadence\Contracts\DelayStrategy;

/**
 * Calculates delays using a Fibonacci backoff strategy.
 */
final readonly class FibonacciStrategy implements DelayStrategy
{
    /**
     * Create a new Fibonacci strategy instance.
     *
     * @param  int  $baseDelay  The multiplier applied to each Fibonacci number.
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
     * @return int The calculated delay in seconds.
     */
    public function delayFor(int $violation): int
    {
        if ($violation < 1) {
            return 0;
        }

        return $this->baseDelay * $this->fibonacci($violation);
    }

    /**
     * Calculate the Fibonacci number for the given position.
     */
    private function fibonacci(int $position): int
    {
        if ($position <= 2) {
            return 1;
        }

        $previous = 1;
        $current = 1;

        for ($i = 3; $i <= $position; $i++) {
            [$previous, $current] = [$current, $previous + $current];
        }

        return $current;
    }
}

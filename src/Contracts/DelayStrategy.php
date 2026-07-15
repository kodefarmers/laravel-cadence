<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Contracts;

/**
 * Defines how delay durations are calculated for rate limit violations.
 */
interface DelayStrategy
{
    /**
     * Calculate the delay, in seconds, for the given violation count.
     *
     * @param  int  $violation  The current violation count, starting at 1.
     * @return int The delay duration in seconds.
     */
    public function delayFor(int $violation): int;
}

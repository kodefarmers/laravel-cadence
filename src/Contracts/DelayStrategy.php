<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Contracts;

/**
 * Defines how backoff delays are calculated.
 */
interface DelayStrategy
{
    /**
     * Calculate the delay, in seconds, for the given violation count.
     *
     * @param  int  $violationCount  The current violation count, starting at 1.
     * @return int The delay duration in seconds.
     */
    public function delayFor(int $violationCount): int;
}

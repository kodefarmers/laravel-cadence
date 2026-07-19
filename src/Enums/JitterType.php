<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Enums;

/**
 * Supported jitter algorithms.
 */
enum JitterType
{
    case FULL;
    case EQUAL;

    /**
     * Apply the jitter algorithm to the provided delay.
     */
    public function apply(int $delay): int
    {
        return match ($this) {
            self::FULL => $this->applyFull($delay),
            self::EQUAL => $this->applyEqual($delay),
        };
    }

    private function applyFull(int $delay): int
    {
        return random_int(0, max(0, $delay));
    }

    private function applyEqual(int $delay): int
    {
        $minimum = intdiv(max(0, $delay), 2);

        return random_int($minimum, max(0, $delay));
    }
}

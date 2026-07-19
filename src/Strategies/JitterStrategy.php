<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Strategies;

use Kodefarmers\Cadence\Contracts\DelayStrategy;
use Kodefarmers\Cadence\Enums\JitterType;

/**
 * Decorates a delay strategy with jitter.
 */
final class JitterStrategy implements DelayStrategy
{
    /**
     * Create a new jitter strategy instance.
     */
    public function __construct(
        private DelayStrategy $strategy,
        private JitterType $type = JitterType::FULL,
    ) {}

    /**
     * Apply the configured jitter algorithm to the wrapped strategy's calculated delay.
     */
    public function delayFor(int $violationCount): int
    {
        $delay = $this->strategy->delayFor($violationCount);

        if ($delay <= 0) {
            return 0;
        }

        $jitteredDelay = $this->type->apply($delay);

        return $jitteredDelay;
    }

    /**
     * Get the wrapped delay strategy.
     */
    public function strategy(): DelayStrategy
    {
        return $this->strategy;
    }
}

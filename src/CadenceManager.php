<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence;

use Illuminate\Support\Manager;
use Kodefarmers\Cadence\Contracts\StateRepository;
use Kodefarmers\Cadence\Strategies\ExponentialStrategy;
use Kodefarmers\Cadence\Strategies\FibonacciStrategy;
use Kodefarmers\Cadence\ValueObjects\CadenceConfig;

/**
 * Creates and manages Cadence engine instances.
 */
class CadenceManager extends Manager
{
    /**
     * Get the default backoff strategy.
     */
    public function getDefaultDriver(): string
    {
        /** @var string $driver */
        $driver = $this->config->get('cadence.default', 'exponential');

        return $driver;
    }

    /**
     * Get a Cadence engine for the given driver.
     */
    public function driver($driver = null): CadenceEngine
    {
        /** @var CadenceEngine */
        return parent::driver($driver);
    }

    /**
     * Resolve the configured state repository.
     */
    protected function repository(): StateRepository
    {
        return $this->container->make(StateRepository::class);
    }

    /**
     * Resolve the Cadence configuration.
     */
    protected function cadenceConfig(): CadenceConfig
    {
        return $this->container->make(CadenceConfig::class);
    }

    /**
     * Create the exponential backoff driver.
     */
    protected function createExponentialDriver(): CadenceEngine
    {
        /** @var int $baseDelay */
        $baseDelay = $this->config->get(
            'cadence.drivers.exponential.base_delay',
        );

        return new CadenceEngine(
            strategy: new ExponentialStrategy($baseDelay),
            repository: $this->repository(),
            config: $this->cadenceConfig(),
        );
    }

    /**
     * Create the fibonacci backoff driver.
     */
    protected function createFibonacciDriver(): CadenceEngine
    {
        /** @var int $baseDelay */
        $baseDelay = $this->config->get(
            'cadence.drivers.fibonacci.base_delay',
        );

        return new CadenceEngine(
            strategy: new FibonacciStrategy($baseDelay),
            repository: $this->repository(),
            config: $this->cadenceConfig(),
        );
    }
}

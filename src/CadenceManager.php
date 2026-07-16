<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence;

use Illuminate\Support\Manager;
use Kodefarmers\Cadence\Contracts\StateRepository;
use Kodefarmers\Cadence\Strategies\ExponentialStrategy;
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
        return $this->config->get('cadence.default', 'exponential');
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
    protected function repository()
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
        return new CadenceEngine(
            strategy: new ExponentialStrategy(
                baseDelay: (int) $this->config->get(
                    'cadence.drivers.exponential.base_delay',
                    2,
                ),
            ),
            repository: $this->repository(),
            config: $this->cadenceConfig(),
        );
    }
}

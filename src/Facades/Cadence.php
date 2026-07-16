<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Facades;

use Illuminate\Support\Facades\Facade;
use Kodefarmers\Cadence\CadenceManager;

/**
 * Provides a static interface to the Cadence manager.
 *
 * @method static \Kodefarmers\Cadence\CadenceEngine driver(string|null $driver = null)
 *
 * @see CadenceManager
 */
class Cadence extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return CadenceManager::class;
    }
}

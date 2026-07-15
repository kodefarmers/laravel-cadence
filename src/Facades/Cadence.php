<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Kodefarmers\Cadence\Cadence
 */
class Cadence extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Kodefarmers\Cadence\Cadence::class;
    }
}

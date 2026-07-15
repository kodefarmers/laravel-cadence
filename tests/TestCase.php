<?php

declare(strict_types=1);

namespace Kodefarmers\Cadence\Tests;

use Kodefarmers\Cadence\CadenceServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Register the package service provider.
     */
    protected function getPackageProviders($app)
    {
        return [
            CadenceServiceProvider::class,
        ];
    }
}

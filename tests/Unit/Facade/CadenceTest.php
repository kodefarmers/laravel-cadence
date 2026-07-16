<?php

declare(strict_types=1);

use Kodefarmers\Cadence\CadenceManager;
use Kodefarmers\Cadence\Facades\Cadence;

it('resolves the cadence facade to the cadence manager', function (): void {
    expect(Cadence::getFacadeRoot())
        ->toBeInstanceOf(CadenceManager::class)
        ->toBe(app(CadenceManager::class));
});

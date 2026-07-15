<?php

declare(strict_types=1);

use Kodefarmers\Cadence\Strategies\ExponentialStrategy;

it('returns zero for non-violations', function (): void {
    $strategy = new ExponentialStrategy();

    expect($strategy->delayFor(0))->toBe(0);
});

it('calculates the exponential delay', function (): void {
    $strategy = new ExponentialStrategy();

    expect($strategy->delayFor(1))->toBe(2)
        ->and($strategy->delayFor(2))->toBe(4)
        ->and($strategy->delayFor(3))->toBe(8)
        ->and($strategy->delayFor(4))->toBe(16)
        ->and($strategy->delayFor(5))->toBe(32);
});

it('supports a custom base delay', function (): void {
    $strategy = new ExponentialStrategy(3);

    expect($strategy->delayFor(1))->toBe(3)
        ->and($strategy->delayFor(2))->toBe(9)
        ->and($strategy->delayFor(3))->toBe(27)
        ->and($strategy->delayFor(4))->toBe(81);
});

it('rejects a base delay less than one second', function (): void {
    expect(fn () => new ExponentialStrategy(0))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => new ExponentialStrategy(-1))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => new ExponentialStrategy(-2))
        ->toThrow(InvalidArgumentException::class);
});

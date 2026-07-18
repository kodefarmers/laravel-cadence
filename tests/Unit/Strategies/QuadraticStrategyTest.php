<?php

declare(strict_types=1);

use Kodefarmers\Cadence\Strategies\QuadraticStrategy;

it('returns zero for non-violations', function (): void {
    $strategy = new QuadraticStrategy();

    expect($strategy->delayFor(0))->toBe(0);
});

it('calculates the quadratic delay', function (): void {
    $strategy = new QuadraticStrategy();

    expect($strategy->delayFor(1))->toBe(1)
        ->and($strategy->delayFor(2))->toBe(4)
        ->and($strategy->delayFor(3))->toBe(9)
        ->and($strategy->delayFor(4))->toBe(16)
        ->and($strategy->delayFor(5))->toBe(25);
});

it('supports a custom base delay', function (): void {
    $strategy = new QuadraticStrategy(3);

    expect($strategy->delayFor(1))->toBe(3)
        ->and($strategy->delayFor(2))->toBe(12)
        ->and($strategy->delayFor(3))->toBe(27)
        ->and($strategy->delayFor(4))->toBe(48)
        ->and($strategy->delayFor(5))->toBe(75);
});

it('continues the quadratic sequence for larger violations', function (): void {
    $strategy = new QuadraticStrategy();

    expect($strategy->delayFor(6))->toBe(36)
        ->and($strategy->delayFor(7))->toBe(49)
        ->and($strategy->delayFor(8))->toBe(64)
        ->and($strategy->delayFor(9))->toBe(81)
        ->and($strategy->delayFor(10))->toBe(100);
});

it('rejects a base delay less than one second', function (): void {
    expect(fn () => new QuadraticStrategy(0))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => new QuadraticStrategy(-1))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => new QuadraticStrategy(-2))
        ->toThrow(InvalidArgumentException::class);
});

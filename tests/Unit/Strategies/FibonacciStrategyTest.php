<?php

declare(strict_types=1);

use Kodefarmers\Cadence\Strategies\FibonacciStrategy;

it('returns zero for non-violations', function (): void {
    $strategy = new FibonacciStrategy();

    expect($strategy->delayFor(0))->toBe(0);
});

it('calculates the fibonacci delay', function (): void {
    $strategy = new FibonacciStrategy();

    expect($strategy->delayFor(1))->toBe(1)
        ->and($strategy->delayFor(2))->toBe(1)
        ->and($strategy->delayFor(3))->toBe(2)
        ->and($strategy->delayFor(4))->toBe(3)
        ->and($strategy->delayFor(5))->toBe(5);
});

it('supports a custom base delay', function (): void {
    $strategy = new FibonacciStrategy(3);

    expect($strategy->delayFor(1))->toBe(3)
        ->and($strategy->delayFor(2))->toBe(3)
        ->and($strategy->delayFor(3))->toBe(6)
        ->and($strategy->delayFor(4))->toBe(9)
        ->and($strategy->delayFor(5))->toBe(15);
});

it('continues the fibonacci sequence for larger violations', function (): void {
    $strategy = new FibonacciStrategy();

    expect($strategy->delayFor(6))->toBe(8)
        ->and($strategy->delayFor(7))->toBe(13)
        ->and($strategy->delayFor(8))->toBe(21)
        ->and($strategy->delayFor(9))->toBe(34)
        ->and($strategy->delayFor(10))->toBe(55);
});

it('rejects a base delay less than one second', function (): void {
    expect(fn () => new FibonacciStrategy(0))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => new FibonacciStrategy(-1))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => new FibonacciStrategy(-2))
        ->toThrow(InvalidArgumentException::class);
});

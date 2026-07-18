<?php

declare(strict_types=1);

use Kodefarmers\Cadence\Strategies\LinearStrategy;

it('returns zero for non-violations', function (): void {
    $strategy = new LinearStrategy();

    expect($strategy->delayFor(0))->toBe(0);
});

it('calculates the linear delay', function (): void {
    $strategy = new LinearStrategy();

    expect($strategy->delayFor(1))->toBe(2)
        ->and($strategy->delayFor(2))->toBe(4)
        ->and($strategy->delayFor(3))->toBe(6)
        ->and($strategy->delayFor(4))->toBe(8)
        ->and($strategy->delayFor(5))->toBe(10);
});

it('supports a custom base delay', function (): void {
    $strategy = new LinearStrategy(3);

    expect($strategy->delayFor(1))->toBe(3)
        ->and($strategy->delayFor(2))->toBe(6)
        ->and($strategy->delayFor(3))->toBe(9)
        ->and($strategy->delayFor(4))->toBe(12)
        ->and($strategy->delayFor(5))->toBe(15);
});

it('continues the linear sequence for larger violations', function (): void {
    $strategy = new LinearStrategy();

    expect($strategy->delayFor(6))->toBe(12)
        ->and($strategy->delayFor(7))->toBe(14)
        ->and($strategy->delayFor(8))->toBe(16)
        ->and($strategy->delayFor(9))->toBe(18)
        ->and($strategy->delayFor(10))->toBe(20);
});

it('rejects a base delay less than one second', function (): void {
    expect(fn () => new LinearStrategy(0))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => new LinearStrategy(-1))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => new LinearStrategy(-2))
        ->toThrow(InvalidArgumentException::class);
});

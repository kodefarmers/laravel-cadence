<?php

declare(strict_types=1);

use Kodefarmers\Cadence\Contracts\DelayStrategy;
use Kodefarmers\Cadence\Enums\JitterType;
use Kodefarmers\Cadence\Strategies\ExponentialStrategy;
use Kodefarmers\Cadence\Strategies\FibonacciStrategy;
use Kodefarmers\Cadence\Strategies\JitterStrategy;
use Kodefarmers\Cadence\Strategies\LinearStrategy;
use Kodefarmers\Cadence\Strategies\QuadraticStrategy;

dataset('jitter', [
    'full' => [
        JitterType::FULL,
        0,
        20,
    ],
    'equal' => [
        JitterType::EQUAL,
        10,
        20,
    ],
]);

it('always returns zero when the wrapped strategy returns zero', function (): void {
    foreach (JitterType::cases() as $type) {
        $strategy = new JitterStrategy(new LinearStrategy(), $type);

        expect($strategy->delayFor(0))->toBe(0);
    }
});

it('applies the selected jitter algorithm', function (
    JitterType $type,
    int $minimum,
    int $maximum,
): void {
    $strategy = new JitterStrategy(
        new LinearStrategy(4),
        $type,
    );

    $delay = $strategy->delayFor(5);

    expect($delay)
        ->toBeInt()
        ->toBeGreaterThanOrEqual($minimum)
        ->toBeLessThanOrEqual($maximum);
})->with('jitter');

it('uses full jitter by default', function (): void {
    $base = (new LinearStrategy(4))->delayFor(5);

    $strategy = new JitterStrategy(
        new LinearStrategy(4),
    );

    expect($strategy->delayFor(5))
        ->toBeGreaterThanOrEqual(0)
        ->toBeLessThanOrEqual($base);
});

it('decorates every built-in strategy', function (): void {
    $scenarios = [
        [new ExponentialStrategy(2), 5],
        [new FibonacciStrategy(1), 5],
        [new LinearStrategy(2), 5],
        [new QuadraticStrategy(1), 5],
    ];

    foreach ($scenarios as [$strategy, $violation]) {
        $base = $strategy->delayFor($violation);

        $decorated = new JitterStrategy(
            $strategy,
            JitterType::FULL,
        );

        expect($decorated->delayFor($violation))
            ->toBeInt()
            ->toBeGreaterThanOrEqual(0)
            ->toBeLessThanOrEqual($base);
    }
});

it('never exceeds the original delay when using full jitter', function (): void {
    $base = (new ExponentialStrategy(2))->delayFor(5);

    $strategy = new JitterStrategy(
        new ExponentialStrategy(2),
        JitterType::FULL,
    );

    for ($i = 0; $i < 100; $i++) {
        expect($strategy->delayFor(5))
            ->toBeGreaterThanOrEqual(0)
            ->toBeLessThanOrEqual($base);
    }
});

it('always returns a value within the equal jitter range', function (): void {
    $base = (new LinearStrategy(4))->delayFor(5);

    $strategy = new JitterStrategy(
        new LinearStrategy(4),
        JitterType::EQUAL,
    );

    for ($i = 0; $i < 100; $i++) {
        expect($strategy->delayFor(5))
            ->toBeGreaterThanOrEqual((int) floor($base / 2))
            ->toBeLessThanOrEqual($base);
    }
});

it('supports every jitter algorithm', function (): void {
    foreach (JitterType::cases() as $type) {
        $strategy = new JitterStrategy(
            new ExponentialStrategy(),
            $type,
        );

        expect($strategy->delayFor(5))->toBeInt();
    }
});

it('produces randomized delays', function (): void {
    $strategy = new JitterStrategy(
        new ExponentialStrategy(),
        JitterType::FULL,
    );

    $results = [];

    for ($i = 0; $i < 50; $i++) {
        $results[] = $strategy->delayFor(5);
    }

    expect(count(array_unique($results)))
        ->toBeGreaterThan(1);
});

it('does not modify the wrapped strategy', function (): void {
    $strategy = new LinearStrategy(4);

    $decorated = new JitterStrategy($strategy);

    expect($strategy->delayFor(5))->toBe(20);

    $decorated->delayFor(5);

    expect($strategy->delayFor(5))->toBe(20);
});

it('delegates delay calculation to the wrapped strategy', function (): void {
    $strategy = new class() implements DelayStrategy
    {
        public function delayFor(int $violation): int
        {
            return 42;
        }
    };

    $decorated = new JitterStrategy(
        $strategy,
        JitterType::FULL,
    );

    expect($decorated->delayFor(1))
        ->toBeGreaterThanOrEqual(0)
        ->toBeLessThanOrEqual(42);
});

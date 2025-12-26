<?php

declare(strict_types=1);

use Relaticle\Flowforge\Services\DecimalPosition;
use Relaticle\Flowforge\Services\PositionRebalancer;
use Relaticle\Flowforge\Tests\Fixtures\Task;

describe('DecimalPosition performance', function () {
    test('10,000 position calculations complete in < 100ms', function () {
        $start = microtime(true);

        for ($i = 0; $i < 10_000; $i++) {
            DecimalPosition::between('1000.0000000000', '2000.0000000000');
        }

        $elapsed = microtime(true) - $start;

        expect($elapsed)->toBeLessThan(0.5); // Allow some margin
    });

    test('10,000 exact midpoint calculations complete in < 50ms', function () {
        $start = microtime(true);

        for ($i = 0; $i < 10_000; $i++) {
            DecimalPosition::betweenExact('1000.0000000000', '2000.0000000000');
        }

        $elapsed = microtime(true) - $start;

        expect($elapsed)->toBeLessThan(0.1);
    });

    test('10,000 normalize operations complete in < 50ms', function () {
        $values = ['1000', '1000.5', 1000, 1000.5, '-500'];
        $start = microtime(true);

        for ($i = 0; $i < 10_000; $i++) {
            DecimalPosition::normalize($values[$i % 5]);
        }

        $elapsed = microtime(true) - $start;

        expect($elapsed)->toBeLessThan(0.1);
    });
});

describe('PositionRebalancer performance', function () {
    test('rebalancing 100 cards completes in < 2 seconds', function () {
        // Create 100 cards with positions
        for ($i = 0; $i < 100; $i++) {
            Task::create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => DecimalPosition::normalize($i * 100),
            ]);
        }

        $rebalancer = new PositionRebalancer;

        $start = microtime(true);
        $count = $rebalancer->rebalanceColumn(
            Task::query(),
            'status',
            'todo',
            'order_position'
        );
        $elapsed = microtime(true) - $start;

        expect($count)->toBe(100)
            ->and($elapsed)->toBeLessThan(2.0);
    });

    test('gap statistics on 100 cards completes in < 500ms', function () {
        // Create 100 cards with positions
        for ($i = 0; $i < 100; $i++) {
            Task::create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => DecimalPosition::normalize($i * 100),
            ]);
        }

        $rebalancer = new PositionRebalancer;

        $start = microtime(true);
        $stats = $rebalancer->getGapStatistics(
            Task::query(),
            'status',
            'todo',
            'order_position'
        );
        $elapsed = microtime(true) - $start;

        expect($stats['count'])->toBe(100)
            ->and($elapsed)->toBeLessThan(0.5);
    });
});

describe('sequence generation performance', function () {
    test('generating 1000 sequential positions completes in < 50ms', function () {
        $start = microtime(true);

        $positions = DecimalPosition::generateSequence(1000);

        $elapsed = microtime(true) - $start;

        expect($positions)->toHaveCount(1000)
            ->and($elapsed)->toBeLessThan(0.1);
    });

    test('generating 100 between positions completes in < 50ms', function () {
        $start = microtime(true);

        $positions = DecimalPosition::generateBetween('1000', '2000', 100);

        $elapsed = microtime(true) - $start;

        expect($positions)->toHaveCount(100)
            ->and($elapsed)->toBeLessThan(0.1);
    });
});

describe('comparison performance', function () {
    test('10,000 comparisons complete in < 50ms', function () {
        $positions = [];
        for ($i = 0; $i < 100; $i++) {
            $positions[] = DecimalPosition::normalize($i * 1000);
        }

        $start = microtime(true);

        $count = 0;
        for ($i = 0; $i < 10_000; $i++) {
            $a = $positions[$i % 100];
            $b = $positions[($i + 1) % 100];
            DecimalPosition::compare($a, $b);
            DecimalPosition::lessThan($a, $b);
            DecimalPosition::greaterThan($a, $b);
            $count++;
        }

        $elapsed = microtime(true) - $start;

        expect($count)->toBe(10_000)
            ->and($elapsed)->toBeLessThan(0.1);
    });
});

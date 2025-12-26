<?php

declare(strict_types=1);

use Relaticle\Flowforge\Services\DecimalPosition;
use Relaticle\Flowforge\Services\PositionRebalancer;
use Relaticle\Flowforge\Tests\Fixtures\Task;

beforeEach(function () {
    // Create tasks with various positions
    Task::create(['title' => 'Task 1', 'status' => 'todo', 'order_position' => '1000.0000000000']);
    Task::create(['title' => 'Task 2', 'status' => 'todo', 'order_position' => '2000.0000000000']);
    Task::create(['title' => 'Task 3', 'status' => 'todo', 'order_position' => '3000.0000000000']);
});

describe('PositionRebalancer::needsRebalancing()', function () {
    test('detects gap below MIN_GAP', function () {
        // Create tasks with very small gap
        Task::create(['title' => 'Close 1', 'status' => 'in_progress', 'order_position' => '1000.0000000000']);
        Task::create(['title' => 'Close 2', 'status' => 'in_progress', 'order_position' => '1000.00005']); // Gap < MIN_GAP

        $rebalancer = new PositionRebalancer;

        expect($rebalancer->needsRebalancing(
            Task::query(),
            'status',
            'in_progress',
            'order_position'
        ))->toBeTrue();
    });

    test('returns false when gaps are healthy', function () {
        $rebalancer = new PositionRebalancer;

        expect($rebalancer->needsRebalancing(
            Task::query(),
            'status',
            'todo',
            'order_position'
        ))->toBeFalse();
    });

    test('returns false for empty column', function () {
        $rebalancer = new PositionRebalancer;

        expect($rebalancer->needsRebalancing(
            Task::query(),
            'status',
            'done', // No tasks in this column
            'order_position'
        ))->toBeFalse();
    });

    test('returns false for single item column', function () {
        Task::create(['title' => 'Alone', 'status' => 'review', 'order_position' => '1000.0000000000']);

        $rebalancer = new PositionRebalancer;

        expect($rebalancer->needsRebalancing(
            Task::query(),
            'status',
            'review',
            'order_position'
        ))->toBeFalse();
    });
});

describe('PositionRebalancer::rebalanceColumn()', function () {
    test('redistributes positions evenly', function () {
        $rebalancer = new PositionRebalancer;

        $count = $rebalancer->rebalanceColumn(
            Task::query(),
            'status',
            'todo',
            'order_position'
        );

        expect($count)->toBe(3);

        // Verify positions are evenly spaced
        $tasks = Task::where('status', 'todo')->orderBy('order_position')->get();

        expect(DecimalPosition::normalize($tasks[0]->order_position))->toBe('65535.0000000000')
            ->and(DecimalPosition::normalize($tasks[1]->order_position))->toBe('131070.0000000000')
            ->and(DecimalPosition::normalize($tasks[2]->order_position))->toBe('196605.0000000000');
    });

    test('maintains original order after rebalancing', function () {
        // Create tasks with irregular positions
        Task::create(['title' => 'A', 'status' => 'testing', 'order_position' => '100.0000000000']);
        Task::create(['title' => 'B', 'status' => 'testing', 'order_position' => '100.0001000000']);
        Task::create(['title' => 'C', 'status' => 'testing', 'order_position' => '100.0001500000']);
        Task::create(['title' => 'D', 'status' => 'testing', 'order_position' => '100.0001600000']);

        // Get original order
        $originalOrder = Task::where('status', 'testing')
            ->orderBy('order_position')
            ->pluck('title')
            ->toArray();

        // Rebalance
        $rebalancer = new PositionRebalancer;
        $rebalancer->rebalanceColumn(Task::query(), 'status', 'testing', 'order_position');

        // Get new order
        $newOrder = Task::where('status', 'testing')
            ->orderBy('order_position')
            ->pluck('title')
            ->toArray();

        expect($newOrder)->toBe($originalOrder);
    });

    test('returns zero for empty column', function () {
        $rebalancer = new PositionRebalancer;

        $count = $rebalancer->rebalanceColumn(
            Task::query(),
            'status',
            'nonexistent',
            'order_position'
        );

        expect($count)->toBe(0);
    });
});

describe('PositionRebalancer::findColumnsNeedingRebalancing()', function () {
    test('identifies columns with small gaps', function () {
        // Create column with healthy gaps
        Task::create(['title' => 'Healthy 1', 'status' => 'done', 'order_position' => '1000.0000000000']);
        Task::create(['title' => 'Healthy 2', 'status' => 'done', 'order_position' => '2000.0000000000']);

        // Create column with small gaps
        Task::create(['title' => 'Cramped 1', 'status' => 'blocked', 'order_position' => '1000.0000000000']);
        Task::create(['title' => 'Cramped 2', 'status' => 'blocked', 'order_position' => '1000.00005']); // Gap < MIN_GAP

        $rebalancer = new PositionRebalancer;

        $needsRebalancing = $rebalancer->findColumnsNeedingRebalancing(
            Task::query(),
            'status',
            'order_position'
        );

        expect($needsRebalancing)->toContain('blocked')
            ->and($needsRebalancing)->not->toContain('done')
            ->and($needsRebalancing)->not->toContain('todo');
    });
});

describe('PositionRebalancer::rebalanceAll()', function () {
    test('processes all columns needing rebalancing', function () {
        // Create multiple columns needing rebalancing
        Task::create(['title' => 'Col1 A', 'status' => 'blocked', 'order_position' => '1000.0000000000']);
        Task::create(['title' => 'Col1 B', 'status' => 'blocked', 'order_position' => '1000.00005']);

        Task::create(['title' => 'Col2 A', 'status' => 'review', 'order_position' => '2000.0000000000']);
        Task::create(['title' => 'Col2 B', 'status' => 'review', 'order_position' => '2000.00003']);

        $rebalancer = new PositionRebalancer;

        $results = $rebalancer->rebalanceAll(
            Task::query(),
            'status',
            'order_position'
        );

        expect($results)->toHaveKey('blocked')
            ->and($results)->toHaveKey('review')
            ->and($results['blocked'])->toBe(2)
            ->and($results['review'])->toBe(2);

        // Verify gaps are now healthy
        expect($rebalancer->needsRebalancing(Task::query(), 'status', 'blocked', 'order_position'))->toBeFalse()
            ->and($rebalancer->needsRebalancing(Task::query(), 'status', 'review', 'order_position'))->toBeFalse();
    });
});

describe('PositionRebalancer::getGapStatistics()', function () {
    test('returns correct statistics for column', function () {
        $rebalancer = new PositionRebalancer;

        $stats = $rebalancer->getGapStatistics(
            Task::query(),
            'status',
            'todo',
            'order_position'
        );

        expect($stats['count'])->toBe(3)
            ->and($stats['min_gap'])->toBe('1000.0000000000')
            ->and($stats['max_gap'])->toBe('1000.0000000000')
            ->and($stats['avg_gap'])->toBe('1000.0000000000')
            ->and($stats['small_gaps'])->toBe(0);
    });

    test('returns nulls for single item column', function () {
        Task::create(['title' => 'Solo', 'status' => 'solo_column', 'order_position' => '1000.0000000000']);

        $rebalancer = new PositionRebalancer;

        $stats = $rebalancer->getGapStatistics(
            Task::query(),
            'status',
            'solo_column',
            'order_position'
        );

        expect($stats['count'])->toBe(1)
            ->and($stats['min_gap'])->toBeNull()
            ->and($stats['max_gap'])->toBeNull()
            ->and($stats['avg_gap'])->toBeNull()
            ->and($stats['small_gaps'])->toBe(0);
    });

    test('counts small gaps correctly', function () {
        Task::create(['title' => 'A', 'status' => 'cramped', 'order_position' => '1000.0000000000']);
        Task::create(['title' => 'B', 'status' => 'cramped', 'order_position' => '1000.00005']); // Small gap
        Task::create(['title' => 'C', 'status' => 'cramped', 'order_position' => '2000.0000000000']); // Large gap

        $rebalancer = new PositionRebalancer;

        $stats = $rebalancer->getGapStatistics(
            Task::query(),
            'status',
            'cramped',
            'order_position'
        );

        expect($stats['count'])->toBe(3)
            ->and($stats['small_gaps'])->toBe(1);
    });
});

<?php

declare(strict_types=1);

use Relaticle\Flowforge\Services\DecimalPosition;
use Relaticle\Flowforge\Services\PositionRebalancer;
use Relaticle\Flowforge\Support\EnumHelper;
use Relaticle\Flowforge\Tests\Fixtures\StatusEnum;
use Relaticle\Flowforge\Tests\Fixtures\Task;

function runPositionRebalancingTests(string $label, callable $s): void
{
    describe($label, function () use ($s) {
        // $s is a callback that either converts a string to a StatusEnum, or returns the string unchanged
        beforeEach(function () use ($s) {
            Task::create(['title' => 'Task 1', 'status' => $s('todo'), 'order_position' => '1000.0000000000']);
            Task::create(['title' => 'Task 2', 'status' => $s('todo'), 'order_position' => '2000.0000000000']);
            Task::create(['title' => 'Task 3', 'status' => $s('todo'), 'order_position' => '3000.0000000000']);
        });

        describe('PositionRebalancer::needsRebalancing()', function () use ($s) {
            test('detects gap below MIN_GAP', function () use ($s) {
                Task::create(['title' => 'Close 1', 'status' => $s('in_progress'), 'order_position' => '1000.0000000000']);
                Task::create(['title' => 'Close 2', 'status' => $s('in_progress'), 'order_position' => '1000.00005']); // Gap < MIN_GAP

                $rebalancer = new PositionRebalancer;

                expect($rebalancer->needsRebalancing(
                    Task::query(),
                    'status',
                    EnumHelper::convertEnumToString($s('in_progress')),
                    'order_position'
                ))->toBeTrue();
            });

            test('returns false when gaps are healthy', function () use ($s) {
                $rebalancer = new PositionRebalancer;

                expect($rebalancer->needsRebalancing(
                    Task::query(),
                    'status',
                    EnumHelper::convertEnumToString($s('todo')),
                    'order_position'
                ))->toBeFalse();
            });

            test('returns false for empty column', function () use ($s) {
                $rebalancer = new PositionRebalancer;

                expect($rebalancer->needsRebalancing(
                    Task::query(),
                    'status',
                    EnumHelper::convertEnumToString($s('done')), // No tasks in this column
                    'order_position'
                ))->toBeFalse();
            });

            test('returns false for single item column', function () use ($s) {
                Task::create(['title' => 'Alone', 'status' => $s('review'), 'order_position' => '1000.0000000000']);

                $rebalancer = new PositionRebalancer;

                expect($rebalancer->needsRebalancing(
                    Task::query(),
                    'status',
                    EnumHelper::convertEnumToString($s('review')),
                    'order_position'
                ))->toBeFalse();
            });
        });

        describe('PositionRebalancer::rebalanceColumn()', function () use ($s) {
            test('redistributes positions evenly', function () use ($s) {
                $rebalancer = new PositionRebalancer;

                $count = $rebalancer->rebalanceColumn(
                    Task::query(),
                    'status',
                    EnumHelper::convertEnumToString($s('todo')),
                    'order_position'
                );

                expect($count)->toBe(3);

                $tasks = Task::where('status', $s('todo'))->orderBy('order_position')->get();

                expect(DecimalPosition::normalize($tasks[0]->order_position))->toBe('65535.0000000000')
                    ->and(DecimalPosition::normalize($tasks[1]->order_position))->toBe('131070.0000000000')
                    ->and(DecimalPosition::normalize($tasks[2]->order_position))->toBe('196605.0000000000');
            });

            test('maintains original order after rebalancing', function () use ($s) {
                Task::create(['title' => 'A', 'status' => $s('testing'), 'order_position' => '100.0000000000']);
                Task::create(['title' => 'B', 'status' => $s('testing'), 'order_position' => '100.0001000000']);
                Task::create(['title' => 'C', 'status' => $s('testing'), 'order_position' => '100.0001500000']);
                Task::create(['title' => 'D', 'status' => $s('testing'), 'order_position' => '100.0001600000']);

                $originalOrder = Task::where('status', $s('testing'))
                    ->orderBy('order_position')
                    ->pluck('title')
                    ->toArray();

                $rebalancer = new PositionRebalancer;
                $rebalancer->rebalanceColumn(
                    Task::query(),
                    'status',
                    EnumHelper::convertEnumToString($s('testing')),
                    'order_position'
                );

                $newOrder = Task::where('status', $s('testing'))
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

        describe('PositionRebalancer::findColumnsNeedingRebalancing()', function () use ($s) {
            test('identifies columns with small gaps', function () use ($s) {
                Task::create(['title' => 'Healthy 1', 'status' => $s('done'), 'order_position' => '1000.0000000000']);
                Task::create(['title' => 'Healthy 2', 'status' => $s('done'), 'order_position' => '2000.0000000000']);

                Task::create(['title' => 'Cramped 1', 'status' => $s('blocked'), 'order_position' => '1000.0000000000']);
                Task::create(['title' => 'Cramped 2', 'status' => $s('blocked'), 'order_position' => '1000.00005']); // Gap < MIN_GAP

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

        describe('PositionRebalancer::rebalanceAll()', function () use ($s) {
            test('processes all columns needing rebalancing', function () use ($s) {
                Task::create(['title' => 'Col1 A', 'status' => $s('blocked'), 'order_position' => '1000.0000000000']);
                Task::create(['title' => 'Col1 B', 'status' => $s('blocked'), 'order_position' => '1000.00005']);

                Task::create(['title' => 'Col2 A', 'status' => $s('review'), 'order_position' => '2000.0000000000']);
                Task::create(['title' => 'Col2 B', 'status' => $s('review'), 'order_position' => '2000.00003']);

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

                expect($rebalancer->needsRebalancing(Task::query(), 'status', EnumHelper::convertEnumToString($s('blocked')), 'order_position'))->toBeFalse()
                    ->and($rebalancer->needsRebalancing(Task::query(), 'status', EnumHelper::convertEnumToString($s('review')), 'order_position'))->toBeFalse();
            });
        });

        describe('PositionRebalancer::getGapStatistics()', function () use ($s) {
            test('returns correct statistics for column', function () use ($s) {
                $rebalancer = new PositionRebalancer;

                $stats = $rebalancer->getGapStatistics(
                    Task::query(),
                    'status',
                    EnumHelper::convertEnumToString($s('todo')),
                    'order_position'
                );

                expect($stats['count'])->toBe(3)
                    ->and($stats['min_gap'])->toBe('1000.0000000000')
                    ->and($stats['max_gap'])->toBe('1000.0000000000')
                    ->and($stats['avg_gap'])->toBe('1000.0000000000')
                    ->and($stats['small_gaps'])->toBe(0);
            });

            test('returns nulls for single item column', function () use ($s) {
                Task::create(['title' => 'Solo', 'status' => $s('solo_column'), 'order_position' => '1000.0000000000']);

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

            test('counts small gaps correctly', function () use ($s) {
                Task::create(['title' => 'A', 'status' => $s('cramped'), 'order_position' => '1000.0000000000']);
                Task::create(['title' => 'B', 'status' => $s('cramped'), 'order_position' => '1000.00005']); // Small gap
                Task::create(['title' => 'C', 'status' => $s('cramped'), 'order_position' => '2000.0000000000']); // Large gap

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
    });
}

runPositionRebalancingTests('with string statuses', fn (string $v) => $v);
runPositionRebalancingTests('with enum statuses', fn (string $v) => StatusEnum::tryFrom($v) ?? $v);

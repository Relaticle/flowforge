<?php

declare(strict_types=1);

use Relaticle\Flowforge\Services\DecimalPosition;
use Relaticle\Flowforge\Tests\Fixtures\Task;

beforeEach(function () {
    // Create reference cards that we'll insert between
    Task::create([
        'title' => 'Reference Card A',
        'status' => 'todo',
        'order_position' => '1000.0000000000',
    ]);

    Task::create([
        'title' => 'Reference Card B',
        'status' => 'todo',
        'order_position' => '2000.0000000000',
    ]);
});

describe('concurrent position insertions', function () {
    test('50 insertions at same position produce unique positions', function () {
        $afterPos = '1000.0000000000';
        $beforePos = '2000.0000000000';
        $insertedPositions = [];
        $failedInserts = 0;

        // Simulate 50 concurrent insertions using the jitter mechanism
        for ($i = 0; $i < 50; $i++) {
            $position = DecimalPosition::between($afterPos, $beforePos);

            try {
                $task = Task::create([
                    'title' => "Concurrent Card {$i}",
                    'status' => 'todo',
                    'order_position' => $position,
                ]);
                $insertedPositions[] = $task->order_position;
            } catch (\Illuminate\Database\QueryException $e) {
                // If we hit a duplicate (extremely rare), count it
                $failedInserts++;
            }
        }

        // All positions should be unique
        expect(array_unique($insertedPositions))->toHaveCount(count($insertedPositions))
            ->and($failedInserts)->toBe(0);

        // All positions should be strictly between bounds
        foreach ($insertedPositions as $position) {
            $posStr = DecimalPosition::normalize($position);
            expect(bccomp($posStr, $afterPos, 10))->toBeGreaterThan(0)
                ->and(bccomp($posStr, $beforePos, 10))->toBeLessThan(0);
        }

        // ORDER BY should give consistent results
        $orderedTasks = Task::where('status', 'todo')
            ->whereNotIn('id', [1, 2]) // Exclude reference cards
            ->orderBy('order_position')
            ->get();

        expect($orderedTasks)->toHaveCount(50);

        // Verify ordering is consistent
        $previousPosition = '0';
        foreach ($orderedTasks as $task) {
            $posStr = DecimalPosition::normalize($task->order_position);
            expect(bccomp($posStr, $previousPosition, 10))
                ->toBeGreaterThan(0);
            $previousPosition = $posStr;
        }
    });

    test('rapid successive insertions by same user dont collide', function () {
        $afterPos = '1000.0000000000';
        $beforePos = '2000.0000000000';
        $insertedCount = 0;

        // Rapid fire 50 insertions without any delay
        for ($i = 0; $i < 50; $i++) {
            $position = DecimalPosition::between($afterPos, $beforePos);

            try {
                Task::create([
                    'title' => "Rapid Card {$i}",
                    'status' => 'todo',
                    'order_position' => $position,
                ]);
                $insertedCount++;
            } catch (\Illuminate\Database\QueryException) {
                // Unique constraint violation - should never happen
            }
        }

        expect($insertedCount)->toBe(50);

        // Verify all positions are unique
        $positions = Task::where('status', 'todo')
            ->whereNotIn('id', [1, 2])
            ->pluck('order_position')
            ->toArray();

        expect(array_unique($positions))->toHaveCount(50);
    });

    test('unique constraint actually prevents duplicate positions', function () {
        // Insert a card with a specific position
        Task::create([
            'title' => 'First Card',
            'status' => 'todo',
            'order_position' => '1500.0000000000',
        ]);

        // Try to insert another card with the exact same position
        expect(fn () => Task::create([
            'title' => 'Duplicate Card',
            'status' => 'todo',
            'order_position' => '1500.0000000000',
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('positions remain sortable after many insertions', function () {
        $afterPos = '1000.0000000000';
        $beforePos = '2000.0000000000';

        // Insert 100 cards
        for ($i = 0; $i < 100; $i++) {
            $position = DecimalPosition::between($afterPos, $beforePos);
            Task::create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => $position,
            ]);
        }

        // Verify ORDER BY works correctly
        $tasks = Task::where('status', 'todo')
            ->orderBy('order_position')
            ->get();

        expect($tasks)->toHaveCount(102); // 100 + 2 reference cards

        // Verify strict ordering
        $previousPosition = '-999999';
        foreach ($tasks as $task) {
            $posStr = DecimalPosition::normalize($task->order_position);
            expect(bccomp($posStr, $previousPosition, 10))
                ->toBeGreaterThan(0);
            $previousPosition = $posStr;
        }
    });
});

describe('position collision statistics', function () {
    test('jitter produces statistically unique positions', function () {
        $afterPos = '1000.0000000000';
        $beforePos = '2000.0000000000';
        $positions = [];

        // Generate 1000 positions without inserting
        for ($i = 0; $i < 1000; $i++) {
            $positions[] = DecimalPosition::between($afterPos, $beforePos);
        }

        $uniquePositions = array_unique($positions);

        // With cryptographic jitter, we should have 100% unique positions
        expect(count($uniquePositions))->toBe(1000);

        // Calculate position distribution around midpoint
        $midpoint = DecimalPosition::betweenExact($afterPos, $beforePos);
        $belowMidpoint = 0;
        $aboveMidpoint = 0;

        foreach ($positions as $position) {
            if (bccomp($position, $midpoint, 10) < 0) {
                $belowMidpoint++;
            } else {
                $aboveMidpoint++;
            }
        }

        // Distribution should be roughly 50/50 (within reasonable variance)
        expect($belowMidpoint)->toBeGreaterThan(400)
            ->and($belowMidpoint)->toBeLessThan(600)
            ->and($aboveMidpoint)->toBeGreaterThan(400)
            ->and($aboveMidpoint)->toBeLessThan(600);
    });
});

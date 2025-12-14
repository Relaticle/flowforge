<?php

use Livewire\Livewire;
use Relaticle\Flowforge\Services\Rank;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

beforeEach(function () {
    $this->board = Livewire::test(TestBoard::class);
});

describe('Performance Regression Baselines - Prevent Future Slowdowns', function () {
    it('benchmarks move operations at different scales', function ($cardCount, $maxDuration) {
        // Create cards
        Task::factory()->count($cardCount)->create(['status' => 'todo']);

        $testCard = Task::inRandomOrder()->first();
        $newStatus = 'in_progress';

        // Measure move duration
        $startTime = microtime(true);
        $this->board->call('moveCard', (string) $testCard->id, $newStatus);
        $duration = microtime(true) - $startTime;

        // Verify within performance threshold
        expect($duration)->toBeLessThan(
            $maxDuration,
            "Move with {$cardCount} cards should complete within {$maxDuration}s (took {$duration}s)"
        );

        $testCard->refresh();
        expect($testCard->status)->toBe($newStatus);
    })->with([
        '50 cards' => [50, 0.1],      // 100ms
        '100 cards' => [100, 0.2],    // 200ms
        '250 cards' => [250, 0.3],    // 300ms
        '500 cards' => [500, 0.5],    // 500ms
    ]);

    it('tracks position string length growth over time', function ($cardCount) {
        // Create cards sequentially
        $cards = collect();
        $lengthMetrics = [];

        for ($i = 1; $i <= $cardCount; $i++) {
            $rank = $i === 1
                ? Rank::forEmptySequence()
                : Rank::after(Rank::fromString($cards->last()->order_position));

            $card = Task::factory()->create([
                'status' => 'todo',
                'order_position' => $rank->get(),
            ]);

            $cards->push($card);

            // Track metrics at checkpoints
            if ($i % 25 === 0) {
                $lengths = $cards->pluck('order_position')->map(fn ($p) => strlen($p));
                $lengthMetrics[$i] = [
                    'avg' => round($lengths->avg(), 2),
                    'max' => $lengths->max(),
                ];
            }
        }

        // Verify growth is linear, not exponential
        $checkpoints = array_keys($lengthMetrics);
        for ($i = 1; $i < count($checkpoints); $i++) {
            $prev = $lengthMetrics[$checkpoints[$i - 1]];
            $curr = $lengthMetrics[$checkpoints[$i]];

            // Growth should be gradual (max increase of 2 chars per 25 cards)
            $avgGrowth = $curr['avg'] - $prev['avg'];
            expect($avgGrowth)->toBeLessThan(
                2,
                'Average position length growth should be gradual'
            );
        }

        dump("Length metrics for {$cardCount} cards:", $lengthMetrics);
    })->with([
        '100 cards' => 100,
        '200 cards' => 200,
    ]);

    it('benchmarks bulk operations performance', function () {
        // Create baseline
        Task::factory()->count(100)->create();

        $tasks = Task::all();

        // Benchmark 50 rapid moves
        $durations = [];
        for ($i = 0; $i < 50; $i++) {
            $task = $tasks->random();
            $newStatus = collect(['todo', 'in_progress', 'completed'])->random();

            $start = microtime(true);
            $this->board->call('moveCard', (string) $task->id, $newStatus);
            $durations[] = microtime(true) - $start;
        }

        $avgDuration = array_sum($durations) / count($durations);
        $maxDuration = max($durations);

        // Performance baselines
        expect($avgDuration)->toBeLessThan(0.1, 'Average operation should be < 100ms');
        expect($maxDuration)->toBeLessThan(0.3, 'Max operation should be < 300ms');

        dump('Bulk operations performance:', [
            'avg_duration_ms' => round($avgDuration * 1000, 2),
            'max_duration_ms' => round($maxDuration * 1000, 2),
            'total_operations' => 50,
        ]);
    });

    it('validates database query performance under load', function () {
        // Create large dataset
        Task::factory()->count(300)->create();

        // Measure query performance for common operations
        $metrics = [];

        // 1. Query all tasks in a column
        $start = microtime(true);
        $todoTasks = Task::where('status', 'todo')->get();
        $metrics['query_column'] = microtime(true) - $start;

        // 2. Query with ordering
        $start = microtime(true);
        $orderedTasks = Task::where('status', 'todo')
            ->orderBy('order_position')
            ->get();
        $metrics['query_ordered'] = microtime(true) - $start;

        // 3. Count operations
        $start = microtime(true);
        $count = Task::where('status', 'todo')->count();
        $metrics['count_query'] = microtime(true) - $start;

        // All queries should be fast (< 50ms)
        foreach ($metrics as $operation => $duration) {
            expect($duration)->toBeLessThan(
                0.05,
                "{$operation} should complete within 50ms (took " . round($duration * 1000, 2) . 'ms)'
            );
        }

        dump('Database query performance:', array_map(
            fn ($d) => round($d * 1000, 2) . 'ms',
            $metrics
        ));
    });

    it('establishes memory usage baselines', function () {
        $beforeMemory = memory_get_usage(true);

        // Create 200 cards and perform operations
        Task::factory()->count(200)->create();
        $tasks = Task::all();

        // Perform 30 operations
        for ($i = 0; $i < 30; $i++) {
            $task = $tasks->random();
            $this->board->call(
                'moveCard',
                (string) $task->id,
                collect(['todo', 'in_progress', 'completed'])->random()
            );
        }

        $afterMemory = memory_get_usage(true);
        $memoryUsed = $afterMemory - $beforeMemory;
        $memoryUsedMB = round($memoryUsed / 1024 / 1024, 2);

        // Memory usage should be reasonable (< 10MB for 200 cards + 30 ops)
        expect($memoryUsedMB)->toBeLessThan(
            10,
            "Memory usage should be under 10MB (used {$memoryUsedMB}MB)"
        );

        dump('Memory usage:', [
            'before_mb' => round($beforeMemory / 1024 / 1024, 2),
            'after_mb' => round($afterMemory / 1024 / 1024, 2),
            'used_mb' => $memoryUsedMB,
        ]);
    });

    it('validates position generation performance', function () {
        // Benchmark position generation algorithms
        $metrics = [];

        // 1. Empty sequence position
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $pos = Rank::forEmptySequence()->get();
        }
        $metrics['empty_sequence'] = (microtime(true) - $start) / 100;

        // 2. After position
        $lastPos = Rank::forEmptySequence();
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $lastPos = Rank::after($lastPos);
        }
        $metrics['after_position'] = (microtime(true) - $start) / 100;

        // 3. Between positions
        $pos1 = Rank::fromString('a');
        $pos2 = Rank::fromString('b');
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $pos = Rank::betweenRanks($pos1, $pos2)->get();
        }
        $metrics['between_positions'] = (microtime(true) - $start) / 100;

        // All operations should be < 1ms on average
        foreach ($metrics as $operation => $avgDuration) {
            expect($avgDuration)->toBeLessThan(
                0.001,
                "{$operation} should be < 1ms per operation"
            );
        }

        dump('Position generation performance (avg per operation):', array_map(
            fn ($d) => round($d * 1000000, 2) . 'μs',
            $metrics
        ));
    });
});

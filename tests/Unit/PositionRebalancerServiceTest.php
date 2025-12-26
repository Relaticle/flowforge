<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Relaticle\Flowforge\Services\DecimalPosition;
use Relaticle\Flowforge\Services\PositionRebalancer;

describe('PositionRebalancer Service', function () {
    /**
     * Create a properly configured mock builder that handles clone operations.
     */
    function createMockBuilder(array $positions): Builder
    {
        // Create a real query builder mock for __clone to work
        $queryMock = Mockery::mock(QueryBuilder::class);
        $queryMock->shouldReceive('cloneWithout')->andReturnSelf();
        $queryMock->shouldReceive('cloneWithoutBindings')->andReturnSelf();

        $mock = Mockery::mock(Builder::class)->makePartial();

        // Set the internal query property so __clone works
        $reflection = new ReflectionClass($mock);
        if ($reflection->hasProperty('query')) {
            $property = $reflection->getProperty('query');
            $property->setAccessible(true);
            $property->setValue($mock, $queryMock);
        }

        // Mock the fluent methods
        $mock->shouldReceive('where')->andReturnSelf();
        $mock->shouldReceive('whereNotNull')->andReturnSelf();
        $mock->shouldReceive('orderBy')->andReturnSelf();
        $mock->shouldReceive('pluck')->andReturn(collect($positions));

        return $mock;
    }

    describe('needsRebalancing', function () {
        it('returns false for empty query', function () {
            $rebalancer = new PositionRebalancer;
            $mock = createMockBuilder([]);

            expect($rebalancer->needsRebalancing($mock, 'status', 'todo', 'position'))->toBeFalse();
        });

        it('returns false for single item', function () {
            $rebalancer = new PositionRebalancer;
            $mock = createMockBuilder(['65535']);

            expect($rebalancer->needsRebalancing($mock, 'status', 'todo', 'position'))->toBeFalse();
        });

        it('returns false for well-spaced positions', function () {
            $rebalancer = new PositionRebalancer;
            $mock = createMockBuilder([
                '65535',
                '131070',
                '196605',
            ]);

            expect($rebalancer->needsRebalancing($mock, 'status', 'todo', 'position'))->toBeFalse();
        });

        it('returns true for positions with gap below MIN_GAP', function () {
            $rebalancer = new PositionRebalancer;
            $mock = createMockBuilder([
                '100.0000000000',
                '100.0000000050', // Gap of 0.00000000050 - below MIN_GAP (0.0001)
                '200.0000000000',
            ]);

            expect($rebalancer->needsRebalancing($mock, 'status', 'todo', 'position'))->toBeTrue();
        });
    });

    describe('getGapStatistics', function () {
        it('returns empty stats for empty column', function () {
            $rebalancer = new PositionRebalancer;
            $mock = createMockBuilder([]);

            $stats = $rebalancer->getGapStatistics($mock, 'status', 'todo', 'position');

            expect($stats['count'])->toBe(0);
            expect($stats['min_gap'])->toBeNull();
            expect($stats['max_gap'])->toBeNull();
            expect($stats['avg_gap'])->toBeNull();
            expect($stats['small_gaps'])->toBe(0);
        });

        it('returns empty stats for single item', function () {
            $rebalancer = new PositionRebalancer;
            $mock = createMockBuilder(['65535']);

            $stats = $rebalancer->getGapStatistics($mock, 'status', 'todo', 'position');

            expect($stats['count'])->toBe(1);
            expect($stats['min_gap'])->toBeNull();
            expect($stats['small_gaps'])->toBe(0);
        });

        it('calculates correct statistics for evenly spaced positions', function () {
            $rebalancer = new PositionRebalancer;
            $mock = createMockBuilder([
                '65535',
                '131070',
                '196605',
            ]);

            $stats = $rebalancer->getGapStatistics($mock, 'status', 'todo', 'position');

            expect($stats['count'])->toBe(3);
            expect($stats['min_gap'])->toBe('65535.0000000000');
            expect($stats['max_gap'])->toBe('65535.0000000000');
            expect($stats['avg_gap'])->toBe('65535.0000000000');
            expect($stats['small_gaps'])->toBe(0);
        });

        it('detects small gaps correctly', function () {
            $rebalancer = new PositionRebalancer;
            $mock = createMockBuilder([
                '100.0000000000',
                '100.0000000010', // Tiny gap
                '100.0000000020', // Another tiny gap
                '65635.0000000000', // Big gap
            ]);

            $stats = $rebalancer->getGapStatistics($mock, 'status', 'todo', 'position');

            expect($stats['count'])->toBe(4);
            expect($stats['small_gaps'])->toBe(2); // Two gaps below MIN_GAP
        });
    });

    describe('generateSequence integration', function () {
        it('generates properly spaced positions for rebalancing', function () {
            $positions = DecimalPosition::generateSequence(5);

            expect($positions)->toHaveCount(5);
            expect($positions[0])->toBe('65535.0000000000');
            expect($positions[1])->toBe('131070.0000000000');
            expect($positions[2])->toBe('196605.0000000000');
            expect($positions[3])->toBe('262140.0000000000');
            expect($positions[4])->toBe('327675.0000000000');

            // All gaps should be DEFAULT_GAP
            for ($i = 1; $i < count($positions); $i++) {
                $gap = DecimalPosition::gap($positions[$i - 1], $positions[$i]);
                expect($gap)->toBe('65535.0000000000');
            }
        });
    });
});

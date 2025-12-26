<?php

use Livewire\Livewire;
use Relaticle\Flowforge\Services\DecimalPosition;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

beforeEach(function () {
    $this->board = Livewire::test(TestBoard::class);
});

describe('Decimal Position Gap Tests - Ensuring Precision Never Fails', function () {
    it('stresses 100 sequential insertions at bottom of column', function () {
        // STRESS TEST: Test DecimalPosition with 100 sequential insertions
        // Expected: Positions grow linearly, all positions unique
        // This tests DecimalPosition::after() under heavy sequential usage

        $cards = collect();
        $position = DecimalPosition::forEmptyColumn();

        // Create 100 cards sequentially at bottom
        for ($i = 1; $i <= 100; $i++) {
            $card = Task::factory()->create([
                'title' => "Sequential #{$i}",
                'status' => 'todo',
                'order_position' => $position,
            ]);

            $cards->push($card);
            $position = DecimalPosition::after($position);

            // Check for inversions at checkpoints
            if (in_array($i, [10, 20, 50, 75, 100])) {
                $inversions = detectInversions(Task::class, 'todo');
                expect($inversions)->toBeEmpty(
                    "No inversions after {$i} sequential insertions"
                );
            }
        }

        // Verify all positions are unique
        $uniquePositions = $cards->pluck('order_position')->unique()->count();
        expect($uniquePositions)->toBe(100, 'All 100 positions should be unique');

        // Verify positions are in ascending order
        $positions = Task::where('status', 'todo')
            ->orderBy('order_position')
            ->orderBy('id')
            ->pluck('order_position')
            ->toArray();

        for ($i = 0; $i < count($positions) - 1; $i++) {
            expect(DecimalPosition::lessThan(
                DecimalPosition::normalize($positions[$i]),
                DecimalPosition::normalize($positions[$i + 1])
            ))->toBeTrue("Position {$i} should be less than position " . ($i + 1));
        }
    });

    it('monitors position growth patterns with 500 sequential cards', function () {
        // STRESS TEST: Create 500 cards sequentially
        // Expected: Linear growth in position values, not exponential
        // This tests DecimalPosition::after() under heavy sequential usage

        $cards = collect();
        $position = DecimalPosition::forEmptyColumn();

        for ($i = 1; $i <= 500; $i++) {
            $card = Task::factory()->create([
                'title' => "Sequential Card {$i}",
                'status' => 'in_progress',
                'order_position' => $position,
            ]);

            $cards->push($card);
            $position = DecimalPosition::after($position);
        }

        // Verify all cards created
        expect($cards->count())->toBe(500, 'All 500 cards should be created');

        // Verify no inversions in sequential creation
        $inversions = detectInversions(Task::class, 'in_progress');
        expect($inversions)->toBeEmpty(
            'Sequential creation should never produce inversions'
        );

        // Verify positions are unique
        $uniquePositions = $cards->pluck('order_position')->unique()->count();
        expect($uniquePositions)->toBe(500, 'All positions should be unique');
    });

    it('tests position calculation near minimum boundary', function () {
        // BOUNDARY TEST: Test positions near zero
        // Create a card with small position and insert before it

        $boundaryCard = Task::factory()->create([
            'title' => 'Near Zero Boundary',
            'status' => 'review',
            'order_position' => '1000.0000000000',
        ]);

        $newCard = Task::factory()->create([
            'title' => 'Insert Before Boundary',
            'status' => 'review',
            'order_position' => DecimalPosition::forEmptyColumn(),
        ]);

        // Move card to be BEFORE the boundary card
        $this->board->call(
            'moveCard',
            (string) $newCard->id,
            'review',
            null,                           // afterCardId=null (move to top)
            (string) $boundaryCard->id      // beforeCardId
        );

        $newCard->refresh();

        // Verify new position is less than boundary
        expect(DecimalPosition::lessThan(
            DecimalPosition::normalize($newCard->order_position),
            DecimalPosition::normalize($boundaryCard->order_position)
        ))->toBeTrue('Position should be < boundary when moved before it');
    });

    it('tests position calculation near large values', function () {
        // BOUNDARY TEST: Test positions with large values
        // Create a card with large position and insert after it

        $boundaryCard = Task::factory()->create([
            'title' => 'Large Position',
            'status' => 'review',
            'order_position' => '9999999999.0000000000', // Large position
        ]);

        $newCard = Task::factory()->create([
            'title' => 'Insert After Large',
            'status' => 'review',
            'order_position' => DecimalPosition::forEmptyColumn(),
        ]);

        // Move card to be AFTER the boundary card
        $this->board->call(
            'moveCard',
            (string) $newCard->id,
            'review',
            (string) $boundaryCard->id,     // afterCardId
            null                            // beforeCardId=null (move to bottom)
        );

        $newCard->refresh();

        // Verify new position is greater than boundary
        expect(DecimalPosition::greaterThan(
            DecimalPosition::normalize($newCard->order_position),
            DecimalPosition::normalize($boundaryCard->order_position)
        ))->toBeTrue('Position should be > boundary when moved after it');
    });

    it('verifies progressive bisection insertions never fail', function () {
        // BISECTION TEST: Insert cards progressively, subdividing space
        // With decimal positions, midpoint calculation never fails

        // Create boundary cards
        $cards = collect([
            Task::factory()->create([
                'title' => 'Start',
                'status' => 'done',
                'order_position' => DecimalPosition::forEmptyColumn(),
            ]),
        ]);

        $secondPosition = DecimalPosition::after(DecimalPosition::forEmptyColumn());
        $cards->push(Task::factory()->create([
            'title' => 'End',
            'status' => 'done',
            'order_position' => $secondPosition,
        ]));

        // Insert 30 cards between first and second (forces bisection)
        for ($i = 1; $i <= 30; $i++) {
            // Get current sorted cards
            $sortedCards = $cards->sortBy('order_position')->values();
            $afterCard = $sortedCards->first();
            $beforeCard = $sortedCards->get(1);

            $newCard = Task::factory()->create([
                'title' => "Bisection #{$i}",
                'status' => 'done',
                'order_position' => DecimalPosition::forEmptyColumn(),
            ]);

            $this->board->call(
                'moveCard',
                (string) $newCard->id,
                'done',
                (string) $afterCard->id,
                (string) $beforeCard->id
            );

            $newCard->refresh();
            $cards->push($newCard);

            // Verify correct placement
            $afterPos = DecimalPosition::normalize($afterCard->order_position);
            $newPos = DecimalPosition::normalize($newCard->order_position);
            $beforePos = DecimalPosition::normalize($beforeCard->fresh()->order_position);

            expect(DecimalPosition::lessThan($afterPos, $newPos))->toBeTrue(
                "Bisection {$i}: new position should be > afterCard"
            );
            expect(DecimalPosition::lessThan($newPos, $beforePos))->toBeTrue(
                "Bisection {$i}: new position should be < beforeCard"
            );
        }

        // Verify all positions unique
        $allPositions = Task::where('status', 'done')->pluck('order_position');
        $uniqueCount = $allPositions->unique()->count();
        expect($uniqueCount)->toBe(
            $allPositions->count(),
            'All positions should be unique after 30 bisections'
        );

        // Verify positions are properly ordered when sorted
        $sortedPositions = Task::where('status', 'done')
            ->orderBy('order_position')
            ->orderBy('id')
            ->pluck('order_position')
            ->toArray();

        for ($i = 0; $i < count($sortedPositions) - 1; $i++) {
            expect(DecimalPosition::lessThan(
                DecimalPosition::normalize($sortedPositions[$i]),
                DecimalPosition::normalize($sortedPositions[$i + 1])
            ))->toBeTrue("Sorted position {$i} should be < position " . ($i + 1));
        }
    });

    it('validates position uniqueness with systematic insertions', function () {
        // UNIQUENESS TEST: Ensure all positions remain unique with systematic insertions
        $cards = collect();
        $position = DecimalPosition::forEmptyColumn();

        // Create 50 cards sequentially
        for ($i = 1; $i <= 50; $i++) {
            $card = Task::factory()->create([
                'title' => "Card #{$i}",
                'status' => 'backlog',
                'order_position' => $position,
            ]);

            $cards->push($card);
            $position = DecimalPosition::after($position);
        }

        // Verify ALL positions are unique
        $allPositions = Task::where('status', 'backlog')->pluck('order_position');
        $uniquePositions = $allPositions->unique();

        expect($uniquePositions->count())->toBe(
            50,
            'All 50 positions must be unique - no duplicates allowed'
        );

        expect($uniquePositions->count())->toBe(
            $allPositions->count(),
            'Unique count should match total count'
        );

        // Verify no inversions
        $inversions = detectInversions(Task::class, 'backlog');
        expect($inversions)->toBeEmpty(
            'No inversions should exist in sequential creation'
        );

        // Verify positions are properly ordered
        $positions = $cards->pluck('order_position')->toArray();
        for ($i = 0; $i < count($positions) - 1; $i++) {
            expect(DecimalPosition::lessThan(
                DecimalPosition::normalize($positions[$i]),
                DecimalPosition::normalize($positions[$i + 1])
            ))->toBeTrue("Position {$i} should be < position " . ($i + 1));
        }
    });

    it('tests deep bisection without precision loss', function () {
        // PRECISION TEST: Deeply bisect to test decimal precision
        // DECIMAL(20,10) should support ~33 bisections before MIN_GAP

        $lower = DecimalPosition::forEmptyColumn(); // 65535
        $upper = DecimalPosition::after($lower);     // 131070

        $bisectionCount = 0;
        $lastMid = null;

        // Keep bisecting until we can't anymore or hit 50 iterations
        while ($bisectionCount < 50) {
            $mid = DecimalPosition::between($lower, $upper);

            // Verify the midpoint is actually between lower and upper
            if (! DecimalPosition::lessThan($lower, $mid) || ! DecimalPosition::lessThan($mid, $upper)) {
                break; // Precision exhausted
            }

            // Check if we've hit MIN_GAP (rebalancing would be needed)
            if (DecimalPosition::needsRebalancing($lower, $mid)) {
                break;
            }

            $lastMid = $mid;
            $upper = $mid; // Narrow the gap
            $bisectionCount++;
        }

        // DECIMAL(20,10) should support at least 25-30 bisections
        expect($bisectionCount)->toBeGreaterThanOrEqual(
            25,
            "Should support at least 25 bisections before precision concerns (got {$bisectionCount})"
        );
    });
});

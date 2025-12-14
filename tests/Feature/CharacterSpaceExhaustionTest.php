<?php

use Livewire\Livewire;
use Relaticle\Flowforge\Services\Rank;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

beforeEach(function () {
    $this->board = Livewire::test(TestBoard::class);
});

describe('Character Space Exhaustion Tests - Finding Breaking Points', function () {
    it('stresses 100 sequential insertions at bottom of column', function () {
        // STRESS TEST: Test Rank algorithm with 100 sequential insertions
        // Expected: Position strings grow linearly, all positions unique
        // This tests Rank::after() under heavy sequential usage

        $cards = collect();
        $lengthMetrics = [];
        $maxLengthObserved = 0;

        // Create 100 cards sequentially at bottom
        for ($i = 1; $i <= 100; $i++) {
            $rank = $i === 1
                ? Rank::forEmptySequence()
                : Rank::after(Rank::fromString($cards->last()->order_position));

            $card = Task::factory()->create([
                'title' => "Sequential #{$i}",
                'status' => 'todo',
                'order_position' => $rank->get(),
            ]);

            $cards->push($card);

            $posLength = strlen($card->order_position);
            $maxLengthObserved = max($maxLengthObserved, $posLength);

            // Track metrics at checkpoints
            if (in_array($i, [10, 20, 50, 75, 100])) {
                $allPositions = $cards->pluck('order_position')->map(fn ($pos) => strlen($pos));

                $lengthMetrics[$i] = [
                    'avg_length' => round($allPositions->avg(), 2),
                    'max_length' => $allPositions->max(),
                    'min_length' => $allPositions->min(),
                    'latest_pos' => $card->order_position,
                ];

                // Check for inversions at each checkpoint
                $inversions = detectInversions(Task::class, 'todo');
                expect($inversions)->toBeEmpty(
                    "No inversions after {$i} sequential insertions"
                );
            }
        }

        // Verify all positions are unique
        $uniquePositions = $cards->pluck('order_position')->unique()->count();
        expect($uniquePositions)->toBe(100, 'All 100 positions should be unique');

        // Verify max length is reasonable
        expect($maxLengthObserved)->toBeLessThan(
            10,
            'Position strings should stay short for sequential insertions'
        );

        dump('=== SEQUENTIAL INSERTION METRICS (100 cards) ===', $lengthMetrics);
        dump("Max length: {$maxLengthObserved} chars");
    });

    it('monitors position string length growth patterns with 500 sequential cards', function () {
        // STRESS TEST: Create 500 cards sequentially (not insertions)
        // Expected: Linear growth in position strings
        // This tests the Rank::after() method under heavy sequential usage

        $cards = collect();
        $lengthMetrics = [];

        for ($i = 1; $i <= 500; $i++) {
            $rank = $i === 1
                ? Rank::forEmptySequence()
                : Rank::after(Rank::fromString($cards->last()->order_position));

            $card = Task::factory()->create([
                'title' => "Sequential Card {$i}",
                'status' => 'in_progress',
                'order_position' => $rank->get(),
            ]);

            $cards->push($card);

            // Track metrics every 50 cards
            if ($i % 50 === 0) {
                $positions = $cards->pluck('order_position')->map(fn ($pos) => strlen($pos));

                $lengthMetrics[$i] = [
                    'avg_length' => round($positions->avg(), 2),
                    'max_length' => $positions->max(),
                    'min_length' => $positions->min(),
                    'latest_pos' => $card->order_position,
                    'latest_length' => strlen($card->order_position),
                ];
            }
        }

        // Verify all cards created
        expect($cards->count())->toBe(500, 'All 500 cards should be created');

        // Verify average length stays reasonable (< 10 chars for sequential)
        $finalAvg = collect($cards->pluck('order_position'))->map(fn ($pos) => strlen($pos))->avg();
        expect($finalAvg)->toBeLessThan(
            10,
            'Average position length should stay reasonable for sequential cards'
        );

        // Verify no inversions in sequential creation
        $inversions = detectInversions(Task::class, 'in_progress');
        expect($inversions)->toBeEmpty(
            'Sequential creation should never produce inversions'
        );

        // Verify positions are unique
        $uniquePositions = $cards->pluck('order_position')->unique()->count();
        expect($uniquePositions)->toBe(500, 'All positions should be unique');

        // Dump metrics for analysis
        dump('=== SEQUENTIAL GROWTH METRICS (500 cards) ===', $lengthMetrics);
    });

    it('tests character space at MIN_CHAR boundary', function () {
        // BOUNDARY TEST: Test positions near MIN_CHAR ('0')
        // Create a card with position just above MIN_CHAR and insert before it

        $boundaryCard = Task::factory()->create([
            'title' => 'Near Min Boundary',
            'status' => 'review',
            'order_position' => '1', // Just above MIN_CHAR ('0')
        ]);

        $newCard = Task::factory()->create([
            'title' => 'Insert Before Min Boundary',
            'status' => 'review',
            'order_position' => Rank::forEmptySequence()->get(),
        ]);

        // Move card to be BEFORE the boundary card (should create position < '1')
        $this->board->call(
            'moveCard',
            (string) $newCard->id,
            'review',
            null,                           // afterCardId=null (move to top)
            (string) $boundaryCard->id      // beforeCardId
        );

        $newCard->refresh();

        // Verify new position is less than '1'
        expect(strcmp($newCard->order_position, '1'))->toBeLessThan(
            0,
            'Position should be < "1" when moved before it'
        );

        // Verify position doesn't end with MIN_CHAR ('0')
        $lastChar = substr($newCard->order_position, -1);
        expect($lastChar)->not->toBe(
            Rank::MIN_CHAR,
            'Position should not end with MIN_CHAR'
        );

        dump('Position near MIN_CHAR boundary:', $newCard->order_position);
    });

    it('tests character space at MAX_CHAR boundary', function () {
        // BOUNDARY TEST: Test positions near MAX_CHAR ('z')
        // Create a card with position just below MAX_CHAR and insert after it

        $boundaryCard = Task::factory()->create([
            'title' => 'Near Max Boundary',
            'status' => 'review',
            'order_position' => 'y', // Just below MAX_CHAR ('z')
        ]);

        $newCard = Task::factory()->create([
            'title' => 'Insert After Max Boundary',
            'status' => 'review',
            'order_position' => Rank::forEmptySequence()->get(),
        ]);

        // Move card to be AFTER the boundary card (should create position > 'y')
        $this->board->call(
            'moveCard',
            (string) $newCard->id,
            'review',
            (string) $boundaryCard->id,     // afterCardId
            null                            // beforeCardId=null (move to bottom)
        );

        $newCard->refresh();

        // Verify new position is greater than 'y'
        expect(strcmp($newCard->order_position, 'y'))->toBeGreaterThan(
            0,
            'Position should be > "y" when moved after it'
        );

        // Verify position is valid (< MAX_CHAR or extended properly)
        expect(strlen($newCard->order_position))->toBeLessThan(
            Rank::MAX_RANK_LEN,
            'Position should be under MAX_RANK_LEN'
        );

        dump('Position near MAX_CHAR boundary:', $newCard->order_position);
    });

    it('verifies character space exhaustion with progressive insertions', function () {
        // EXHAUSTION TEST: Insert cards progressively, subdividing space
        // Expected: Position strings grow longer as we subdivide the space

        // Create boundary cards
        $cards = collect([
            Task::factory()->create([
                'title' => 'Start',
                'status' => 'done',
                'order_position' => 'a',
            ]),
            Task::factory()->create([
                'title' => 'End',
                'status' => 'done',
                'order_position' => 'b',
            ]),
        ]);

        $insertions = [];
        $maxLength = 0;

        // Insert 20 cards, each splitting existing space
        for ($i = 1; $i <= 20; $i++) {
            // Pick two adjacent cards to insert between
            $sortedCards = $cards->sortBy('order_position')->values();
            $pairIndex = ($i - 1) % ($sortedCards->count() - 1);
            $afterCard = $sortedCards->get($pairIndex);
            $beforeCard = $sortedCards->get($pairIndex + 1);

            $newCard = Task::factory()->create([
                'title' => "Subdivision #{$i}",
                'status' => 'done',
                'order_position' => Rank::forEmptySequence()->get(),
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

            $posLength = strlen($newCard->order_position);
            $maxLength = max($maxLength, $posLength);

            $insertions[] = [
                'insertion' => $i,
                'between' => [$afterCard->title, $beforeCard->title],
                'position' => $newCard->order_position,
                'length' => $posLength,
            ];

            // Verify correct placement
            expect(strcmp($afterCard->order_position, $newCard->order_position))->toBeLessThan(0);
            expect(strcmp($newCard->order_position, $beforeCard->order_position))->toBeLessThan(0);
        }

        // Verify all positions unique
        $allPositions = Task::where('status', 'done')->pluck('order_position');
        $uniqueCount = $allPositions->unique()->count();
        expect($uniqueCount)->toBe(
            $allPositions->count(),
            'All positions should be unique'
        );

        // Verify positions are properly ordered when sorted
        $sortedPositions = Task::where('status', 'done')
            ->orderBy('order_position')
            ->pluck('order_position')
            ->toArray();

        for ($i = 0; $i < count($sortedPositions) - 1; $i++) {
            expect(strcmp($sortedPositions[$i], $sortedPositions[$i + 1]))->toBeLessThan(
                0,
                "Sorted position {$i} should be < position " . ($i + 1)
            );
        }

        dump('=== PROGRESSIVE SUBDIVISION INSERTIONS ===');
        dump('Sample insertions:', array_slice($insertions, 0, 10));
        dump("Max length: {$maxLength} chars");
    });

    it('validates position uniqueness with systematic insertions', function () {
        // UNIQUENESS TEST: Ensure all positions remain unique with systematic insertions
        // Create base cards
        $cards = collect();

        // Create 50 cards sequentially
        for ($i = 1; $i <= 50; $i++) {
            $rank = $i === 1
                ? Rank::forEmptySequence()
                : Rank::after(Rank::fromString($cards->last()->order_position));

            $card = Task::factory()->create([
                'title' => "Card #{$i}",
                'status' => 'backlog',
                'order_position' => $rank->get(),
            ]);

            $cards->push($card);
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
            expect(strcmp($positions[$i], $positions[$i + 1]))->toBeLessThan(
                0,
                "Position {$i} should be < position " . ($i + 1)
            );
        }
    });
});

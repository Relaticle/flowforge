<?php

use Livewire\Livewire;
use Relaticle\Flowforge\Services\Rank;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

beforeEach(function () {
    $this->board = Livewire::test(TestBoard::class);
});

// Note: detectInversions() helper function is defined in ParameterOrderMutationTest.php

describe('Real-World Position Inversion Scenarios', function () {
    it('reproduces inversions from rapid sequential moves between same positions', function () {
        // Create 5 cards in todo column
        $cards = collect();
        for ($i = 1; $i <= 5; $i++) {
            $cards->push(Task::factory()->create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => Rank::forEmptySequence()->get(),
            ]));
        }

        // Simulate rapid back-and-forth movements (simulates user indecision)
        $targetCard = $cards->get(2);
        $bugReproduced = false;

        for ($j = 0; $j < 10; $j++) {
            try {
                // Move card 3 between card 1 and card 2 repeatedly
                $this->board->call(
                    'moveCard',
                    (string) $targetCard->id,
                    'todo',
                    (string) $cards->get(0)->id, // afterCardId
                    (string) $cards->get(1)->id  // beforeCardId
                );

                // Refresh positions
                $cards = $cards->map(fn ($card) => $card->fresh());
            } catch (\Relaticle\Flowforge\Exceptions\PrevGreaterThanOrEquals $e) {
                dump("BUG REPRODUCED at move #{$j}: " . $e->getMessage());
                $bugReproduced = true;

                break;
            }
        }

        // Check for inversions if no exception was thrown
        if (! $bugReproduced) {
            $inversions = detectInversions(Task::class, 'todo');

            if (count($inversions) > 0) {
                dump('INVERSION REPRODUCED!', $inversions);
                $bugReproduced = true;
            }
        }

        // This test succeeds when it reproduces the bug
        expect($bugReproduced)->toBeTrue('Successfully reproduced position inversion bug from rapid moves');
    });

    it('reproduces inversions from inserting many cards between two existing cards', function () {
        // Create initial boundary cards
        $firstCard = Task::factory()->create([
            'title' => 'First Card',
            'status' => 'todo',
            'order_position' => Rank::forEmptySequence()->get(), // Gets 'm'
        ]);

        $lastCard = Task::factory()->create([
            'title' => 'Last Card',
            'status' => 'todo',
            'order_position' => Rank::after(Rank::fromString($firstCard->order_position))->get(),
        ]);

        // Insert 50 cards between these two
        $insertedCards = collect();
        $bugReproduced = false;

        for ($i = 1; $i <= 50; $i++) {
            $newCard = Task::factory()->create([
                'title' => "Inserted Card {$i}",
                'status' => 'todo',
                'order_position' => Rank::forEmptySequence()->get(), // Temporary position
            ]);

            try {
                $this->board->call(
                    'moveCard',
                    (string) $newCard->id,
                    'todo',
                    (string) $firstCard->id,
                    (string) $lastCard->id
                );

                $newCard->refresh();
                $insertedCards->push($newCard);

                // Check after every 10 insertions
                if ($i % 10 === 0) {
                    $inversions = detectInversions(Task::class, 'todo');
                    if (count($inversions) > 0) {
                        dump("INVERSION DETECTED after {$i} insertions!", $inversions);
                        $bugReproduced = true;

                        break;
                    }
                }
            } catch (\Relaticle\Flowforge\Exceptions\PrevGreaterThanOrEquals $e) {
                dump("BUG REPRODUCED at insertion #{$i}: " . $e->getMessage());
                $bugReproduced = true;

                break;
            }
        }

        // Final check if no bug found yet
        if (! $bugReproduced) {
            $inversions = detectInversions(Task::class, 'todo');
            if (count($inversions) > 0) {
                dump('INVERSIONS DETECTED in final check!', $inversions);
                $bugReproduced = true;
            }
        }

        // This test succeeds when it reproduces the bug
        expect($bugReproduced)->toBeTrue('Successfully reproduced position inversion bug from many insertions');
    });

    it('reproduces inversions from concurrent-like operations (simulated)', function () {
        // Create 10 cards
        $cards = collect();
        for ($i = 1; $i <= 10; $i++) {
            $lastRank = $i === 1
                ? Rank::forEmptySequence()
                : Rank::after(Rank::fromString($cards->last()->order_position));

            $cards->push(Task::factory()->create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => $lastRank->get(),
            ]));
        }

        // Simulate concurrent operations: multiple cards moving at "same time"
        // We'll move 3 different cards to 3 different positions simultaneously (in succession)
        $operations = [
            ['card' => $cards->get(2), 'after' => $cards->get(5), 'before' => $cards->get(6)],
            ['card' => $cards->get(7), 'after' => $cards->get(1), 'before' => $cards->get(2)],
            ['card' => $cards->get(4), 'after' => $cards->get(8), 'before' => $cards->get(9)],
        ];

        $bugReproduced = false;

        foreach ($operations as $index => $op) {
            try {
                $this->board->call(
                    'moveCard',
                    (string) $op['card']->id,
                    'todo',
                    (string) $op['after']->id,
                    (string) $op['before']->id
                );
            } catch (\Relaticle\Flowforge\Exceptions\PrevGreaterThanOrEquals $e) {
                dump("BUG REPRODUCED at operation #{$index}: " . $e->getMessage());
                $bugReproduced = true;

                break;
            }
        }

        // Check for inversions if no exception was thrown
        if (! $bugReproduced) {
            $inversions = detectInversions(Task::class, 'todo');

            if (count($inversions) > 0) {
                dump('CONCURRENT OPERATIONS CAUSED INVERSIONS!', $inversions);
                $bugReproduced = true;
            }
        }

        // This test succeeds when it reproduces the bug
        expect($bugReproduced)->toBeTrue('Successfully reproduced position inversion bug from concurrent operations');
    });

    it('stress tests position system with 100 random moves', function () {
        // Create 20 cards
        $cards = collect();
        for ($i = 1; $i <= 20; $i++) {
            $lastRank = $i === 1
                ? Rank::forEmptySequence()
                : Rank::after(Rank::fromString($cards->last()->order_position));

            $cards->push(Task::factory()->create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => $lastRank->get(),
            ]));
        }

        // Perform 100 random moves
        $bugReproduced = false;
        for ($move = 1; $move <= 100; $move++) {
            // Pick random card and random position
            $cardToMove = $cards->random();
            $otherCards = $cards->where('id', '!=', $cardToMove->id);

            if ($otherCards->count() < 2) {
                continue;
            }

            $afterCard = $otherCards->random();
            $beforeCard = $otherCards->where('id', '!=', $afterCard->id)->random();

            try {
                $this->board->call(
                    'moveCard',
                    (string) $cardToMove->id,
                    'todo',
                    (string) $afterCard->id,
                    (string) $beforeCard->id
                );

                // Refresh all cards
                $cards = $cards->map(fn ($card) => $card->fresh());

                // Check for inversions every 20 moves
                if ($move % 20 === 0) {
                    $inversions = detectInversions(Task::class, 'todo');
                    if (count($inversions) > 0) {
                        dump("INVERSION FOUND after move #{$move}!", $inversions);
                        $bugReproduced = true;

                        break;
                    }
                }
            } catch (\Exception $e) {
                // If we get PrevGreaterThanOrEquals exception, we've reproduced the bug!
                if (str_contains($e->getMessage(), 'Previous Rank')) {
                    dump("BUG REPRODUCED at move #{$move}: " . $e->getMessage());
                    $bugReproduced = true;

                    break;
                }

                throw $e;
            }
        }

        // Final check if no bug found yet
        if (! $bugReproduced) {
            $inversions = detectInversions(Task::class, 'todo');

            if (count($inversions) > 0) {
                dump('FINAL CHECK: Inversions detected!', $inversions);
                $bugReproduced = true;
            }
        }

        // This test succeeds when it reproduces the bug
        expect($bugReproduced)->toBeTrue('Successfully reproduced position inversion bug from random moves');
    });

    it('tests the exact scenario from production data - now fixed', function () {
        // Based on diagnostic output, we know these inversions existed:
        // Card #019b18e5-a8b6-7350-9a8c-6534f48280df (pos: "VU") comes before
        // Card #019b18e5-a8b7-73ec-9be1-89c01264fab3 (pos: "T")

        // Create positions that would previously cause issues
        $card1 = Task::factory()->create([
            'title' => 'Card with position T',
            'status' => 'review',
            'order_position' => 'T',
        ]);

        $card2 = Task::factory()->create([
            'title' => 'Card with position VU',
            'status' => 'review',
            'order_position' => 'VU',
        ]);

        // According to strcmp, "T" < "VU" - these are in correct lexicographic order
        $comparison = strcmp('T', 'VU');
        expect($comparison)->toBeLessThan(0, 'T should be lexicographically less than VU');

        // Now try to move a card between them - with the fix, this should succeed
        $newCard = Task::factory()->create([
            'title' => 'New card to insert',
            'status' => 'review',
            'order_position' => Rank::forEmptySequence()->get(),
        ]);

        // With the fix, inserting between properly ordered positions should work
        $this->board->call(
            'moveCard',
            (string) $newCard->id,
            'review',
            (string) $card1->id, // afterCardId (position "T")
            (string) $card2->id  // beforeCardId (position "VU")
        );

        $newCard->refresh();

        // Verify the new card is positioned correctly between T and VU
        expect(strcmp($card1->order_position, $newCard->order_position))->toBeLessThan(0, 'New card should be after T');
        expect(strcmp($newCard->order_position, $card2->order_position))->toBeLessThan(0, 'New card should be before VU');
    });

    it('verifies rank string growth leads to inversions', function () {
        // Theory: After many insertions, rank strings grow longer
        // and eventually two adjacent ranks can become inverted

        $prevCard = Task::factory()->create([
            'title' => 'Boundary Card 1',
            'status' => 'todo',
            'order_position' => 'a',
        ]);

        $nextCard = Task::factory()->create([
            'title' => 'Boundary Card 2',
            'status' => 'todo',
            'order_position' => 'b',
        ]);

        $positions = [$prevCard->order_position, $nextCard->order_position];
        $bugReproduced = false;

        // Insert 100 cards between 'a' and 'b'
        for ($i = 1; $i <= 100; $i++) {
            $newCard = Task::factory()->create([
                'title' => "Insert {$i}",
                'status' => 'todo',
                'order_position' => Rank::forEmptySequence()->get(),
            ]);

            // Get current boundary cards
            $prevCard = $prevCard->fresh();
            $nextCard = $nextCard->fresh();

            try {
                $this->board->call(
                    'moveCard',
                    (string) $newCard->id,
                    'todo',
                    (string) $prevCard->id,
                    (string) $nextCard->id
                );

                $newCard->refresh();
                $positions[] = $newCard->order_position;

                // Track position string lengths
                if ($i % 25 === 0) {
                    $avgLength = collect($positions)->avg(fn ($p) => strlen($p));
                    dump("After {$i} insertions, average position length: {$avgLength}");
                }
            } catch (\Relaticle\Flowforge\Exceptions\PrevGreaterThanOrEquals $e) {
                dump("BUG REPRODUCED at insertion #{$i}: " . $e->getMessage());
                dump('Positions created so far:', $positions);
                $bugReproduced = true;

                break;
            }
        }

        // Check if any inversions exist (if no exception was thrown)
        if (! $bugReproduced) {
            $inversions = detectInversions(Task::class, 'todo');

            if (count($inversions) > 0) {
                dump('Position lengths that caused inversions:', collect($positions)->map(fn ($p) => strlen($p))->all());
                $bugReproduced = true;
            }
        }

        // This test succeeds when it reproduces the bug
        expect($bugReproduced)->toBeTrue('Successfully reproduced position inversion bug from rank string growth');
    });
});

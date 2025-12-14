<?php

use Livewire\Livewire;
use Relaticle\Flowforge\Exceptions\PrevGreaterThanOrEquals;
use Relaticle\Flowforge\Services\Rank;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

beforeEach(function () {
    $this->board = Livewire::test(TestBoard::class);
});

/**
 * Helper to detect position inversions in a column
 */
function detectInversions(string $modelClass, string $columnValue, string $positionField = 'order_position'): array
{
    $records = $modelClass::query()
        ->where('status', $columnValue)
        ->whereNotNull($positionField)
        ->orderBy('id')
        ->get();

    $inversions = [];
    for ($i = 0; $i < $records->count() - 1; $i++) {
        $current = $records[$i];
        $next = $records[$i + 1];

        $currentPos = $current->getAttribute($positionField);
        $nextPos = $next->getAttribute($positionField);

        if (strcmp($currentPos, $nextPos) >= 0) {
            $inversions[] = [
                'current_id' => $current->id,
                'current_pos' => $currentPos,
                'next_id' => $next->id,
                'next_pos' => $nextPos,
            ];
        }
    }

    return $inversions;
}

describe('Parameter Order Mutation Tests - Prove the Fix Matters', function () {
    it('documents correct parameter order after fix', function () {
        // DOCUMENTATION TEST: Shows how parameters work AFTER the fix
        // This test verifies the fix is working correctly

        $cardA = Task::factory()->create([
            'title' => 'Card A',
            'status' => 'todo',
            'order_position' => 'a',
        ]);

        $cardB = Task::factory()->create([
            'title' => 'Card B',
            'status' => 'todo',
            'order_position' => 'b',
        ]);

        $cardC = Task::factory()->create([
            'title' => 'Card C',
            'status' => 'todo',
            'order_position' => 'c',
        ]);

        $newCard = Task::factory()->create([
            'title' => 'New Card',
            'status' => 'todo',
            'order_position' => 'm',
        ]);

        // CORRECT PARAMETER ORDER (after fix):
        // moveCard(cardId, column, afterCardId, beforeCardId)
        // afterCardId = card BEFORE the new position (visually above)
        // beforeCardId = card AFTER the new position (visually below)

        // Want: A < NewCard < B
        $this->board->call(
            'moveCard',
            (string) $newCard->id,
            'todo',
            (string) $cardA->id,  // afterCardId: card A (comes before new position)
            (string) $cardB->id   // beforeCardId: card B (comes after new position)
        );

        $newCard->refresh();

        // Verify correct placement
        $isCorrect = strcmp($cardA->order_position, $newCard->order_position) < 0
                  && strcmp($newCard->order_position, $cardB->order_position) < 0;

        expect($isCorrect)->toBeTrue(
            'With current fix, card should be between A and B'
        );
    });

    it('proves PHP logic with swapped parameters creates exception', function () {
        // MUTATION TEST: Simulates OLD BROKEN PHP logic
        // This shows what happens when betweenRanks parameters are swapped

        $cardA = Task::factory()->create([
            'status' => 'todo',
            'order_position' => 'a',
        ]);

        $cardB = Task::factory()->create([
            'status' => 'todo',
            'order_position' => 'b',
        ]);

        // SIMULATE OLD BROKEN PHP LOGIC:
        // Used to be: betweenRanks($beforePos, $afterPos) - WRONG ORDER
        // This throws exception because 'b' > 'a'

        expect(function () use ($cardA, $cardB) {
            Rank::betweenRanks(
                Rank::fromString($cardB->order_position),  // 'b' as prev (WRONG)
                Rank::fromString($cardA->order_position)   // 'a' as next (WRONG)
            );
        })->toThrow(PrevGreaterThanOrEquals::class);
    });

    it('validates parameter semantic meanings under stress', function () {
        // Create 10 cards in sequence
        $cards = collect();
        for ($i = 0; $i < 10; $i++) {
            $rank = $i === 0
                ? Rank::forEmptySequence()
                : Rank::after(Rank::fromString($cards->last()->order_position));

            $cards->push(Task::factory()->create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => $rank->get(),
            ]));
        }

        // Test EVERY adjacent pair with correct parameter semantics
        for ($i = 0; $i < $cards->count() - 1; $i++) {
            $newCard = Task::factory()->create([
                'title' => "New Card {$i}",
                'status' => 'todo',
                'order_position' => 'm',
            ]);

            $afterCard = $cards->get($i);
            $beforeCard = $cards->get($i + 1);

            // CORRECT ORDER: afterCardId, beforeCardId
            // afterCard = visually ABOVE (smaller position)
            // beforeCard = visually BELOW (larger position)
            $this->board->call(
                'moveCard',
                (string) $newCard->id,
                'todo',
                (string) $afterCard->id,    // 3rd param: card BEFORE new position
                (string) $beforeCard->id    // 4th param: card AFTER new position
            );

            $newCard->refresh();
            $afterCard = $afterCard->fresh();
            $beforeCard = $beforeCard->fresh();

            // Invariant: afterCard < newCard < beforeCard
            expect(strcmp($afterCard->order_position, $newCard->order_position))->toBeLessThan(
                0,
                "After inserting between {$afterCard->title} and {$beforeCard->title}, " .
                'new card position should be > afterCard'
            );

            expect(strcmp($newCard->order_position, $beforeCard->order_position))->toBeLessThan(
                0,
                "After inserting between {$afterCard->title} and {$beforeCard->title}, " .
                'new card position should be < beforeCard'
            );
        }
    });

    it('verifies correct behavior for all edge cases', function () {
        $cards = collect(['a', 'b', 'c'])->map(
            fn ($pos) => Task::factory()->create([
                'status' => 'todo',
                'order_position' => $pos,
            ])
        );

        // Edge Case 1: Move to TOP (afterCardId=null, beforeCardId=firstCard)
        $newCard1 = Task::factory()->create(['status' => 'todo', 'order_position' => 'm']);
        $this->board->call(
            'moveCard',
            (string) $newCard1->id,
            'todo',
            null,                           // afterCardId=null (no card before)
            (string) $cards->get(0)->id     // beforeCardId=first card
        );
        $newCard1->refresh();
        expect(strcmp($newCard1->order_position, $cards->get(0)->order_position))->toBeLessThan(
            0,
            'Card moved to top should have position < first card'
        );

        // Edge Case 2: Move to BOTTOM (afterCardId=lastCard, beforeCardId=null)
        $newCard2 = Task::factory()->create(['status' => 'todo', 'order_position' => 'm']);
        $this->board->call(
            'moveCard',
            (string) $newCard2->id,
            'todo',
            (string) $cards->last()->id,    // afterCardId=last card
            null                            // beforeCardId=null (no card after)
        );
        $newCard2->refresh();
        expect(strcmp($cards->last()->order_position, $newCard2->order_position))->toBeLessThan(
            0,
            'Card moved to bottom should have position > last card'
        );

        // Edge Case 3: Move BETWEEN (both non-null)
        $newCard3 = Task::factory()->create(['status' => 'todo', 'order_position' => 'm']);
        $this->board->call(
            'moveCard',
            (string) $newCard3->id,
            'todo',
            (string) $cards->get(0)->id,    // afterCardId=first card
            (string) $cards->get(1)->id     // beforeCardId=second card
        );
        $newCard3->refresh();
        expect(strcmp($cards->get(0)->order_position, $newCard3->order_position))->toBeLessThan(
            0,
            'Card moved between should have position > first card'
        );
        expect(strcmp($newCard3->order_position, $cards->get(1)->order_position))->toBeLessThan(
            0,
            'Card moved between should have position < second card'
        );
    });

    it('stresses parameter order with rapid alternating insertions', function () {
        $cards = collect();
        for ($i = 0; $i < 5; $i++) {
            $rank = $i === 0
                ? Rank::forEmptySequence()
                : Rank::after(Rank::fromString($cards->last()->order_position));

            $cards->push(Task::factory()->create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => $rank->get(),
            ]));
        }

        // Rapidly insert 20 cards, alternating between positions
        for ($round = 0; $round < 20; $round++) {
            $newCard = Task::factory()->create([
                'title' => "Rapid Card {$round}",
                'status' => 'todo',
                'order_position' => 'm',
            ]);

            // Alternate between inserting at different positions
            $targetIndex = $round % ($cards->count() - 1);
            $afterCard = $cards->get($targetIndex);
            $beforeCard = $cards->get($targetIndex + 1);

            $this->board->call(
                'moveCard',
                (string) $newCard->id,
                'todo',
                (string) $afterCard->id,
                (string) $beforeCard->id
            );

            $newCard->refresh();

            // Verify correct placement EVERY time
            expect(strcmp($afterCard->fresh()->order_position, $newCard->order_position))->toBeLessThan(
                0,
                "Round {$round}: Card should be after {$afterCard->title}"
            );
            expect(strcmp($newCard->order_position, $beforeCard->fresh()->order_position))->toBeLessThan(
                0,
                "Round {$round}: Card should be before {$beforeCard->title}"
            );
        }
    });

    // NOTE: More aggressive stress testing of random moves is covered in
    // ConcurrentOperationStressTest.php - this test was too unreliable here
});

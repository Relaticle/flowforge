<?php

use Livewire\Livewire;
use Relaticle\Flowforge\Services\DecimalPosition;
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

        $currentPos = DecimalPosition::normalize($current->getAttribute($positionField));
        $nextPos = DecimalPosition::normalize($next->getAttribute($positionField));

        // Check if positions are inverted (should be current < next)
        if (DecimalPosition::compare($currentPos, $nextPos) >= 0) {
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

describe('Parameter Order Mutation Tests - Decimal Positioning', function () {
    it('documents correct parameter order with decimal positions', function () {
        // DOCUMENTATION TEST: Shows how parameters work with decimal positions
        // This test verifies the fix is working correctly

        $cardA = Task::factory()->create([
            'title' => 'Card A',
            'status' => 'todo',
            'order_position' => '65535.0000000000',
        ]);

        $cardB = Task::factory()->create([
            'title' => 'Card B',
            'status' => 'todo',
            'order_position' => '131070.0000000000',
        ]);

        $cardC = Task::factory()->create([
            'title' => 'Card C',
            'status' => 'todo',
            'order_position' => '196605.0000000000',
        ]);

        $newCard = Task::factory()->create([
            'title' => 'New Card',
            'status' => 'todo',
            'order_position' => DecimalPosition::forEmptyColumn(),
        ]);

        // CORRECT PARAMETER ORDER:
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

        // Verify correct placement using decimal comparison
        $cardAPos = DecimalPosition::normalize($cardA->order_position);
        $newCardPos = DecimalPosition::normalize($newCard->order_position);
        $cardBPos = DecimalPosition::normalize($cardB->order_position);

        $isCorrect = DecimalPosition::lessThan($cardAPos, $newCardPos)
                  && DecimalPosition::lessThan($newCardPos, $cardBPos);

        expect($isCorrect)->toBeTrue(
            'Card should be between A and B'
        );
    });

    it('proves midpoint calculation never fails (unlike old Rank service)', function () {
        // IMPROVEMENT TEST: With decimal positions, midpoint always works
        // No more PrevGreaterThanOrEquals exception!

        $cardA = Task::factory()->create([
            'status' => 'todo',
            'order_position' => '65535.0000000000',
        ]);

        $cardB = Task::factory()->create([
            'status' => 'todo',
            'order_position' => '131070.0000000000',
        ]);

        // Decimal midpoint calculation always succeeds
        $midpoint = DecimalPosition::between(
            DecimalPosition::normalize($cardA->order_position),
            DecimalPosition::normalize($cardB->order_position)
        );

        // The midpoint should be between the two positions
        expect(DecimalPosition::lessThan(DecimalPosition::normalize($cardA->order_position), $midpoint))->toBeTrue();
        expect(DecimalPosition::lessThan($midpoint, DecimalPosition::normalize($cardB->order_position)))->toBeTrue();
    });

    it('validates parameter semantic meanings under stress', function () {
        // Create 10 cards in sequence
        $cards = collect();
        $position = DecimalPosition::forEmptyColumn();
        for ($i = 0; $i < 10; $i++) {
            $cards->push(Task::factory()->create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => $position,
            ]));
            $position = DecimalPosition::after($position);
        }

        // Test EVERY adjacent pair with correct parameter semantics
        for ($i = 0; $i < $cards->count() - 1; $i++) {
            $newCard = Task::factory()->create([
                'title' => "New Card {$i}",
                'status' => 'todo',
                'order_position' => DecimalPosition::forEmptyColumn(),
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
            $afterCardPos = DecimalPosition::normalize($afterCard->order_position);
            $newCardPos = DecimalPosition::normalize($newCard->order_position);
            $beforeCardPos = DecimalPosition::normalize($beforeCard->order_position);

            expect(DecimalPosition::lessThan($afterCardPos, $newCardPos))->toBeTrue(
                "After inserting between {$afterCard->title} and {$beforeCard->title}, " .
                'new card position should be > afterCard'
            );

            expect(DecimalPosition::lessThan($newCardPos, $beforeCardPos))->toBeTrue(
                "After inserting between {$afterCard->title} and {$beforeCard->title}, " .
                'new card position should be < beforeCard'
            );
        }
    });

    it('verifies correct behavior for all edge cases', function () {
        $position = DecimalPosition::forEmptyColumn();
        $cards = collect(['a', 'b', 'c'])->map(
            function ($label) use (&$position) {
                $card = Task::factory()->create([
                    'status' => 'todo',
                    'order_position' => $position,
                ]);
                $position = DecimalPosition::after($position);

                return $card;
            }
        );

        // Edge Case 1: Move to TOP (afterCardId=null, beforeCardId=firstCard)
        $newCard1 = Task::factory()->create(['status' => 'todo', 'order_position' => DecimalPosition::forEmptyColumn()]);
        $this->board->call(
            'moveCard',
            (string) $newCard1->id,
            'todo',
            null,                           // afterCardId=null (no card before)
            (string) $cards->get(0)->id     // beforeCardId=first card
        );
        $newCard1->refresh();
        expect(DecimalPosition::lessThan(
            DecimalPosition::normalize($newCard1->order_position),
            DecimalPosition::normalize($cards->get(0)->order_position)
        ))->toBeTrue('Card moved to top should have position < first card');

        // Edge Case 2: Move to BOTTOM (afterCardId=lastCard, beforeCardId=null)
        $newCard2 = Task::factory()->create(['status' => 'todo', 'order_position' => DecimalPosition::forEmptyColumn()]);
        $this->board->call(
            'moveCard',
            (string) $newCard2->id,
            'todo',
            (string) $cards->last()->id,    // afterCardId=last card
            null                            // beforeCardId=null (no card after)
        );
        $newCard2->refresh();
        expect(DecimalPosition::greaterThan(
            DecimalPosition::normalize($newCard2->order_position),
            DecimalPosition::normalize($cards->last()->order_position)
        ))->toBeTrue('Card moved to bottom should have position > last card');

        // Edge Case 3: Move BETWEEN (both non-null)
        $newCard3 = Task::factory()->create(['status' => 'todo', 'order_position' => DecimalPosition::forEmptyColumn()]);
        $this->board->call(
            'moveCard',
            (string) $newCard3->id,
            'todo',
            (string) $cards->get(0)->id,    // afterCardId=first card
            (string) $cards->get(1)->id     // beforeCardId=second card
        );
        $newCard3->refresh();
        expect(DecimalPosition::greaterThan(
            DecimalPosition::normalize($newCard3->order_position),
            DecimalPosition::normalize($cards->get(0)->order_position)
        ))->toBeTrue('Card moved between should have position > first card');
        expect(DecimalPosition::lessThan(
            DecimalPosition::normalize($newCard3->order_position),
            DecimalPosition::normalize($cards->get(1)->order_position)
        ))->toBeTrue('Card moved between should have position < second card');
    });

    it('stresses parameter order with rapid alternating insertions', function () {
        $cards = collect();
        $position = DecimalPosition::forEmptyColumn();
        for ($i = 0; $i < 5; $i++) {
            $cards->push(Task::factory()->create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => $position,
            ]));
            $position = DecimalPosition::after($position);
        }

        // Rapidly insert 20 cards, alternating between positions
        for ($round = 0; $round < 20; $round++) {
            $newCard = Task::factory()->create([
                'title' => "Rapid Card {$round}",
                'status' => 'todo',
                'order_position' => DecimalPosition::forEmptyColumn(),
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
            $afterCardPos = DecimalPosition::normalize($afterCard->fresh()->order_position);
            $newCardPos = DecimalPosition::normalize($newCard->order_position);
            $beforeCardPos = DecimalPosition::normalize($beforeCard->fresh()->order_position);

            expect(DecimalPosition::lessThan($afterCardPos, $newCardPos))->toBeTrue(
                "Round {$round}: Card should be after {$afterCard->title}"
            );
            expect(DecimalPosition::lessThan($newCardPos, $beforeCardPos))->toBeTrue(
                "Round {$round}: Card should be before {$beforeCard->title}"
            );
        }
    });

    // NOTE: More aggressive stress testing of random moves is covered in
    // ConcurrentOperationStressTest.php - this test was too unreliable here
});

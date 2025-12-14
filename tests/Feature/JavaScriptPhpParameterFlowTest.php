<?php

use Livewire\Livewire;
use Relaticle\Flowforge\Services\Rank;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

beforeEach(function () {
    $this->board = Livewire::test(TestBoard::class);
});

describe('JavaScript → PHP Parameter Flow Validation', function () {
    it('validates exact parameter flow matches flowforge.js:24-28', function () {
        // This test mirrors EXACT JavaScript logic from flowforge.js
        // Lines 24-28:
        // const cardIndex = newOrder.indexOf(cardId);
        // const afterCardId = cardIndex > 0 ? newOrder[cardIndex - 1] : null;
        // const beforeCardId = cardIndex < newOrder.length - 1 ? newOrder[cardIndex + 1] : null;
        // this.$wire.moveCard(cardId, targetColumn, afterCardId, beforeCardId)

        $cards = collect(['a', 'b', 'c', 'd', 'e'])->map(
            fn ($pos) => Task::factory()->create([
                'title' => "Card {$pos}",
                'status' => 'todo',
                'order_position' => $pos,
            ])
        );

        $cardToMove = $cards->get(0);  // Moving card 'a'
        $newOrderIndex = 2;            // Wants to be at index 2 (between 'b' and 'c')

        // SIMULATE EXACT JAVASCRIPT CALCULATION:
        // const cardIndex = newOrder.indexOf(cardId);           // = 2
        // const afterCardId = cardIndex > 0 ? newOrder[cardIndex - 1] : null;
        $afterCardId = $newOrderIndex > 0
            ? $cards->get($newOrderIndex - 1)->id  // Card 'b' (index 1)
            : null;

        // const beforeCardId = cardIndex < newOrder.length - 1 ? newOrder[cardIndex + 1] : null;
        $beforeCardId = $newOrderIndex < $cards->count()
            ? $cards->get($newOrderIndex)->id      // Card 'c' (index 2)
            : null;

        // JavaScript sends (line 28):
        // this.$wire.moveCard(cardId, targetColumn, afterCardId, beforeCardId)
        $this->board->call(
            'moveCard',
            (string) $cardToMove->id,
            'todo',
            (string) $afterCardId,   // 3rd param: Card 'b'
            (string) $beforeCardId   // 4th param: Card 'c'
        );

        $cardToMove->refresh();
        $afterCard = $cards->get(1);   // Card 'b'
        $beforeCard = $cards->get(2);  // Card 'c'

        // Verify: 'b' < movedCard < 'c'
        expect(strcmp($afterCard->fresh()->order_position, $cardToMove->order_position))->toBeLessThan(
            0,
            "Moved card should be after '{$afterCard->title}'"
        );
        expect(strcmp($cardToMove->order_position, $beforeCard->fresh()->order_position))->toBeLessThan(
            0,
            "Moved card should be before '{$beforeCard->title}'"
        );
    });

    it('tests JavaScript edge case: moving to TOP (index 0)', function () {
        $cards = collect(['a', 'b', 'c'])->map(
            fn ($pos) => Task::factory()->create([
                'title' => "Card {$pos}",
                'status' => 'todo',
                'order_position' => $pos,
            ])
        );

        $newCard = Task::factory()->create([
            'title' => 'NewTop',
            'status' => 'todo',
            'order_position' => 'm',
        ]);

        // SIMULATE JAVASCRIPT: Moving to index 0 (top)
        $newOrderIndex = 0;

        // const afterCardId = cardIndex > 0 ? newOrder[cardIndex - 1] : null;
        $afterCardId = $newOrderIndex > 0
            ? $cards->get($newOrderIndex - 1)->id
            : null;  // null (no card before)

        // const beforeCardId = cardIndex < newOrder.length - 1 ? newOrder[cardIndex + 1] : null;
        $beforeCardId = $newOrderIndex < $cards->count()
            ? $cards->get($newOrderIndex)->id
            : null;  // Card 'a'

        $this->board->call(
            'moveCard',
            (string) $newCard->id,
            'todo',
            $afterCardId,               // null
            (string) $beforeCardId      // Card 'a'
        );

        $newCard->refresh();

        // Verify: newCard < 'a'
        expect(strcmp($newCard->order_position, $cards->get(0)->fresh()->order_position))->toBeLessThan(
            0,
            'Card moved to top should be before first card'
        );
    });

    it('tests JavaScript edge case: moving to BOTTOM (last index)', function () {
        $cards = collect(['a', 'b', 'c'])->map(
            fn ($pos) => Task::factory()->create([
                'title' => "Card {$pos}",
                'status' => 'todo',
                'order_position' => $pos,
            ])
        );

        $newCard = Task::factory()->create([
            'title' => 'NewBottom',
            'status' => 'todo',
            'order_position' => 'm',
        ]);

        // SIMULATE JAVASCRIPT: Moving to last index (bottom)
        $newOrderIndex = $cards->count();  // = 3 (after last card)

        // const afterCardId = cardIndex > 0 ? newOrder[cardIndex - 1] : null;
        $afterCardId = $newOrderIndex > 0
            ? $cards->get($newOrderIndex - 1)->id  // Card 'c'
            : null;

        // const beforeCardId = cardIndex < newOrder.length - 1 ? newOrder[cardIndex + 1] : null;
        $beforeCardId = $newOrderIndex < $cards->count()
            ? $cards->get($newOrderIndex)->id
            : null;  // null (no card after)

        $this->board->call(
            'moveCard',
            (string) $newCard->id,
            'todo',
            (string) $afterCardId,      // Card 'c'
            $beforeCardId               // null
        );

        $newCard->refresh();

        // Verify: 'c' < newCard
        expect(strcmp($cards->last()->fresh()->order_position, $newCard->order_position))->toBeLessThan(
            0,
            'Card moved to bottom should be after last card'
        );
    });

    it('tests JavaScript edge case: moving BETWEEN cards (middle index)', function () {
        $cards = collect(['a', 'b', 'c', 'd'])->map(
            fn ($pos) => Task::factory()->create([
                'title' => "Card {$pos}",
                'status' => 'todo',
                'order_position' => $pos,
            ])
        );

        $newCard = Task::factory()->create([
            'title' => 'NewMiddle',
            'status' => 'todo',
            'order_position' => 'm',
        ]);

        // SIMULATE JAVASCRIPT: Moving to index 2 (between 'b' and 'c')
        $newOrderIndex = 2;

        // const afterCardId = cardIndex > 0 ? newOrder[cardIndex - 1] : null;
        $afterCardId = $newOrderIndex > 0
            ? $cards->get($newOrderIndex - 1)->id  // Card 'b' (index 1)
            : null;

        // const beforeCardId = cardIndex < newOrder.length - 1 ? newOrder[cardIndex + 1] : null;
        $beforeCardId = $newOrderIndex < $cards->count()
            ? $cards->get($newOrderIndex)->id      // Card 'c' (index 2)
            : null;

        $this->board->call(
            'moveCard',
            (string) $newCard->id,
            'todo',
            (string) $afterCardId,      // Card 'b'
            (string) $beforeCardId      // Card 'c'
        );

        $newCard->refresh();
        $afterCard = $cards->get(1);   // Card 'b'
        $beforeCard = $cards->get(2);  // Card 'c'

        // Verify: 'b' < newCard < 'c'
        expect(strcmp($afterCard->fresh()->order_position, $newCard->order_position))->toBeLessThan(
            0,
            'Card should be after Card b'
        );
        expect(strcmp($newCard->order_position, $beforeCard->fresh()->order_position))->toBeLessThan(
            0,
            'Card should be before Card c'
        );
    });

    it('simulates browser drag-drop with exact index calculations', function () {
        // Create ordered list like browser shows
        $cards = collect();
        for ($i = 1; $i <= 5; $i++) {
            $lastRank = $i === 1
                ? Rank::forEmptySequence()
                : Rank::after(Rank::fromString($cards->last()->order_position));

            $cards->push(Task::factory()->create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => $lastRank->get(),
            ]));
        }

        // Simulate dragging Card 1 to position between Card 3 and Card 4
        // Visual: C1, C2, C3, [NEW POSITION], C4, C5
        // In array: index 0, 1, 2, [3], 4, 5
        $cardToMove = $cards->get(0);
        $targetIndex = 3;

        // EXACT JavaScript logic from flowforge.js:24-26
        $afterCardId = $targetIndex > 0
            ? $cards->get($targetIndex - 1)->id
            : null;  // Card 3 (index 2)

        $beforeCardId = $targetIndex < $cards->count()
            ? $cards->get($targetIndex)->id
            : null;  // Card 4 (index 3)

        // JavaScript NOW sends (line 28):
        // moveCard(cardId, column, afterCardId, beforeCardId)
        $this->board->call(
            'moveCard',
            (string) $cardToMove->id,
            'todo',
            (string) $afterCardId,   // Card 3
            (string) $beforeCardId   // Card 4
        );

        $cardToMove->refresh();
        $card3 = $cards->get(2)->fresh();
        $card4 = $cards->get(3)->fresh();

        // Verify: Card3 < MovedCard < Card4
        expect(strcmp($card3->order_position, $cardToMove->order_position))->toBeLessThan(
            0,
            'Moved card should be after Card 3'
        );
        expect(strcmp($cardToMove->order_position, $card4->order_position))->toBeLessThan(
            0,
            'Moved card should be before Card 4'
        );
    });

    it('tests all possible index positions in a 10-card column', function () {
        // Create 10 cards
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

        // Test moving a new card to EVERY possible index (0 through 10)
        for ($targetIndex = 0; $targetIndex <= 10; $targetIndex++) {
            $newCard = Task::factory()->create([
                'title' => "New at index {$targetIndex}",
                'status' => 'todo',
                'order_position' => 'm',
            ]);

            // SIMULATE JAVASCRIPT INDEX CALCULATION
            $afterCardId = $targetIndex > 0
                ? $cards->get($targetIndex - 1)->id
                : null;

            $beforeCardId = $targetIndex < $cards->count()
                ? $cards->get($targetIndex)->id
                : null;

            $this->board->call(
                'moveCard',
                (string) $newCard->id,
                'todo',
                $afterCardId,
                $beforeCardId
            );

            $newCard->refresh();

            // Verify correct placement based on index
            if ($targetIndex > 0) {
                $afterCard = $cards->get($targetIndex - 1)->fresh();
                expect(strcmp($afterCard->order_position, $newCard->order_position))->toBeLessThan(
                    0,
                    "At index {$targetIndex}, card should be after card at index " . ($targetIndex - 1)
                );
            }

            if ($targetIndex < $cards->count()) {
                $beforeCard = $cards->get($targetIndex)->fresh();
                expect(strcmp($newCard->order_position, $beforeCard->order_position))->toBeLessThan(
                    0,
                    "At index {$targetIndex}, card should be before card at index {$targetIndex}"
                );
            }

            // Clean up for next iteration
            $newCard->delete();
        }
    });

    it('validates cross-column moves with JavaScript parameter logic', function () {
        // Create cards in different columns
        $todoCards = collect(['a', 'b', 'c'])->map(
            fn ($pos) => Task::factory()->create([
                'title' => "Todo {$pos}",
                'status' => 'todo',
                'order_position' => $pos,
            ])
        );

        $inProgressCards = collect(['d', 'e', 'f'])->map(
            fn ($pos) => Task::factory()->create([
                'title' => "InProgress {$pos}",
                'status' => 'in_progress',
                'order_position' => $pos,
            ])
        );

        // Move a todo card to in_progress column at index 1
        $cardToMove = $todoCards->first();
        $targetIndex = 1;

        // SIMULATE JAVASCRIPT for in_progress column
        $afterCardId = $targetIndex > 0
            ? $inProgressCards->get($targetIndex - 1)->id  // Card 'd'
            : null;

        $beforeCardId = $targetIndex < $inProgressCards->count()
            ? $inProgressCards->get($targetIndex)->id      // Card 'e'
            : null;

        $this->board->call(
            'moveCard',
            (string) $cardToMove->id,
            'in_progress',              // Moving to different column
            (string) $afterCardId,      // Card 'd'
            (string) $beforeCardId      // Card 'e'
        );

        $cardToMove->refresh();

        // Verify moved to correct column
        $status = $cardToMove->status instanceof \BackedEnum ? $cardToMove->status->value : $cardToMove->status;
        expect($status)->toBe('in_progress');

        // Verify positioned correctly: 'd' < movedCard < 'e'
        $afterCard = $inProgressCards->get(0)->fresh();
        $beforeCard = $inProgressCards->get(1)->fresh();

        expect(strcmp($afterCard->order_position, $cardToMove->order_position))->toBeLessThan(
            0,
            'Moved card should be after Card d in new column'
        );
        expect(strcmp($cardToMove->order_position, $beforeCard->order_position))->toBeLessThan(
            0,
            'Moved card should be before Card e in new column'
        );
    });
});

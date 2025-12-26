<?php

use Livewire\Livewire;
use Relaticle\Flowforge\Services\DecimalPosition;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

beforeEach(function () {
    $this->board = Livewire::test(TestBoard::class);
});

// Note: detectInversions() helper function is defined in ParameterOrderMutationTest.php

describe('Decimal Position System - No Inversions', function () {
    it('handles rapid sequential moves between same positions without inversions', function () {
        // Create 5 cards in todo column
        $cards = collect();
        $position = DecimalPosition::forEmptyColumn();
        for ($i = 1; $i <= 5; $i++) {
            $cards->push(Task::factory()->create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => $position,
            ]));
            $position = DecimalPosition::after($position);
        }

        // Simulate rapid back-and-forth movements (simulates user indecision)
        $targetCard = $cards->get(2);

        for ($j = 0; $j < 10; $j++) {
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
        }

        // With DecimalPosition, no inversions should occur
        $inversions = detectInversions(Task::class, 'todo');
        expect($inversions)->toBeEmpty('No inversions should occur with decimal positioning');
    });

    it('handles inserting many cards between two existing cards without issues', function () {
        // Create initial boundary cards
        $firstCard = Task::factory()->create([
            'title' => 'First Card',
            'status' => 'todo',
            'order_position' => DecimalPosition::forEmptyColumn(),
        ]);

        $secondPos = DecimalPosition::after($firstCard->order_position);
        $lastCard = Task::factory()->create([
            'title' => 'Last Card',
            'status' => 'todo',
            'order_position' => $secondPos,
        ]);

        // Insert 50 cards between these two - decimal midpoint never fails
        for ($i = 1; $i <= 50; $i++) {
            $newCard = Task::factory()->create([
                'title' => "Inserted Card {$i}",
                'status' => 'todo',
                'order_position' => DecimalPosition::forEmptyColumn(),
            ]);

            $this->board->call(
                'moveCard',
                (string) $newCard->id,
                'todo',
                (string) $firstCard->id,
                (string) $lastCard->id
            );

            $newCard->refresh();

            // Check every 10 insertions
            if ($i % 10 === 0) {
                $inversions = detectInversions(Task::class, 'todo');
                expect($inversions)->toBeEmpty("No inversions after {$i} insertions");
            }
        }

        // Final check
        $inversions = detectInversions(Task::class, 'todo');
        expect($inversions)->toBeEmpty('No inversions after 50 insertions');
    });

    it('handles concurrent-like operations without inversions', function () {
        // Create 10 cards
        $cards = collect();
        $position = DecimalPosition::forEmptyColumn();

        for ($i = 1; $i <= 10; $i++) {
            $cards->push(Task::factory()->create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => $position,
            ]));
            $position = DecimalPosition::after($position);
        }

        // Simulate concurrent operations: multiple cards moving at "same time"
        $operations = [
            ['card' => $cards->get(2), 'after' => $cards->get(5), 'before' => $cards->get(6)],
            ['card' => $cards->get(7), 'after' => $cards->get(1), 'before' => $cards->get(2)],
            ['card' => $cards->get(4), 'after' => $cards->get(8), 'before' => $cards->get(9)],
        ];

        foreach ($operations as $op) {
            $this->board->call(
                'moveCard',
                (string) $op['card']->id,
                'todo',
                (string) $op['after']->id,
                (string) $op['before']->id
            );
        }

        // No inversions should occur with decimal positioning
        $inversions = detectInversions(Task::class, 'todo');
        expect($inversions)->toBeEmpty('No inversions from concurrent-like operations');
    });

    it('handles 100 random moves without inversions', function () {
        // Create 20 cards
        $cards = collect();
        $position = DecimalPosition::forEmptyColumn();

        for ($i = 1; $i <= 20; $i++) {
            $cards->push(Task::factory()->create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => $position,
            ]));
            $position = DecimalPosition::after($position);
        }

        // Perform 100 random moves
        for ($move = 1; $move <= 100; $move++) {
            $cardToMove = $cards->random();
            $otherCards = $cards->where('id', '!=', $cardToMove->id);

            if ($otherCards->count() < 2) {
                continue;
            }

            $afterCard = $otherCards->random();
            $beforeCard = $otherCards->where('id', '!=', $afterCard->id)->random();

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
                expect($inversions)->toBeEmpty("No inversions after {$move} moves");
            }
        }

        // Final check
        $inversions = detectInversions(Task::class, 'todo');
        expect($inversions)->toBeEmpty('No inversions after 100 random moves');
    });

    it('correctly positions cards using decimal midpoint', function () {
        // Create two boundary cards with a gap
        $card1 = Task::factory()->create([
            'title' => 'Card with position 65535',
            'status' => 'review',
            'order_position' => '65535.0000000000',
        ]);

        $card2 = Task::factory()->create([
            'title' => 'Card with position 131070',
            'status' => 'review',
            'order_position' => '131070.0000000000',
        ]);

        // Insert a card between them
        $newCard = Task::factory()->create([
            'title' => 'New card to insert',
            'status' => 'review',
            'order_position' => DecimalPosition::forEmptyColumn(),
        ]);

        $this->board->call(
            'moveCard',
            (string) $newCard->id,
            'review',
            (string) $card1->id,
            (string) $card2->id
        );

        $newCard->refresh();

        // Verify the new card is positioned at the midpoint
        $card1Pos = DecimalPosition::normalize($card1->order_position);
        $card2Pos = DecimalPosition::normalize($card2->order_position);
        $newPos = DecimalPosition::normalize($newCard->order_position);

        expect(DecimalPosition::lessThan($card1Pos, $newPos))->toBeTrue('New card should be after card1');
        expect(DecimalPosition::lessThan($newPos, $card2Pos))->toBeTrue('New card should be before card2');
    });

    it('maintains precision after many bisections', function () {
        // Create two boundary cards
        $firstCard = Task::factory()->create([
            'title' => 'First Card',
            'status' => 'todo',
            'order_position' => '65535.0000000000',
        ]);

        $lastCard = Task::factory()->create([
            'title' => 'Last Card',
            'status' => 'todo',
            'order_position' => '131070.0000000000',
        ]);

        // Insert 30 cards between them (forcing 30 bisections)
        for ($i = 1; $i <= 30; $i++) {
            $newCard = Task::factory()->create([
                'title' => "Insert {$i}",
                'status' => 'todo',
                'order_position' => DecimalPosition::forEmptyColumn(),
            ]);

            $this->board->call(
                'moveCard',
                (string) $newCard->id,
                'todo',
                (string) $firstCard->id,
                (string) $lastCard->id
            );

            $newCard->refresh();

            // The new card becomes the new boundary
            $lastCard = $newCard;
        }

        // All cards should still be in correct order
        $inversions = detectInversions(Task::class, 'todo');
        expect($inversions)->toBeEmpty('No inversions after 30 bisections');

        // Verify we can still distinguish positions
        $tasks = Task::where('status', 'todo')
            ->orderBy('order_position')
            ->orderBy('id')
            ->get();

        for ($i = 1; $i < $tasks->count(); $i++) {
            $prev = DecimalPosition::normalize($tasks[$i - 1]->order_position);
            $curr = DecimalPosition::normalize($tasks[$i]->order_position);
            expect(DecimalPosition::lessThan($prev, $curr))->toBeTrue("Position {$i} should be less than position " . ($i + 1));
        }
    });
});

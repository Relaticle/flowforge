<?php

use Livewire\Livewire;
use Relaticle\Flowforge\Services\DecimalPosition;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

beforeEach(function () {
    $this->board = Livewire::test(TestBoard::class);
});

describe('Parameter Combination Testing - Finding the RIGHT way', function () {
    it('tests ALL 4 possible parameter combinations for inserting between cards', function () {
        // Create 3 cards in sequence
        $position = DecimalPosition::forEmptyColumn();
        $cardA = Task::factory()->create([
            'title' => 'Card A',
            'status' => 'todo',
            'order_position' => $position,
        ]);
        $position = DecimalPosition::after($position);

        $cardB = Task::factory()->create([
            'title' => 'Card B',
            'status' => 'todo',
            'order_position' => $position,
        ]);
        $position = DecimalPosition::after($position);

        $cardC = Task::factory()->create([
            'title' => 'Card C',
            'status' => 'todo',
            'order_position' => $position,
        ]);
        $position = DecimalPosition::after($position); // Continue after cardC for new cards

        // We want to insert NEW card between A and B
        // Expected result: A < NEW < B

        $results = [];

        // Combination 1: (newCard, column, afterCardId=A, beforeCardId=B)
        try {
            $new1 = Task::factory()->create(['title' => 'New1', 'status' => 'todo', 'order_position' => $position]);
            $position = DecimalPosition::after($position);
            $this->board->call('moveCard', (string) $new1->id, 'todo', (string) $cardA->id, (string) $cardB->id);
            $new1->refresh();
            $results['Combo1_after=A_before=B'] = [
                'success' => true,
                'position' => $new1->order_position,
                'correct_order' => DecimalPosition::lessThan(
                    DecimalPosition::normalize($cardA->order_position),
                    DecimalPosition::normalize($new1->order_position)
                ) && DecimalPosition::lessThan(
                    DecimalPosition::normalize($new1->order_position),
                    DecimalPosition::normalize($cardB->order_position)
                ),
            ];
        } catch (\Exception $e) {
            $results['Combo1_after=A_before=B'] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Combination 2: (newCard, column, afterCardId=B, beforeCardId=A) - REVERSED
        try {
            $new2 = Task::factory()->create(['title' => 'New2', 'status' => 'todo', 'order_position' => $position]);
            $position = DecimalPosition::after($position);
            $this->board->call('moveCard', (string) $new2->id, 'todo', (string) $cardB->id, (string) $cardA->id);
            $new2->refresh();
            $results['Combo2_after=B_before=A'] = [
                'success' => true,
                'position' => $new2->order_position,
                'correct_order' => DecimalPosition::lessThan(
                    DecimalPosition::normalize($cardA->order_position),
                    DecimalPosition::normalize($new2->order_position)
                ) && DecimalPosition::lessThan(
                    DecimalPosition::normalize($new2->order_position),
                    DecimalPosition::normalize($cardB->order_position)
                ),
            ];
        } catch (\Exception $e) {
            $results['Combo2_after=B_before=A'] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Combination 3: (newCard, column, beforeCardId=B, afterCardId=A) - Swapped param order
        try {
            $new3 = Task::factory()->create(['title' => 'New3', 'status' => 'todo', 'order_position' => $position]);
            $position = DecimalPosition::after($position);
            // This is how JavaScript calls it!
            $this->board->call('moveCard', (string) $new3->id, 'todo', (string) $cardB->id, (string) $cardA->id);
            $new3->refresh();
            $results['Combo3_JS_order_before=B_after=A'] = [
                'success' => true,
                'position' => $new3->order_position,
                'correct_order' => DecimalPosition::lessThan(
                    DecimalPosition::normalize($cardA->order_position),
                    DecimalPosition::normalize($new3->order_position)
                ) && DecimalPosition::lessThan(
                    DecimalPosition::normalize($new3->order_position),
                    DecimalPosition::normalize($cardB->order_position)
                ),
            ];
        } catch (\Exception $e) {
            $results['Combo3_JS_order_before=B_after=A'] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Combination 4: (newCard, column, beforeCardId=A, afterCardId=B) - Different swap
        try {
            $new4 = Task::factory()->create(['title' => 'New4', 'status' => 'todo', 'order_position' => $position]);
            $position = DecimalPosition::after($position);
            $this->board->call('moveCard', (string) $new4->id, 'todo', (string) $cardA->id, (string) $cardB->id);
            $new4->refresh();
            $results['Combo4_before=A_after=B'] = [
                'success' => true,
                'position' => $new4->order_position,
                'correct_order' => DecimalPosition::lessThan(
                    DecimalPosition::normalize($cardA->order_position),
                    DecimalPosition::normalize($new4->order_position)
                ) && DecimalPosition::lessThan(
                    DecimalPosition::normalize($new4->order_position),
                    DecimalPosition::normalize($cardB->order_position)
                ),
            ];
        } catch (\Exception $e) {
            $results['Combo4_before=A_after=B'] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Find which combination works
        $workingCombos = array_filter($results, fn ($r) => $r['success'] === true && ($r['correct_order'] ?? false));

        expect(count($workingCombos))->toBeGreaterThan(0, 'At least one combination should work correctly');
    });

    it('tests moving to TOP of column - both parameter orders', function () {
        $position = DecimalPosition::forEmptyColumn();
        $cardA = Task::factory()->create(['title' => 'Card A', 'status' => 'todo', 'order_position' => $position]);
        $position = DecimalPosition::after($position);
        $cardB = Task::factory()->create(['title' => 'Card B', 'status' => 'todo', 'order_position' => $position]);
        $position = DecimalPosition::after($position); // Continue for new cards

        // Want: NEW < A < B
        $results = [];

        // Test 1: afterCardId=null, beforeCardId=A
        try {
            $new1 = Task::factory()->create(['title' => 'NewTop1', 'status' => 'todo', 'order_position' => $position]);
            $position = DecimalPosition::after($position);
            $this->board->call('moveCard', (string) $new1->id, 'todo', null, (string) $cardA->id);
            $new1->refresh();
            $results['after=null_before=A'] = [
                'success' => true,
                'position' => $new1->order_position,
                'correct' => DecimalPosition::lessThan(
                    DecimalPosition::normalize($new1->order_position),
                    DecimalPosition::normalize($cardA->order_position)
                ),
            ];
        } catch (\Exception $e) {
            $results['after=null_before=A'] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Test 2: beforeCardId=A, afterCardId=null (JS order)
        try {
            $new2 = Task::factory()->create(['title' => 'NewTop2', 'status' => 'todo', 'order_position' => $position]);
            $position = DecimalPosition::after($position);
            $this->board->call('moveCard', (string) $new2->id, 'todo', (string) $cardA->id, null);
            $new2->refresh();
            $results['before=A_after=null'] = [
                'success' => true,
                'position' => $new2->order_position,
                'correct' => DecimalPosition::lessThan(
                    DecimalPosition::normalize($new2->order_position),
                    DecimalPosition::normalize($cardA->order_position)
                ),
            ];
        } catch (\Exception $e) {
            $results['before=A_after=null'] = ['success' => false, 'error' => $e->getMessage()];
        }

        $workingCombos = array_filter($results, fn ($r) => $r['success'] === true && ($r['correct'] ?? false));

        expect(count($workingCombos))->toBeGreaterThan(0);
    });

    it('tests moving to BOTTOM of column - both parameter orders', function () {
        $position = DecimalPosition::forEmptyColumn();
        $cardA = Task::factory()->create(['title' => 'Card A', 'status' => 'todo', 'order_position' => $position]);
        $position = DecimalPosition::after($position);
        $cardB = Task::factory()->create(['title' => 'Card B', 'status' => 'todo', 'order_position' => $position]);
        $position = DecimalPosition::after($position); // Continue for new cards

        // Want: A < B < NEW
        $results = [];

        // Test 1: afterCardId=B, beforeCardId=null
        try {
            $new1 = Task::factory()->create(['title' => 'NewBottom1', 'status' => 'todo', 'order_position' => $position]);
            $position = DecimalPosition::after($position);
            $this->board->call('moveCard', (string) $new1->id, 'todo', (string) $cardB->id, null);
            $new1->refresh();
            $results['after=B_before=null'] = [
                'success' => true,
                'position' => $new1->order_position,
                'correct' => DecimalPosition::greaterThan(
                    DecimalPosition::normalize($new1->order_position),
                    DecimalPosition::normalize($cardB->order_position)
                ),
            ];
        } catch (\Exception $e) {
            $results['after=B_before=null'] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Test 2: beforeCardId=null, afterCardId=B (JS order)
        try {
            $new2 = Task::factory()->create(['title' => 'NewBottom2', 'status' => 'todo', 'order_position' => $position]);
            $position = DecimalPosition::after($position);
            $this->board->call('moveCard', (string) $new2->id, 'todo', null, (string) $cardB->id);
            $new2->refresh();
            $results['before=null_after=B'] = [
                'success' => true,
                'position' => $new2->order_position,
                'correct' => DecimalPosition::greaterThan(
                    DecimalPosition::normalize($new2->order_position),
                    DecimalPosition::normalize($cardB->order_position)
                ),
            ];
        } catch (\Exception $e) {
            $results['before=null_after=B'] = ['success' => false, 'error' => $e->getMessage()];
        }

        $workingCombos = array_filter($results, fn ($r) => $r['success'] === true && ($r['correct'] ?? false));

        expect(count($workingCombos))->toBeGreaterThan(0);
    });

    it('simulates exact browser drag-and-drop behavior', function () {
        // Create ordered list like browser shows
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

        // Simulate dragging Card 1 to position between Card 3 and Card 4
        // Visual: C1, C2, C3, [NEW POSITION], C4, C5
        // In array: index 0, 1, 2, [3], 4
        $cardToMove = $cards->get(0);
        $targetIndex = 3;

        // JavaScript logic from flowforge.js:
        $afterCardId = $targetIndex > 0 ? $cards->get($targetIndex - 1)->id : null; // Card 3
        $beforeCardId = $targetIndex < $cards->count() ? $cards->get($targetIndex)->id : null; // Card 4

        // JavaScript NOW sends: moveCard(cardId, column, afterCardId, beforeCardId) - FIXED!
        $this->board->call(
            'moveCard',
            (string) $cardToMove->id,
            'todo',
            (string) $afterCardId,  // 3rd param (after fix)
            (string) $beforeCardId  // 4th param (after fix)
        );

        $cardToMove->refresh();
        $card3 = $cards->get(2)->fresh();
        $card4 = $cards->get(3)->fresh();

        expect(DecimalPosition::lessThan(
            DecimalPosition::normalize($card3->order_position),
            DecimalPosition::normalize($cardToMove->order_position)
        ))->toBeTrue('Moved card should be after Card 3');
        expect(DecimalPosition::lessThan(
            DecimalPosition::normalize($cardToMove->order_position),
            DecimalPosition::normalize($card4->order_position)
        ))->toBeTrue('Moved card should be before Card 4');
    });
});

<?php

use Livewire\Livewire;
use Relaticle\Flowforge\Services\Rank;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

beforeEach(function () {
    $this->board = Livewire::test(TestBoard::class);
});

describe('Parameter Combination Testing - Finding the RIGHT way', function () {
    it('tests ALL 4 possible parameter combinations for inserting between cards', function () {
        // Create 3 cards in sequence
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

        // We want to insert NEW card between A and B
        // Expected result: A < NEW < B (positions: "a" < newPos < "b")

        $results = [];

        // Combination 1: (newCard, column, afterCardId=A, beforeCardId=B)
        try {
            $new1 = Task::factory()->create(['title' => 'New1', 'status' => 'todo', 'order_position' => 'm']);
            $this->board->call('moveCard', (string) $new1->id, 'todo', (string) $cardA->id, (string) $cardB->id);
            $new1->refresh();
            $results['Combo1_after=A_before=B'] = [
                'success' => true,
                'position' => $new1->order_position,
                'correct_order' => strcmp($cardA->order_position, $new1->order_position) < 0 && strcmp($new1->order_position, $cardB->order_position) < 0,
            ];
        } catch (\Exception $e) {
            $results['Combo1_after=A_before=B'] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Combination 2: (newCard, column, afterCardId=B, beforeCardId=A) - REVERSED
        try {
            $new2 = Task::factory()->create(['title' => 'New2', 'status' => 'todo', 'order_position' => 'm']);
            $this->board->call('moveCard', (string) $new2->id, 'todo', (string) $cardB->id, (string) $cardA->id);
            $new2->refresh();
            $results['Combo2_after=B_before=A'] = [
                'success' => true,
                'position' => $new2->order_position,
                'correct_order' => strcmp($cardA->order_position, $new2->order_position) < 0 && strcmp($new2->order_position, $cardB->order_position) < 0,
            ];
        } catch (\Exception $e) {
            $results['Combo2_after=B_before=A'] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Combination 3: (newCard, column, beforeCardId=B, afterCardId=A) - Swapped param order
        try {
            $new3 = Task::factory()->create(['title' => 'New3', 'status' => 'todo', 'order_position' => 'm']);
            // This is how JavaScript calls it!
            $this->board->call('moveCard', (string) $new3->id, 'todo', (string) $cardB->id, (string) $cardA->id);
            $new3->refresh();
            $results['Combo3_JS_order_before=B_after=A'] = [
                'success' => true,
                'position' => $new3->order_position,
                'correct_order' => strcmp($cardA->order_position, $new3->order_position) < 0 && strcmp($new3->order_position, $cardB->order_position) < 0,
            ];
        } catch (\Exception $e) {
            $results['Combo3_JS_order_before=B_after=A'] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Combination 4: (newCard, column, beforeCardId=A, afterCardId=B) - Different swap
        try {
            $new4 = Task::factory()->create(['title' => 'New4', 'status' => 'todo', 'order_position' => 'm']);
            $this->board->call('moveCard', (string) $new4->id, 'todo', (string) $cardA->id, (string) $cardB->id);
            $new4->refresh();
            $results['Combo4_before=A_after=B'] = [
                'success' => true,
                'position' => $new4->order_position,
                'correct_order' => strcmp($cardA->order_position, $new4->order_position) < 0 && strcmp($new4->order_position, $cardB->order_position) < 0,
            ];
        } catch (\Exception $e) {
            $results['Combo4_before=A_after=B'] = ['success' => false, 'error' => $e->getMessage()];
        }

        dump('=== PARAMETER COMBINATION RESULTS ===');
        dump($results);

        // Find which combination works
        $workingCombos = array_filter($results, fn ($r) => $r['success'] === true && ($r['correct_order'] ?? false));
        dump('Working combinations:', array_keys($workingCombos));

        expect(count($workingCombos))->toBeGreaterThan(0, 'At least one combination should work correctly');
    });

    it('tests moving to TOP of column - both parameter orders', function () {
        $cardA = Task::factory()->create(['title' => 'Card A', 'status' => 'todo', 'order_position' => 'a']);
        $cardB = Task::factory()->create(['title' => 'Card B', 'status' => 'todo', 'order_position' => 'b']);

        // Want: NEW < A < B
        $results = [];

        // Test 1: afterCardId=null, beforeCardId=A
        try {
            $new1 = Task::factory()->create(['title' => 'NewTop1', 'status' => 'todo', 'order_position' => 'm']);
            $this->board->call('moveCard', (string) $new1->id, 'todo', null, (string) $cardA->id);
            $new1->refresh();
            $results['after=null_before=A'] = [
                'success' => true,
                'position' => $new1->order_position,
                'correct' => strcmp($new1->order_position, $cardA->order_position) < 0,
            ];
        } catch (\Exception $e) {
            $results['after=null_before=A'] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Test 2: beforeCardId=A, afterCardId=null (JS order)
        try {
            $new2 = Task::factory()->create(['title' => 'NewTop2', 'status' => 'todo', 'order_position' => 'm']);
            $this->board->call('moveCard', (string) $new2->id, 'todo', (string) $cardA->id, null);
            $new2->refresh();
            $results['before=A_after=null'] = [
                'success' => true,
                'position' => $new2->order_position,
                'correct' => strcmp($new2->order_position, $cardA->order_position) < 0,
            ];
        } catch (\Exception $e) {
            $results['before=A_after=null'] = ['success' => false, 'error' => $e->getMessage()];
        }

        dump('=== TOP POSITION RESULTS ===');
        dump($results);

        $workingCombos = array_filter($results, fn ($r) => $r['success'] === true && ($r['correct'] ?? false));
        dump('Working combinations:', array_keys($workingCombos));

        expect(count($workingCombos))->toBeGreaterThan(0);
    });

    it('tests moving to BOTTOM of column - both parameter orders', function () {
        $cardA = Task::factory()->create(['title' => 'Card A', 'status' => 'todo', 'order_position' => 'a']);
        $cardB = Task::factory()->create(['title' => 'Card B', 'status' => 'todo', 'order_position' => 'b']);

        // Want: A < B < NEW
        $results = [];

        // Test 1: afterCardId=B, beforeCardId=null
        try {
            $new1 = Task::factory()->create(['title' => 'NewBottom1', 'status' => 'todo', 'order_position' => 'm']);
            $this->board->call('moveCard', (string) $new1->id, 'todo', (string) $cardB->id, null);
            $new1->refresh();
            $results['after=B_before=null'] = [
                'success' => true,
                'position' => $new1->order_position,
                'correct' => strcmp($new1->order_position, $cardB->order_position) > 0,
            ];
        } catch (\Exception $e) {
            $results['after=B_before=null'] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Test 2: beforeCardId=null, afterCardId=B (JS order)
        try {
            $new2 = Task::factory()->create(['title' => 'NewBottom2', 'status' => 'todo', 'order_position' => 'm']);
            $this->board->call('moveCard', (string) $new2->id, 'todo', null, (string) $cardB->id);
            $new2->refresh();
            $results['before=null_after=B'] = [
                'success' => true,
                'position' => $new2->order_position,
                'correct' => strcmp($new2->order_position, $cardB->order_position) > 0,
            ];
        } catch (\Exception $e) {
            $results['before=null_after=B'] = ['success' => false, 'error' => $e->getMessage()];
        }

        dump('=== BOTTOM POSITION RESULTS ===');
        dump($results);

        $workingCombos = array_filter($results, fn ($r) => $r['success'] === true && ($r['correct'] ?? false));
        dump('Working combinations:', array_keys($workingCombos));

        expect(count($workingCombos))->toBeGreaterThan(0);
    });

    it('simulates exact browser drag-and-drop behavior', function () {
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
        // In array: index 0, 1, 2, [3], 4
        $cardToMove = $cards->get(0);
        $targetIndex = 3;

        // JavaScript logic from flowforge.js:
        $afterCardId = $targetIndex > 0 ? $cards->get($targetIndex - 1)->id : null; // Card 3
        $beforeCardId = $targetIndex < $cards->count() ? $cards->get($targetIndex)->id : null; // Card 4

        dump('Browser would send:', [
            'cardToMove' => $cardToMove->title,
            'afterCard' => $cards->get($targetIndex - 1)->title,
            'beforeCard' => $cards->get($targetIndex)->title,
        ]);

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

        dump('Result:', [
            'card3_pos' => $card3->order_position,
            'moved_card_pos' => $cardToMove->order_position,
            'card4_pos' => $card4->order_position,
            'is_between' => strcmp($card3->order_position, $cardToMove->order_position) < 0 &&
                            strcmp($cardToMove->order_position, $card4->order_position) < 0,
        ]);

        expect(strcmp($card3->order_position, $cardToMove->order_position))->toBeLessThan(0);
        expect(strcmp($cardToMove->order_position, $card4->order_position))->toBeLessThan(0);
    });
});

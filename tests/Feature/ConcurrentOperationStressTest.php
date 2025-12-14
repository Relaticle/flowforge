<?php

use Livewire\Livewire;
use Relaticle\Flowforge\Services\Rank;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

beforeEach(function () {
    $this->board = Livewire::test(TestBoard::class);
});

describe('Concurrent Operation Stress Tests - Simulating Real-World Concurrency', function () {
    it('handles rapid successive moves of same card (20+ times)', function () {
        // STRESS TEST: Move the same card 20+ times rapidly
        // Simulates a user repeatedly changing their mind or network lag causing multiple requests

        // Create cards in each column
        $todoCards = Task::factory()->count(5)->create(['status' => 'todo']);
        $inProgressCards = Task::factory()->count(5)->create(['status' => 'in_progress']);
        $completedCards = Task::factory()->count(5)->create(['status' => 'completed']);

        $targetCard = $todoCards->first();
        $statuses = ['todo', 'in_progress', 'completed'];

        // Perform 20 rapid successive moves
        for ($i = 0; $i < 20; $i++) {
            $randomStatus = $statuses[array_rand($statuses)];

            $this->board->call('moveCard', (string) $targetCard->id, $randomStatus);
            $targetCard->refresh();

            // Verify card has valid position after each move
            expect($targetCard->order_position)->not()->toBeNull()
                ->and($targetCard->order_position)->toBeString()
                ->and(strlen($targetCard->order_position))->toBeGreaterThan(0)
                ->and($targetCard->status)->toBe($randomStatus);
        }

        // Verify positions are properly sorted in each column
        foreach ($statuses as $status) {
            $positions = Task::where('status', $status)
                ->orderBy('order_position')
                ->pluck('order_position')
                ->toArray();

            // Check positions are in ascending order
            for ($i = 0; $i < count($positions) - 1; $i++) {
                expect(strcmp($positions[$i], $positions[$i + 1]))->toBeLessThan(
                    0,
                    "Positions should be sorted in {$status} column after 20 rapid moves"
                );
            }
        }

        // Verify final card state is valid
        $targetCard->refresh();
        expect($targetCard->order_position)->not()->toBeNull();
    });

    it('simulates concurrent-like operations with interleaved moves', function () {
        // CONCURRENCY SIMULATION: Multiple cards moving simultaneously (interleaved)
        // Create 20 cards spread across columns
        $cards = collect();
        foreach (['todo', 'in_progress', 'completed'] as $status) {
            $statusCards = Task::factory()->count(7)->create(['status' => $status]);
            $cards = $cards->merge($statusCards);
        }

        // Simulate 5 "concurrent" operations by interleaving them
        // In real concurrency, these would execute simultaneously
        $operations = [];
        for ($i = 0; $i < 5; $i++) {
            $card = $cards->random();
            $newStatus = collect(['todo', 'in_progress', 'completed'])->random();
            $operations[] = ['card' => $card, 'status' => $newStatus];
        }

        // Execute all operations
        foreach ($operations as $op) {
            $this->board->call('moveCard', (string) $op['card']->id, $op['status']);
        }

        // Verify positions are properly sorted in each column
        foreach (['todo', 'in_progress', 'completed'] as $status) {
            $positions = Task::where('status', $status)
                ->orderBy('order_position')
                ->pluck('order_position')
                ->toArray();

            // Check positions are in ascending order
            for ($i = 0; $i < count($positions) - 1; $i++) {
                expect(strcmp($positions[$i], $positions[$i + 1]))->toBeLessThan(
                    0,
                    "Positions should be sorted in {$status} column after concurrent operations"
                );
            }
        }

        // Verify all positions are unique in each column
        foreach (['todo', 'in_progress', 'completed'] as $status) {
            $positions = Task::where('status', $status)
                ->pluck('order_position')
                ->toArray();

            $uniqueCount = count(array_unique($positions));
            expect($uniqueCount)->toBe(
                count($positions),
                "All positions in {$status} should be unique"
            );
        }
    });

    it('mass reorders entire column (reverse all cards)', function () {
        // STRESS TEST: Reverse order of all cards in a column
        // Simulates bulk reordering operations

        // Create 20 cards in sequential order
        $cards = collect();
        for ($i = 1; $i <= 20; $i++) {
            $rank = $i === 1
                ? Rank::forEmptySequence()
                : Rank::after(Rank::fromString($cards->last()->order_position));

            $card = Task::factory()->create([
                'title' => "Card {$i}",
                'status' => 'todo',
                'order_position' => $rank->get(),
            ]);

            $cards->push($card);
        }

        // Reverse the order by moving each card to the top
        $reversedCards = $cards->reverse();
        foreach ($reversedCards as $card) {
            // Move to top (afterCardId=null, beforeCardId=first card)
            $firstCard = Task::where('status', 'todo')
                ->orderBy('order_position')
                ->first();

            $this->board->call(
                'moveCard',
                (string) $card->id,
                'todo',
                null,                        // afterCardId=null (move to top)
                (string) $firstCard->id      // beforeCardId=first card
            );
        }

        // Verify all positions are valid and unique
        $positions = Task::where('status', 'todo')
            ->pluck('order_position')
            ->toArray();

        expect(count(array_unique($positions)))->toBe(
            20,
            'All 20 positions should be unique after mass reorder'
        );

        // Verify no inversions
        $sortedPositions = Task::where('status', 'todo')
            ->orderBy('order_position')
            ->pluck('order_position')
            ->toArray();

        for ($i = 0; $i < count($sortedPositions) - 1; $i++) {
            expect(strcmp($sortedPositions[$i], $sortedPositions[$i + 1]))->toBeLessThan(
                0,
                "Position {$i} should be < position " . ($i + 1) . ' after mass reorder'
            );
        }
    });

    it('handles simultaneous moves to same column from different columns', function () {
        // CONCURRENCY SCENARIO: Multiple cards moving into same destination column
        // Create cards in different columns
        $todoCards = Task::factory()->count(5)->create(['status' => 'todo']);
        $inProgressCards = Task::factory()->count(5)->create(['status' => 'in_progress']);
        $completedCards = Task::factory()->count(5)->create(['status' => 'completed']);

        // Move 3 cards from different columns all to 'in_progress'
        $this->board->call('moveCard', (string) $todoCards->get(0)->id, 'in_progress');
        $this->board->call('moveCard', (string) $todoCards->get(1)->id, 'in_progress');
        $this->board->call('moveCard', (string) $completedCards->get(0)->id, 'in_progress');

        // Verify positions are properly sorted in target column
        $positions = Task::where('status', 'in_progress')
            ->orderBy('order_position')
            ->pluck('order_position')
            ->toArray();

        for ($i = 0; $i < count($positions) - 1; $i++) {
            expect(strcmp($positions[$i], $positions[$i + 1]))->toBeLessThan(
                0,
                'Positions should be sorted in in_progress column'
            );
        }

        // Verify all positions unique in target column
        $positions = Task::where('status', 'in_progress')
            ->pluck('order_position')
            ->toArray();

        expect(count(array_unique($positions)))->toBe(
            count($positions),
            'All positions in in_progress should be unique'
        );
    });

    it('stress tests alternating column movements (ping-pong pattern)', function () {
        // STRESS TEST: Move cards back and forth between columns rapidly
        // Simulates indecisive users or workflow state changes

        $card1 = Task::factory()->create(['status' => 'todo']);
        $card2 = Task::factory()->create(['status' => 'in_progress']);
        $card3 = Task::factory()->create(['status' => 'completed']);

        // Perform 30 ping-pong movements
        for ($i = 0; $i < 30; $i++) {
            // Card 1: todo <-> in_progress
            $this->board->call(
                'moveCard',
                (string) $card1->id,
                $i % 2 === 0 ? 'in_progress' : 'todo'
            );

            // Card 2: in_progress <-> completed
            $this->board->call(
                'moveCard',
                (string) $card2->id,
                $i % 2 === 0 ? 'completed' : 'in_progress'
            );

            // Card 3: completed <-> todo
            $this->board->call(
                'moveCard',
                (string) $card3->id,
                $i % 2 === 0 ? 'todo' : 'completed'
            );
        }

        // Verify all cards have valid positions
        foreach ([$card1, $card2, $card3] as $card) {
            $card->refresh();
            expect($card->order_position)->not()->toBeNull()
                ->and(strlen($card->order_position))->toBeGreaterThan(0);
        }

        // Verify positions are properly sorted in each column
        foreach (['todo', 'in_progress', 'completed'] as $status) {
            $positions = Task::where('status', $status)
                ->orderBy('order_position')
                ->pluck('order_position')
                ->toArray();

            for ($i = 0; $i < count($positions) - 1; $i++) {
                expect(strcmp($positions[$i], $positions[$i + 1]))->toBeLessThan(
                    0,
                    "Positions should be sorted in {$status} after ping-pong movements"
                );
            }
        }
    });

    it('validates data consistency under high-frequency operations', function () {
        // CONSISTENCY TEST: Verify database state remains consistent under stress
        // Create baseline
        $cards = Task::factory()->count(30)->create();

        $initialCount = Task::count();
        $initialProjectIds = Task::pluck('project_id')->filter()->unique()->count();

        // Perform 50 high-frequency operations
        for ($i = 0; $i < 50; $i++) {
            $card = $cards->random();
            $newStatus = collect(['todo', 'in_progress', 'completed'])->random();

            $this->board->call('moveCard', (string) $card->id, $newStatus);
        }

        // Verify data integrity
        $finalCount = Task::count();
        expect($finalCount)->toBe($initialCount, 'Card count should remain stable');

        // Verify relationships intact
        $finalProjectIds = Task::pluck('project_id')->filter()->unique()->count();
        expect($finalProjectIds)->toBe(
            $initialProjectIds,
            'Project relationships should remain intact'
        );

        // Verify no null positions
        $nullPositions = Task::whereNull('order_position')->count();
        expect($nullPositions)->toBe(0, 'No cards should have null positions');
    });
});

<?php

declare(strict_types=1);

use Relaticle\Flowforge\Services\DecimalPosition;
use Relaticle\Flowforge\Tests\Fixtures\Task;

describe('position calculation for card movements', function () {
    test('first card in empty column gets default position', function () {
        $position = DecimalPosition::calculate(null, null);

        expect($position)->toBe(DecimalPosition::DEFAULT_GAP);
    });

    test('card at top of column gets position before first card', function () {
        // Create existing cards
        Task::create(['title' => 'First', 'status' => 'todo', 'order_position' => '65535.0000000000']);
        Task::create(['title' => 'Second', 'status' => 'todo', 'order_position' => '131070.0000000000']);

        // Calculate position at top (before first card)
        $firstPosition = '65535.0000000000';
        $newPosition = DecimalPosition::calculate(null, $firstPosition);

        expect(bccomp($newPosition, $firstPosition, 10))->toBeLessThan(0);
    });

    test('card at bottom of column gets position after last card', function () {
        // Create existing cards
        Task::create(['title' => 'First', 'status' => 'todo', 'order_position' => '65535.0000000000']);
        Task::create(['title' => 'Last', 'status' => 'todo', 'order_position' => '131070.0000000000']);

        // Calculate position at bottom (after last card)
        $lastPosition = '131070.0000000000';
        $newPosition = DecimalPosition::calculate($lastPosition, null);

        expect(bccomp($newPosition, $lastPosition, 10))->toBeGreaterThan(0);
    });

    test('card between two cards gets position in middle', function () {
        // Create reference cards
        Task::create(['title' => 'First', 'status' => 'todo', 'order_position' => '1000.0000000000']);
        Task::create(['title' => 'Third', 'status' => 'todo', 'order_position' => '2000.0000000000']);

        // Calculate position between
        $newPosition = DecimalPosition::calculate('1000.0000000000', '2000.0000000000');

        expect(bccomp($newPosition, '1000.0000000000', 10))->toBeGreaterThan(0)
            ->and(bccomp($newPosition, '2000.0000000000', 10))->toBeLessThan(0);
    });
});

describe('card movement scenarios', function () {
    beforeEach(function () {
        // Create a column with 5 cards
        Task::create(['title' => 'Card 1', 'status' => 'todo', 'order_position' => '65535.0000000000']);
        Task::create(['title' => 'Card 2', 'status' => 'todo', 'order_position' => '131070.0000000000']);
        Task::create(['title' => 'Card 3', 'status' => 'todo', 'order_position' => '196605.0000000000']);
        Task::create(['title' => 'Card 4', 'status' => 'todo', 'order_position' => '262140.0000000000']);
        Task::create(['title' => 'Card 5', 'status' => 'todo', 'order_position' => '327675.0000000000']);
    });

    test('move card from position 5 to position 2', function () {
        $card5 = Task::where('title', 'Card 5')->first();
        $card1Position = '65535.0000000000';
        $card2Position = '131070.0000000000';

        // Calculate new position between Card 1 and Card 2
        $newPosition = DecimalPosition::between($card1Position, $card2Position);
        $card5->update(['order_position' => $newPosition]);

        // Verify order
        $ordered = Task::where('status', 'todo')
            ->orderBy('order_position')
            ->pluck('title')
            ->toArray();

        expect($ordered)->toBe(['Card 1', 'Card 5', 'Card 2', 'Card 3', 'Card 4']);
    });

    test('move card from position 1 to end', function () {
        $card1 = Task::where('title', 'Card 1')->first();
        $card5Position = '327675.0000000000';

        // Calculate new position after Card 5
        $newPosition = DecimalPosition::after($card5Position);
        $card1->update(['order_position' => $newPosition]);

        // Verify order
        $ordered = Task::where('status', 'todo')
            ->orderBy('order_position')
            ->pluck('title')
            ->toArray();

        expect($ordered)->toBe(['Card 2', 'Card 3', 'Card 4', 'Card 5', 'Card 1']);
    });

    test('move card from middle to top', function () {
        $card3 = Task::where('title', 'Card 3')->first();
        $card1Position = '65535.0000000000';

        // Calculate new position before Card 1
        $newPosition = DecimalPosition::before($card1Position);
        $card3->update(['order_position' => $newPosition]);

        // Verify order
        $ordered = Task::where('status', 'todo')
            ->orderBy('order_position')
            ->pluck('title')
            ->toArray();

        expect($ordered)->toBe(['Card 3', 'Card 1', 'Card 2', 'Card 4', 'Card 5']);
    });

    test('move card to different column', function () {
        $card3 = Task::where('title', 'Card 3')->first();

        // Move to empty "in_progress" column
        $newPosition = DecimalPosition::forEmptyColumn();
        $card3->update([
            'status' => 'in_progress',
            'order_position' => $newPosition,
        ]);

        // Verify card moved
        expect($card3->refresh()->status)->toBe('in_progress')
            ->and(DecimalPosition::normalize($card3->order_position))->toBe(DecimalPosition::normalize(DecimalPosition::DEFAULT_GAP));

        // Verify original column order
        $todoOrdered = Task::where('status', 'todo')
            ->orderBy('order_position')
            ->pluck('title')
            ->toArray();

        expect($todoOrdered)->toBe(['Card 1', 'Card 2', 'Card 4', 'Card 5']);

        // Verify new column
        $inProgressOrdered = Task::where('status', 'in_progress')
            ->orderBy('order_position')
            ->pluck('title')
            ->toArray();

        expect($inProgressOrdered)->toBe(['Card 3']);
    });

    test('multiple moves maintain correct order', function () {
        // Perform series of moves
        $card5 = Task::where('title', 'Card 5')->first();
        $card1 = Task::where('title', 'Card 1')->first();
        $card3 = Task::where('title', 'Card 3')->first();

        // Move Card 5 to position 2
        $newPos = DecimalPosition::between('65535.0000000000', '131070.0000000000');
        $card5->update(['order_position' => $newPos]);

        // Move Card 1 to position 4
        $card4Pos = DecimalPosition::normalize(Task::where('title', 'Card 4')->first()->order_position);
        $originalCard5Pos = '327675.0000000000'; // Card 5's old position is now unused
        $newPos2 = DecimalPosition::between($card4Pos, $originalCard5Pos);
        $card1->update(['order_position' => $newPos2]);

        // Verify final order
        $ordered = Task::where('status', 'todo')
            ->orderBy('order_position')
            ->pluck('title')
            ->toArray();

        // Card 5 moved between 1,2 → becomes position 2
        // Card 1 moved after 4 → becomes position 5
        // Order should be: 5, 2, 3, 4, 1
        expect($ordered)->toBe(['Card 5', 'Card 2', 'Card 3', 'Card 4', 'Card 1']);
    });
});

describe('edge cases', function () {
    test('handles many consecutive insertions at same position', function () {
        // Create two reference cards
        $card1 = Task::create(['title' => 'Anchor 1', 'status' => 'todo', 'order_position' => '1000.0000000000']);
        $card2 = Task::create(['title' => 'Anchor 2', 'status' => 'todo', 'order_position' => '2000.0000000000']);

        // Insert 30 cards between them
        for ($i = 0; $i < 30; $i++) {
            $pos = DecimalPosition::between('1000.0000000000', '2000.0000000000');
            Task::create([
                'title' => "Insert {$i}",
                'status' => 'todo',
                'order_position' => $pos,
            ]);
        }

        // All should be unique and between bounds
        $middleCards = Task::where('status', 'todo')
            ->where('title', 'like', 'Insert%')
            ->pluck('order_position')
            ->map(fn ($p) => DecimalPosition::normalize($p))
            ->toArray();

        expect(array_unique($middleCards))->toHaveCount(30);

        foreach ($middleCards as $pos) {
            expect(bccomp($pos, '1000.0000000000', 10))->toBeGreaterThan(0)
                ->and(bccomp($pos, '2000.0000000000', 10))->toBeLessThan(0);
        }
    });

    test('handles negative positions correctly', function () {
        // Create a card at position 0
        $card1 = Task::create(['title' => 'Zero', 'status' => 'todo', 'order_position' => '0.0000000000']);

        // Insert before it (should get negative position)
        $negativePos = DecimalPosition::before('0.0000000000');
        $card2 = Task::create(['title' => 'Negative', 'status' => 'todo', 'order_position' => $negativePos]);

        // Verify order
        $ordered = Task::where('status', 'todo')
            ->orderBy('order_position')
            ->pluck('title')
            ->toArray();

        expect($ordered)->toBe(['Negative', 'Zero']);
        expect(bccomp(DecimalPosition::normalize($card2->order_position), '0', 10))->toBeLessThan(0);
    });
});

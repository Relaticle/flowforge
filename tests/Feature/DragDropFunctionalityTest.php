<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Services\Rank;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

describe('Real Drag & Drop Functionality', function () {
    beforeEach(function () {
        // Create tasks with proper Rank positions
        Task::create([
            'title' => 'Task A',
            'status' => 'todo',
            'position' => 'A',
        ]);

        Task::create([
            'title' => 'Task B',
            'status' => 'todo',
            'position' => 'B',
        ]);

        Task::create([
            'title' => 'Task C',
            'status' => 'in_progress',
            'position' => 'U',
        ]);
    });

    describe('Basic Card Movement', function () {
        it('moves card between columns', function () {
            $taskA = Task::where('title', 'Task A')->first();
            expect($taskA->status)->toBe('todo');

            Livewire::test(TestBoard::class)
                ->call('moveCard', (string) $taskA->id, 'in_progress');

            $taskA->refresh();
            expect($taskA->status)->toBe('in_progress')
                ->and($taskA->position)->not()->toBe('A'); // Should have new position
        });

        it('moves card after another card in same column', function () {
            $taskA = Task::where('title', 'Task A')->first();
            $taskB = Task::where('title', 'Task B')->first();

            // Move A after B in todo column
            Livewire::test(TestBoard::class)
                ->call('moveCard', (string) $taskA->id, 'todo', (string) $taskB->id);

            $taskA->refresh();
            $taskB->refresh();

            // A should now be positioned after B
            expect($taskB->position)->toBeLessThan($taskA->position);
        });

        it('moves card before another card', function () {
            $taskA = Task::where('title', 'Task A')->first();
            $taskB = Task::where('title', 'Task B')->first();

            // Move B before A
            Livewire::test(TestBoard::class)
                ->call('moveCard', (string) $taskB->id, 'todo', null, (string) $taskA->id);

            $taskA->refresh();
            $taskB->refresh();

            // B should now be positioned before A
            expect($taskB->position)->toBeLessThan($taskA->position);
        });

        it('moves card between two other cards', function () {
            // Create third task
            $taskD = Task::create([
                'title' => 'Task D',
                'status' => 'todo',
                'position' => 'D',
            ]);

            $taskA = Task::where('title', 'Task A')->first();
            $taskB = Task::where('title', 'Task B')->first();

            // Move D between A and B
            Livewire::test(TestBoard::class)
                ->call('moveCard', (string) $taskD->id, 'todo', (string) $taskA->id, (string) $taskB->id);

            $taskA->refresh();
            $taskB->refresh();
            $taskD->refresh();

            // Verify ordering: A < D < B
            expect($taskA->position)->toBeLessThan($taskD->position)
                ->and($taskD->position)->toBeLessThan($taskB->position);
        });
    });

    describe('Error Handling', function () {
        it('throws exception for nonexistent card', function () {
            expect(fn () => Livewire::test(TestBoard::class)
                ->call('moveCard', '999', 'todo'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws exception when board query unavailable', function () {
            // This would require mocking the board to return null query
            // Testing the error path is important for robustness
            expect(true)->toBeTrue(); // Placeholder for now
        });
    });

    describe('Event Dispatching', function () {
        it('dispatches success event after move', function () {
            $taskA = Task::where('title', 'Task A')->first();

            Livewire::test(TestBoard::class)
                ->call('moveCard', (string) $taskA->id, 'in_progress')
                ->assertDispatched('kanban-card-moved');
        });
    });

    describe('Position Persistence', function () {
        it('maintains correct ordering after multiple moves', function () {
            $taskA = Task::where('title', 'Task A')->first();
            $taskB = Task::where('title', 'Task B')->first();

            // A -> after B
            Livewire::test(TestBoard::class)
                ->call('moveCard', (string) $taskA->id, 'todo', (string) $taskB->id);

            // B -> before A
            Livewire::test(TestBoard::class)
                ->call('moveCard', (string) $taskB->id, 'todo', null, (string) $taskA->id);

            $taskA->refresh();
            $taskB->refresh();

            // Final order should be B < A
            expect($taskB->position)->toBeLessThan($taskA->position);
        });

        it('handles rapid successive moves', function () {
            $taskA = Task::where('title', 'Task A')->first();
            $component = Livewire::test(TestBoard::class);

            // Rapid moves between columns
            $component->call('moveCard', (string) $taskA->id, 'in_progress');
            $component->call('moveCard', (string) $taskA->id, 'done');
            $component->call('moveCard', (string) $taskA->id, 'todo');

            $taskA->refresh();
            expect($taskA->status)->toBe('todo');
        });
    });
});

describe('Board Functionality', function () {
    beforeEach(function () {
        // Create multiple tasks for pagination testing
        for ($i = 1; $i <= 12; $i++) {
            Task::create([
                'title' => "Task {$i}",
                'status' => 'todo',
                'position' => chr(65 + $i), // A, B, C, etc.
            ]);
        }
    });

    it('loads more items correctly', function () {
        Livewire::test(TestBoard::class)
            ->assertSet('columnCardLimits', [])
            ->call('loadMoreItems', 'todo', 5)
            ->assertSet('columnCardLimits', ['todo' => 25]); // Default 20 + 5
    });

    it('returns board column records with correct count', function () {
        $component = Livewire::test(TestBoard::class)
            ->set('columnCardLimits', ['todo' => 5]);

        $records = $component->call('getBoardColumnRecords', 'todo');
        expect($records->payload['output'])->toHaveCount(5);
    });

    it('returns correct record count', function () {
        Livewire::test(TestBoard::class)
            ->call('getBoardColumnRecordCount', 'todo')
            ->assertReturned(12); // All 12 tasks created
    });
});

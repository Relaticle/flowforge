<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Services\DecimalPosition;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestStandaloneBoard;

/**
 * @see https://github.com/relaticle/flowforge/issues/84
 */
describe('standalone board rendering', function () {
    test('renders board with all columns', function () {
        Livewire::test(TestStandaloneBoard::class)
            ->assertStatus(200)
            ->assertSee('To Do')
            ->assertSee('In Progress')
            ->assertSee('Completed');
    });

    test('displays cards in correct columns', function () {
        Task::factory()->todo()->create(['title' => 'Standalone Todo']);
        Task::factory()->inProgress()->create(['title' => 'Standalone In Progress']);
        Task::factory()->completed()->create(['title' => 'Standalone Completed']);

        Livewire::test(TestStandaloneBoard::class)
            ->assertSee('Standalone Todo')
            ->assertSee('Standalone In Progress')
            ->assertSee('Standalone Completed');
    });
});

describe('standalone card movement', function () {
    test('moves card to different column', function () {
        $task = Task::factory()->todo()->withPosition('65535.0000000000')->create();

        Livewire::test(TestStandaloneBoard::class)
            ->call('moveCard', (string) $task->id, 'in_progress', null, null)
            ->assertDispatched('kanban-card-moved');

        expect($task->fresh()->status)->toBe('in_progress');
    });

    test('moves card between two cards', function () {
        $task1 = Task::factory()->inProgress()->withPosition('65535.0000000000')->create();
        $task2 = Task::factory()->inProgress()->withPosition('131070.0000000000')->create();
        $taskToMove = Task::factory()->todo()->withPosition('65535.0000000000')->create();

        Livewire::test(TestStandaloneBoard::class)
            ->call('moveCard', (string) $taskToMove->id, 'in_progress', (string) $task1->id, (string) $task2->id)
            ->assertDispatched('kanban-card-moved');

        $movedTask = $taskToMove->fresh();
        expect($movedTask->status)->toBe('in_progress')
            ->and((float) $movedTask->order_position)->toBeGreaterThan(65535)
            ->and((float) $movedTask->order_position)->toBeLessThan(131070);
    });

    test('moves card to empty column', function () {
        $task = Task::factory()->todo()->withPosition('65535.0000000000')->create();

        Livewire::test(TestStandaloneBoard::class)
            ->call('moveCard', (string) $task->id, 'completed', null, null)
            ->assertDispatched('kanban-card-moved');

        $movedTask = $task->fresh();
        expect($movedTask->status)->toBe('completed')
            ->and((float) $movedTask->order_position)->toBe((float) DecimalPosition::DEFAULT_GAP);
    });
});

describe('standalone pagination', function () {
    test('loads more items on demand', function () {
        Task::factory(30)->todo()->create();

        Livewire::test(TestStandaloneBoard::class)
            ->call('loadMoreItems', 'todo', 20)
            ->assertDispatched('kanban-items-loaded');
    });
});

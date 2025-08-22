<?php

declare(strict_types=1);

use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

beforeEach(function () {
    Task::create(['title' => 'Task 1', 'status' => 'todo', 'order_column' => 1]);
    Task::create(['title' => 'Task 2', 'status' => 'todo', 'order_column' => 2]);
    Task::create(['title' => 'Task 3', 'status' => 'in_progress', 'order_column' => 1]);
});

test('updateRecordsOrderAndColumn moves task between columns', function () {
    $task = Task::where('status', 'todo')->first();
    expect($task->status)->toBe('todo');

    Livewire::test(TestBoard::class)
        ->call('updateRecordsOrderAndColumn', 'in_progress', [$task->getKey()])
        ->assertReturned(true);

    $task->refresh();
    expect($task->status)->toBe('in_progress');
});

test('updateRecordsOrderAndColumn updates order correctly', function () {
    $task1 = Task::where('title', 'Task 1')->first();
    $task2 = Task::where('title', 'Task 2')->first();

    Livewire::test(TestBoard::class)
        ->call('updateRecordsOrderAndColumn', 'todo', [$task2->getKey(), $task1->getKey()]);

    $task1->refresh();
    $task2->refresh();

    expect($task2->order_column)->toBe(0);
    expect($task1->order_column)->toBe(1);
});

test('updateRecordsOrderAndColumn handles nonexistent records gracefully', function () {
    Livewire::test(TestBoard::class)
        ->call('updateRecordsOrderAndColumn', 'todo', [999]);

    expect(true)->toBeTrue();
});

test('loadMoreItems increases column card limit', function () {
    Livewire::test(TestBoard::class)
        ->assertSet('columnCardLimits', [])
        ->call('loadMoreItems', 'todo', 5)
        ->assertSet('columnCardLimits', ['todo' => 15]);
});

test('loadMoreItems dispatches refresh event', function () {
    Livewire::test(TestBoard::class)
        ->call('loadMoreItems', 'todo')
        ->assertDispatched('board-refreshed');
});

test('board respects column card limits for pagination', function () {
    for ($i = 1; $i <= 15; $i++) {
        Task::create(['title' => "Task {$i}", 'status' => 'todo', 'order_column' => $i]);
    }

    $component = Livewire::test(TestBoard::class)
        ->set('columnCardLimits', ['todo' => 5]);

    $board = $component->instance()->getBoard();
    $records = $board->getBoardRecords('todo');

    expect($records)->toHaveCount(5);
});

test('getBoardColumnRecords returns correct format', function () {
    Livewire::test(TestBoard::class)
        ->call('getBoardColumnRecords', 'todo')
        ->assertReturned(fn ($records) => is_array($records) && count($records) > 0);
});

test('getBoardColumnRecordCount returns correct count', function () {
    Livewire::test(TestBoard::class)
        ->call('getBoardColumnRecordCount', 'todo')
        ->assertReturned(2);
});

test('getBoardRecord returns correct model', function () {
    $task = Task::first();
    expect($task)->not->toBeNull();

    $component = Livewire::test(TestBoard::class);

    $component->call('getBoardRecord', $task->getKey());

    $result = $component->instance()->getBoardRecord($task->getKey());
    expect($result)->toBeInstanceOf(Task::class);
    expect($result->getKey())->toBe($task->getKey());
});

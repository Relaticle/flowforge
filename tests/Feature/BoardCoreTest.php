<?php

declare(strict_types=1);

use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Column;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;

beforeEach(function () {
    // Create test data
    Task::create(['title' => 'Task 1', 'status' => 'todo', 'order_column' => 1]);
    Task::create(['title' => 'Task 2', 'status' => 'in_progress', 'order_column' => 1]);
    Task::create(['title' => 'Task 3', 'status' => 'completed', 'order_column' => 1]);
});

test('board can be instantiated and configured', function () {
    $testBoard = new TestBoard;
    $board = $testBoard->getBoard();

    expect($board)->toBeInstanceOf(Board::class);
    expect($board->getRecordTitleAttribute())->toBe('title');
    expect($board->getColumnIdentifierAttribute())->toBe('status');
});

test('board columns are configured correctly', function () {
    $testBoard = new TestBoard;
    $board = $testBoard->getBoard();

    $columns = $board->getColumns();

    expect($columns)->toHaveCount(3);
    expect($columns[0])->toBeInstanceOf(Column::class);
    expect($columns[0]->getName())->toBe('todo');
    expect($columns[0]->getLabel())->toBe('To Do');
    expect($columns[0]->getColor())->toBe('gray');
});

test('board retrieves records by column', function () {
    $testBoard = new TestBoard;
    $board = $testBoard->getBoard();

    $todoRecords = $board->getBoardRecords('todo');
    $inProgressRecords = $board->getBoardRecords('in_progress');
    $completedRecords = $board->getBoardRecords('completed');

    expect($todoRecords)->toHaveCount(1);
    expect($inProgressRecords)->toHaveCount(1);
    expect($completedRecords)->toHaveCount(1);

    expect($todoRecords->first()->title)->toBe('Task 1');
    expect($inProgressRecords->first()->title)->toBe('Task 2');
    expect($completedRecords->first()->title)->toBe('Task 3');
});

test('board counts records correctly', function () {
    $testBoard = new TestBoard;
    $board = $testBoard->getBoard();

    expect($board->getBoardRecordCount('todo'))->toBe(1);
    expect($board->getBoardRecordCount('in_progress'))->toBe(1);
    expect($board->getBoardRecordCount('completed'))->toBe(1);
    expect($board->getBoardRecordCount('nonexistent'))->toBe(0);
});

test('board formats records correctly', function () {
    $testBoard = new TestBoard;
    $board = $testBoard->getBoard();

    $task = Task::where('status', 'todo')->first();
    $formatted = $board->formatBoardRecord($task);

    expect($formatted)->toHaveKeys(['id', 'title', 'column', 'model']);
    expect($formatted['title'])->toBe('Task 1');
    expect($formatted['column'])->toBe('todo');
    expect($formatted['model'])->toBeInstanceOf(Task::class);
});

test('board view data has correct structure', function () {
    $testBoard = new TestBoard;
    $board = $testBoard->getBoard();

    $viewData = $board->getViewData();

    expect($viewData)->toHaveKeys(['columns', 'config']);
    expect($viewData['columns'])->toBeArray();
    expect($viewData['columns'])->toHaveCount(3);

    // Check todo column
    $todoColumn = $viewData['columns']['todo'];
    expect($todoColumn)->toHaveKeys(['id', 'label', 'color', 'items', 'total']);
    expect($todoColumn['id'])->toBe('todo');
    expect($todoColumn['label'])->toBe('To Do');
    expect($todoColumn['color'])->toBe('gray');
    expect($todoColumn['items'])->toHaveCount(1);
    expect($todoColumn['total'])->toBe(1);

    // Check config object
    expect($viewData['config']->getTitleField())->toBe('title');
    expect($viewData['config']->getColumnField())->toBe('status');
});

test('board handles empty columns', function () {
    Task::query()->delete();

    $testBoard = new TestBoard;
    $board = $testBoard->getBoard();

    $viewData = $board->getViewData();

    foreach ($viewData['columns'] as $column) {
        expect($column['items'])->toHaveCount(0);
        expect($column['total'])->toBe(0);
    }
});

test('board reorder configuration works', function () {
    $testBoard = new TestBoard;
    $board = $testBoard->getBoard();

    $reorderBy = $board->getReorderBy();

    expect($reorderBy)->toBeArray();
    expect($reorderBy['column'])->toBe('order_column');
    expect($reorderBy['direction'])->toBe('asc');
});

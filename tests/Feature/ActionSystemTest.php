<?php

declare(strict_types=1);

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoard;
use Relaticle\Flowforge\Tests\Fixtures\TestBoardWithActions;

beforeEach(function () {
    Task::create(['title' => 'Test Task', 'status' => 'todo']);
});

test('board with actions renders successfully', function () {
    Livewire::test(TestBoardWithActions::class)
        ->assertSuccessful();
});

test('board actions are configured correctly via livewire', function () {
    $component = Livewire::test(TestBoardWithActions::class);
    $board = $component->instance()->getBoard();

    $columnActions = $board->getColumnActions();
    $cardActions = $board->getRecordActions();
    $cardAction = $board->getCardAction();

    expect($columnActions)->toHaveCount(1);
    expect($cardActions)->toHaveCount(2);
    expect($cardAction)->toBe('edit');

    expect($columnActions[0])->toBeInstanceOf(CreateAction::class);
    expect($cardActions[0])->toBeInstanceOf(EditAction::class);
    expect($cardActions[1])->toBeInstanceOf(DeleteAction::class);
});

test('column actions work via livewire', function () {
    $component = Livewire::test(TestBoardWithActions::class);

    $component->call('getColumnActionsForColumn', 'todo')
        ->assertReturned(fn ($actions) => is_array($actions) && count($actions) > 0);
});

test('card actions work via livewire', function () {
    $task = Task::first();
    $component = Livewire::test(TestBoardWithActions::class);

    $board = $component->instance()->getBoard();
    $formatted = $board->formatBoardRecord($task);

    $component->call('getCardActionsForRecord', $formatted)
        ->assertReturned(fn ($actions) => is_array($actions) && count($actions) > 0);
});

test('card action can be executed via livewire', function () {
    $task = Task::first();
    $component = Livewire::test(TestBoardWithActions::class);

    $board = $component->instance()->getBoard();
    $formatted = $board->formatBoardRecord($task);

    $component->call('getCardActionForRecord', $formatted)
        ->assertReturned('edit');

    $component->call('hasCardAction', $formatted)
        ->assertReturned(true);
});

test('board without actions works via livewire', function () {
    $component = Livewire::test(TestBoard::class);
    $board = $component->instance()->getBoard();

    expect($board->getActions())->toBe([]);
    expect($board->getColumnActions())->toBe([]);
    expect($board->getRecordActions())->toBe([]);
    expect($board->getCardAction())->toBeNull();
});

test('action delegation works via livewire', function () {
    $component = Livewire::test(TestBoardWithActions::class);
    $board = $component->instance()->getBoard();
    $livewire = $board->getLivewire();

    expect($livewire)->toBe($component->instance());
    expect($board->getLivewire())->toBeInstanceOf(\Relaticle\Flowforge\Contracts\HasBoard::class);
});

<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Relaticle\Flowforge\Tests\Fixtures\Task;
use Relaticle\Flowforge\Tests\Fixtures\TestBoardResourcePage;

test('column action receives column argument in mutateDataUsing', function () {
    Livewire::test(TestBoardResourcePage::class)
        ->callAction(
            TestAction::make('createTask')->arguments(['column' => 'in_progress']),
            ['title' => 'New Task'],
        );

    $task = Task::where('title', 'New Task')->firstOrFail();

    expect($task->status)->toBe('in_progress');
});

<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardResourcePage;
use Relaticle\Flowforge\Column;

/**
 * Test fixture for BoardResourcePage that uses InteractsWithRecord.
 * Replicates the GitHub issue #37 scenario where a project has many tasks.
 */
class TestBoardResourcePage extends BoardResourcePage
{
    use InteractsWithRecord;

    protected static string $resource = TestResource::class;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function board(Board $board): Board
    {
        // Use $this->getRecord() to scope tasks to this project
        return $board
            ->query($this->getRecord()->tasks()->getQuery())
            ->recordTitleAttribute('title')
            ->columnIdentifier('status')
            ->positionIdentifier('order_position')
            ->columns([
                Column::make('todo')->label('To Do')->color('gray'),
                Column::make('in_progress')->label('In Progress')->color('blue'),
                Column::make('completed')->label('Completed')->color('green'),
            ]);
    }
}

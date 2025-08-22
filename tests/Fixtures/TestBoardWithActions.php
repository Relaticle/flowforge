<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;

class TestBoardWithActions extends BoardPage
{
    public function getEloquentQuery(): Builder
    {
        return Task::query();
    }

    public function board(Board $board): Board
    {
        return $board
            ->query($this->getEloquentQuery())
            ->recordTitleAttribute('title')
            ->columnIdentifier('status')
            ->columns([
                Column::make('todo')->label('To Do'),
                Column::make('completed')->label('Completed'),
            ])
            ->columnActions([
                CreateAction::make('create')
                    ->model(Task::class)
                    ->label('Add Task'),
            ])
            ->cardActions([
                EditAction::make('edit')
                    ->model(Task::class),
                DeleteAction::make('delete')
                    ->model(Task::class),
            ])
            ->cardAction('edit');
    }
}

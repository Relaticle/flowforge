<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardResourcePage;
use Relaticle\Flowforge\Column;

class TestBoardResourcePage extends BoardResourcePage
{
    protected static string $resource = TestResource::class;

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
            ->positionIdentifier('order_position')
            ->columns([
                Column::make('todo')->label('To Do'),
                Column::make('in_progress')->label('In Progress'),
                Column::make('completed')->label('Completed'),
            ])
            ->columnActions([
                CreateAction::make('createTask')
                    ->model(Task::class)
                    ->schema([
                        TextInput::make('title')->required(),
                    ])
                    ->mutateDataUsing(function (array $data, array $arguments): array {
                        $data['status'] = $arguments['column'] ?? 'todo';

                        return $data;
                    }),
            ]);
    }
}

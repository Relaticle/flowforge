<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardPage;
use Relaticle\Flowforge\Column;

class TestCardActionBoard extends BoardPage
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
            ])
            ->cardActions([
                Action::make('view')
                    ->url(fn (Task $record): string => "https://example.test/tasks/{$record->getKey()}"),
                Action::make('viewInNewTab')
                    ->url(fn (Task $record): string => "https://example.test/tasks/{$record->getKey()}")
                    ->openUrlInNewTab(),
                Action::make('edit')
                    ->schema([
                        TextInput::make('title'),
                    ])
                    ->action(function (): void {}),
            ])
            ->cardAction($this->cardActionName());
    }

    protected function cardActionName(): string
    {
        return 'view';
    }
}

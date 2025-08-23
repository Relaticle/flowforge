<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Relaticle\Flowforge\Concerns\InteractsWithBoard;
use Relaticle\Flowforge\Contracts\HasBoard;

/**
 * Simplified BoardPage - just like Filament's pages.
 * No adapters, no complex caching - just clean delegation.
 */
abstract class BoardPage extends Page implements HasActions, HasBoard, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithBoard {
        InteractsWithBoard::getDefaultActionRecord insteadof InteractsWithActions;
    }
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'flowforge::filament.pages.board-page';

    /**
     * Configure the table (Filament's native way) - convert board config to table.
     */
    public function table(Table $table): Table
    {
        $board = $this->getBoard();

        return $table
            ->query($board->getQuery())
            ->filters($board->getBoardFilters());
    }

    /**
     * Configure the board - implement in subclasses.
     */
    abstract public function board(Board $board): Board;
}

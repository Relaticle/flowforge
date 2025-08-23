<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;

/**
 * Provides table functionality to any Livewire component that has a Board.
 * This allows pure Livewire components to use Board filters without extending BoardPage.
 */
trait HasTableFromBoard
{
    use InteractsWithTable;

    /**
     * Get table from board configuration.
     */
    public function table(Table $table): Table
    {
        $board = $this->getBoard();

        return $table
            ->query($board->getQuery())
            ->filters($board->getBoardFilters());
    }
}

<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Filament\Tables\Columns\Column;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;

/**
 * Provides table functionality to any Livewire component that has a Board.
 * This allows pure Livewire components to use Board filters without extending BoardPage.
 */
trait InteractsWithBoardTable
{
    use InteractsWithTable;

    /**
     * Get table from board configuration.
     */
    public function table(Table $table): Table
    {
        $board = $this->getBoard();

        $searchableColumns = collect($board->getSearchableFields())
            ->map(fn ($field) => Column::make($field)->searchable())->toArray();

        return $table
            ->queryStringIdentifier('board')
            ->query($board->getQuery())
            ->filters($board->getBoardFilters())
            ->filtersFormWidth($board->getFiltersFormWidth())
            ->filtersFormColumns($board->getFiltersFormColumns())
            ->filtersLayout($board->getFiltersLayout())
            ->columns($searchableColumns);
    }

    /**
     * Override to use board-specific query string identifier.
     */
    protected function getTableQueryStringIdentifier(): ?string
    {
        return 'board';
    }
}

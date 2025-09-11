<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Filament\Tables\Columns\Column;
use Filament\Tables\Table;

/**
 * Provides isolated table functionality for boards.
 * This creates a separate table context that doesn't interfere with page tables.
 */
trait InteractsWithBoardTable
{
    // Board-specific table state to avoid conflicts with page tables
    protected ?Table $boardTable = null;
    
    public array $boardTableFilters = [];
    public ?string $boardTableSearch = '';
    public array $boardTableSortColumn = [];
    public ?string $boardTableSortDirection = null;
    public array $boardTableColumnSearches = [];
    public array $boardTableSelectedRecords = [];
    public bool $boardTableSelectAll = false;

    /**
     * Get table from board configuration with isolated state.
     */
    public function table(Table $table): Table
    {
        $board = $this->getBoard();

        $searchableColumns = collect($board->getSearchableFields())
            ->map(fn ($field) => Column::make($field)->searchable())->toArray();

        return $table
            ->query($board->getQuery())
            ->filters($board->getBoardFilters())
            ->filtersFormWidth($board->getFiltersFormWidth())
            ->filtersFormColumns($board->getFiltersFormColumns())
            ->filtersLayout($board->getFiltersLayout())
            ->columns($searchableColumns)
            ->statePath('boardTable'); // Use isolated state path
    }

    /**
     * Get the board's table instance with isolated state.
     */
    public function getBoardTable(): Table
    {
        return $this->boardTable ??= $this->table(Table::make($this));
    }

    /**
     * Get filtered board query that applies both search and filters.
     */
    public function getFilteredBoardQuery()
    {
        $board = $this->getBoard();
        $query = $board->getQuery();
        
        if (!$query) {
            return null;
        }

        // Apply search if present
        if (!empty($this->boardTableSearch) && $board->isSearchable()) {
            $searchableFields = $board->getSearchableFields();
            if (!empty($searchableFields)) {
                $query->where(function ($q) use ($searchableFields) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'like', '%' . $this->boardTableSearch . '%');
                    }
                });
            }
        }

        // Apply filters if present
        if (!empty($this->boardTableFilters)) {
            $table = $this->getBoardTable();
            foreach ($table->getFilters() as $filter) {
                $filterData = $this->boardTableFilters[$filter->getName()] ?? null;
                if ($filterData !== null && !empty($filterData)) {
                    $filter->apply($query, $filterData);
                }
            }
        }

        return $query;
    }

    /**
     * Get board table filters form.
     */
    public function getBoardTableFiltersForm(): \Filament\Forms\Form
    {
        return $this->getBoardTable()->getFiltersForm();
    }

    /**
     * Reset board table filters.
     */
    public function resetBoardTableFiltersForm(): void
    {
        $this->boardTableFilters = [];
        $this->getBoardTable()->getFiltersForm()->fill();
    }

    /**
     * Remove a specific board filter.
     */
    public function removeBoardTableFilter(string $filterName, ?string $field = null): void
    {
        $filterName = $field ?? $filterName;
        
        unset($this->boardTableFilters[$filterName]);
        
        $this->getBoardTable()->getFiltersForm()->fill($this->boardTableFilters);
    }

    /**
     * Remove all board table filters.
     */
    public function removeBoardTableFilters(): void
    {
        $this->resetBoardTableFiltersForm();
    }

    /**
     * Get board table search value.
     */
    public function getBoardTableSearch(): ?string
    {
        return $this->boardTableSearch;
    }

    /**
     * Update board table search.
     */
    public function updatedBoardTableSearch(): void
    {
        // Trigger board data refresh when search changes
        $this->dispatch('board-search-updated', search: $this->boardTableSearch);
    }
}
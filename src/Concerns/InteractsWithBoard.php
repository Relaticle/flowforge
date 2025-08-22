<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Relaticle\Flowforge\Board;

trait InteractsWithBoard
{
    protected Board $board;

    /**
     * Get the board configuration.
     */
    public function getBoard(): Board
    {
        return $this->board ??= $this->board($this->makeBoard());
    }

    /**
     * Boot the InteractsWithBoard trait.
     */
    public function bootedInteractsWithBoard(): void
    {
        $this->board = $this->board($this->makeBoard());
    }

    protected function makeBoard(): Board
    {
        return Board::make($this)
            ->query(fn (): Builder | Relation | null => $this->getBoardQuery());
    }

    /**
     * Get column actions for a specific column.
     */
    public function getColumnActionsForColumn(string $columnId): array
    {
        return [];
    }

    /**
     * Get card actions for a record.
     */
    public function getCardActionsForRecord(array $record): array
    {
        return [];
    }

    /**
     * Get card action for a record.
     */
    public function getCardActionForRecord(array $record): ?string
    {
        return null;
    }

    /**
     * Check if a card has an action.
     */
    public function hasCardAction(array $record): bool
    {
        return false;
    }

    /**
     * Update records order and column.
     */
    public function updateRecordsOrderAndColumn(string $columnId, array $recordIds): bool
    {
        return true;
    }

    /**
     * Load more items for a column.
     */
    public function loadMoreItems(string $columnId, ?int $count = null): void
    {
        // Default implementation
    }

    /**
     * Get board query.
     */
    public function getBoardQuery(): ?Builder
    {
        return null;
    }
}

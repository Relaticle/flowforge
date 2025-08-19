<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Relaticle\Flowforge\Board;

trait InteractsWithBoard
{
    protected ?Board $board = null;

    /**
     * Get the board configuration.
     */
    public function getBoard(): Board
    {
        return $this->board ??= $this->board(Board::make());
    }

    /**
     * Configure the board.
     */
    abstract public function board(Board $board): Board;

    /**
     * Get the Eloquent query for the board.
     */
    abstract public function getEloquentQuery(): Builder;

    /**
     * Boot the InteractsWithBoard trait.
     */
    public function bootedInteractsWithBoard(): void
    {
        // Recreate board fresh (Filament pattern)
        $this->board = $this->board(Board::make());

        // Set the query on the board if not already set
        if (! $this->board->getQuery()) {
            $this->board->query($this->getEloquentQuery());
        }
    }

    /**
     * Move a record from one column to another.
     */
    public function moveRecord(Model $record, string $toColumn, ?string $fromColumn = null): void
    {
        $board = $this->getBoard();
        $columnIdentifier = $board->getColumnIdentifierAttribute();

        if (! $columnIdentifier) {
            throw new \Exception('Column identifier attribute is required for moving records');
        }

        // Update the record's column identifier
        $record->{$columnIdentifier} = $toColumn;
        $record->save();

        // Refresh the model to ensure relationships are up to date
        $record->refresh();

        // Force refresh the board data
        if (method_exists($this, 'refreshBoard')) {
            $this->refreshBoard();
        } else {
            // Fallback to Livewire refresh
            $this->dispatch('$refresh');
        }
    }
}

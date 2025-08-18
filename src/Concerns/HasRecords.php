<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasRecords
{
    /**
     * Move a record from one column to another.
     * This method can be overridden by InteractsWithBoard.
     */
    public function moveRecord(Model $record, string $toColumn, ?string $fromColumn = null): void
    {
        // Default implementation - should be overridden by InteractsWithBoard
        // This is just a placeholder to prevent trait conflicts
    }

    /**
     * Get records for the board.
     */
    public function getRecords(): array
    {
        return $this->getAdapter()?->getRecords() ?? [];
    }

    /**
     * Refresh records data.
     */
    public function refreshRecords(): void
    {
        // Trigger a component refresh to reload records
        $this->dispatch('$refresh');
    }
}
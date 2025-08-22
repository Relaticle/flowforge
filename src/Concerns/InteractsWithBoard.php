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
     * Cards per column pagination state.
     */
    public array $columnCardLimits = [];

    /**
     * Default cards per column.
     */
    public int $cardsIncrement = 10;

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
        $this->cacheBoardActions();
    }

    /**
     * Cache board actions for Filament's action system.
     */
    protected function cacheBoardActions(): void
    {
        $board = $this->getBoard();
        
        // Cache all actions for Filament's action system
        foreach ([...$board->getActions(), ...$board->getRecordActions(), ...$board->getColumnActions()] as $action) {
            if ($action instanceof \Filament\Actions\ActionGroup) {
                foreach ($action->getFlatActions() as $flatAction) {
                    $this->cacheAction($flatAction);
                }
            } elseif ($action instanceof \Filament\Actions\Action) {
                $this->cacheAction($action);
            }
        }
    }

    protected function makeBoard(): Board
    {
        return Board::make($this)
            ->query(fn (): Builder | Relation | null => $this->getBoardQuery());
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
        $count = $count ?? $this->cardsIncrement;
        $currentLimit = $this->columnCardLimits[$columnId] ?? 10;
        $this->columnCardLimits[$columnId] = $currentLimit + $count;
        
        // Dispatch event to refresh the board
        $this->dispatch('board-refreshed');
    }

    /**
     * Get the default record for an action (Filament's record injection system).
     */
    public function getDefaultActionRecord(\Filament\Actions\Action $action): ?\Illuminate\Database\Eloquent\Model
    {
        // Get the current mounted action context
        $mountedActions = $this->mountedActions ?? [];
        
        if (empty($mountedActions)) {
            return null;
        }
        
        // Get the current mounted action
        $currentMountedAction = end($mountedActions);
        
        // Extract recordKey from context or arguments
        $recordKey = $currentMountedAction['context']['recordKey'] ?? 
                    $currentMountedAction['arguments']['recordKey'] ?? null;
        
        if (!$recordKey) {
            return null;
        }
        
        // Resolve the record using board query
        $board = $this->getBoard();
        $query = $board->getQuery();
        
        if ($query) {
            return (clone $query)->find($recordKey);
        }
        
        return null;
    }

    /**
     * Get board query.
     */
    public function getBoardQuery(): ?Builder
    {
        return $this->getBoard()->getQuery();
    }

    /**
     * Get board records for a column (standardized Filament naming).
     */
    public function getBoardColumnRecords(string $columnId): array
    {
        $board = $this->getBoard();
        $query = $board->getQuery();
        
        if (!$query) {
            return [];
        }
        
        $statusField = $board->getColumnIdentifierAttribute() ?? 'status';
        $limit = $this->columnCardLimits[$columnId] ?? 10;
        
        return (clone $query)
            ->where($statusField, $columnId)
            ->limit($limit)
            ->get()
            ->toArray();
    }


    /**
     * Get board record count for a column (standardized Filament naming).
     */
    public function getBoardColumnRecordCount(string $columnId): int
    {
        $board = $this->getBoard();
        $query = $board->getQuery();
        
        if (!$query) {
            return 0;
        }
        
        $statusField = $board->getColumnIdentifierAttribute() ?? 'status';
        
        return (clone $query)->where($statusField, $columnId)->count();
    }

    /**
     * Get board record actions with proper context.
     */
    public function getBoardRecordActions(array $record): array
    {
        $board = $this->getBoard();
        $actions = [];
        
        // Get the actual model
        $model = $board->getBoardRecord($record['id']);
        if (!$model) {
            return [];
        }
        
        foreach ($board->getRecordActions() as $action) {
            $actionClone = $action->getClone();
            $actionClone->livewire($this);
            $actionClone->record($model);
            $actions[] = $actionClone;
        }
        
        return $actions;
    }

    /**
     * Get board column actions with proper context.
     */
    public function getBoardColumnActions(string $columnId): array
    {
        $board = $this->getBoard();
        $actions = [];
        
        foreach ($board->getColumnActions() as $action) {
            $actionClone = $action->getClone();
            $actionClone->livewire($this);
            $actionClone->arguments(['column' => $columnId]);
            $actions[] = $actionClone;
        }
        
        return $actions;
    }

    /**
     * Get a board record by ID.
     */
    public function getBoardRecord(int | string $recordId): ?\Illuminate\Database\Eloquent\Model
    {
        $board = $this->getBoard();
        $query = $board->getQuery();
        
        return $query ? (clone $query)->find($recordId) : null;
    }

    /**
     * Legacy method names for view compatibility.
     */
    public function getColumnActionsForColumn(string $columnId): array
    {
        return $this->getBoardColumnActions($columnId);
    }

    public function getCardActionsForRecord(array $record): array
    {
        return $this->getBoardRecordActions($record);
    }

    public function getCardActionForRecord(array $record): ?string
    {
        return $this->getBoard()->getCardAction();
    }

    public function hasCardAction(array $record): bool
    {
        return $this->getCardActionForRecord($record) !== null;
    }
}

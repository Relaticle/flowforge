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
     * Get column actions for a specific column.
     */
    public function getColumnActionsForColumn(string $columnId): array
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
     * Get card actions for a record.
     */
    public function getCardActionsForRecord(array $record): array
    {
        $board = $this->getBoard();
        $actions = [];
        
        // Get the actual model for the record - clone query to avoid consumption
        $query = $board->getQuery();
        if (!$query) {
            return [];
        }
        
        $model = (clone $query)->find($record['id']);
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
     * Get card action for a record.
     */
    public function getCardActionForRecord(array $record): ?string
    {
        return $this->getBoard()->getCardAction();
    }

    /**
     * Check if a card has an action.
     */
    public function hasCardAction(array $record): bool
    {
        return $this->getCardActionForRecord($record) !== null;
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
     * Get board records for a column (pagination-aware).
     */
    public function getBoardRecordsForColumn(string $columnId): array
    {
        $board = $this->getBoard();
        $query = $board->getQuery();
        
        if (!$query) {
            return [];
        }
        
        $statusField = $board->getColumnIdentifierAttribute() ?? 'status';
        
        // Clone query to avoid modification issues
        $clonedQuery = clone $query;
        
        // Get pagination limit for this column
        $limit = $this->columnCardLimits[$columnId] ?? 10;
        
        $models = $clonedQuery->where($statusField, $columnId)
            ->limit($limit)
            ->get();

        // Format records for display with properties
        return $models->map(function ($model) use ($board) {
            return $this->formatRecordForDisplay($model, $board);
        })->toArray();
    }

    /**
     * Format a record for display with properties and actions.
     */
    protected function formatRecordForDisplay($model, Board $board): array
    {
        $titleField = $board->getCardTitleAttribute() ?? 'title';
        $descriptionField = $board->getDescriptionAttribute() ?? 'description';
        $statusField = $board->getColumnIdentifierAttribute() ?? 'status';

        $record = [
            'id' => $model->getKey(),
            'title' => data_get($model, $titleField),
            'description' => data_get($model, $descriptionField),
            'column' => data_get($model, $statusField),
        ];

        // Process card properties
        $cardProperties = $board->getCardProperties() ?? [];
        foreach ($cardProperties as $property) {
            $name = $property->getName();
            $value = $property->getFormattedState($model);

            if ($value !== null && $value !== '') {
                $record['attributes'][$name] = [
                    'label' => $property->getLabel(),
                    'value' => $value,
                    'color' => $property->getColor(),
                    'icon' => $property->getIcon(),
                    'iconColor' => $property->getIconColor(),
                ];
            }
        }

        return $record;
    }

    /**
     * Get board record count for a column.
     */
    public function getBoardRecordCountForColumn(string $columnId): int
    {
        $board = $this->getBoard();
        $query = $board->getQuery();
        
        if (!$query) {
            return 0;
        }
        
        $statusField = $board->getColumnIdentifierAttribute() ?? 'status';
        
        // Clone query to avoid modification issues
        $clonedQuery = clone $query;
        
        return $clonedQuery->where($statusField, $columnId)->count();
    }
}

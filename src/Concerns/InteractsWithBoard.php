<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Relaticle\Flowforge\Board;

trait InteractsWithBoard
{
    use HasTableFromBoard;

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
            if ($action instanceof ActionGroup) {
                foreach ($action->getFlatActions() as $flatAction) {
                    $this->cacheAction($flatAction);
                }
            } elseif ($action instanceof Action) {
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
        $board = $this->getBoard();
        $statusField = $board->getColumnIdentifierAttribute() ?? 'status';
        $query = $board->getQuery();

        if (! $query) {
            return false;
        }

        $success = true;

        // Update each record's status and order
        foreach ($recordIds as $index => $recordId) {
            $record = (clone $query)->find($recordId);

            if ($record) {
                // Handle enum status field properly
                $statusValue = $columnId;
                if (enum_exists($record->getCasts()[$statusField] ?? '')) {
                    $enumClass = $record->getCasts()[$statusField];
                    $statusValue = $enumClass::from($columnId);
                }

                $updateData = [$statusField => $statusValue];

                // Add sort order if reordering is configured
                $reorderBy = $board->getReorderBy();
                if ($reorderBy && $this->modelHasOrderColumn($record, $reorderBy['column'])) {
                    // For DESC order: first item (index 0) gets highest value
                    // For ASC order: first item (index 0) gets lowest value (0)
                    if ($reorderBy['direction'] === 'desc') {
                        $updateData[$reorderBy['column']] = count($recordIds) - 1 - $index;
                    } else {
                        $updateData[$reorderBy['column']] = $index;
                    }
                }

                $success = $record->update($updateData) && $success;
            }
        }

        return $success;
    }

    /**
     * Check if model has the specified order column.
     */
    protected function modelHasOrderColumn($model, string $columnName): bool
    {
        try {
            $table = $model->getTable();
            $schema = $model->getConnection()->getSchemaBuilder();

            return $schema->hasColumn($table, $columnName);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Load more items for a column.
     */
    public function loadMoreItems(string $columnId, ?int $count = null): void
    {
        $count = $count ?? $this->cardsIncrement;
        $currentLimit = $this->columnCardLimits[$columnId] ?? 10;
        $this->columnCardLimits[$columnId] = $currentLimit + $count;

        // Just refresh without dispatching events that might reset filter state
        // The board will automatically refresh to show more items
    }

    /**
     * Get the default record for an action (Filament's record injection system).
     */
    public function getDefaultActionRecord(Action $action): ?Model
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

        if (! $recordKey) {
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

        if (! $query) {
            return [];
        }

        $statusField = $board->getColumnIdentifierAttribute() ?? 'status';
        $limit = $this->columnCardLimits[$columnId] ?? 10;

        $queryClone = (clone $query)->where($statusField, $columnId);

        // Apply board filters if available and active
        if (method_exists($this, 'applyFiltersToBoardQuery') && $board->hasBoardFilters()) {
            $queryClone = $this->applyFiltersToBoardQuery($queryClone);
        }

        // Apply ordering if configured
        $reorderBy = $board->getReorderBy();
        if ($reorderBy) {
            $queryClone->orderBy($reorderBy['column'], $reorderBy['direction']);
        }

        return $queryClone->limit($limit)->get()->toArray();
    }

    /**
     * Get board record count for a column (standardized Filament naming).
     */
    public function getBoardColumnRecordCount(string $columnId): int
    {
        $board = $this->getBoard();
        $query = $board->getQuery();

        if (! $query) {
            return 0;
        }

        $statusField = $board->getColumnIdentifierAttribute() ?? 'status';
        $queryClone = (clone $query)->where($statusField, $columnId);

        // Apply board filters if available and active
        if (method_exists($this, 'applyFiltersToBoardQuery') && $board->hasBoardFilters()) {
            $queryClone = $this->applyFiltersToBoardQuery($queryClone);
        }

        return $queryClone->count();
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
        if (! $model) {
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
    public function getBoardRecord(int | string $recordId): ?Model
    {
        $board = $this->getBoard();
        $query = $board->getQuery();

        return $query ? (clone $query)->find($recordId) : null;
    }

    /**
     * Get column actions for a column (delegates to Board).
     */
    public function getColumnActionsForColumn(string $columnId): array
    {
        return $this->getBoard()->getBoardColumnActions($columnId);
    }

    /**
     * Get card actions for a record (delegates to Board).
     */
    public function getCardActionsForRecord(array $record): array
    {
        return $this->getBoard()->getBoardRecordActions($record);
    }

    /**
     * Get card action for a record (delegates to Board).
     */
    public function getCardActionForRecord(array $record): ?string
    {
        return $this->getBoard()->getCardAction();
    }

    /**
     * Check if card has action.
     */
    public function hasCardAction(array $record): bool
    {
        return $this->getCardActionForRecord($record) !== null;
    }
}

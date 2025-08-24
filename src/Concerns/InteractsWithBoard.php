<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Services\Rank;
use Throwable;

trait InteractsWithBoard
{
    use HasTableFromBoard;

    protected Board $board;

    /**
     * Cards per column pagination state.
     */
    public array $columnCardLimits = [];

    /**
     * Loading states for columns.
     */
    public array $loadingStates = [];

    /**
     * Default cards per column.
     */
    public int $cardsIncrement = 20;

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
            ->query(fn (): ?Builder => $this->getBoardQuery());
    }

    /**
     * Move card to new position using Rank-based positioning.
     *
     * @throws Throwable
     */
    public function moveCard(
        string $cardId,
        string $targetColumnId,
        ?string $afterCardId = null,
        ?string $beforeCardId = null
    ): void {
        $board = $this->getBoard();
        $query = $board->getQuery();

        if (! $query) {
            throw new InvalidArgumentException('Board query not available');
        }

        $card = (clone $query)->find($cardId);
        if (! $card) {
            throw new InvalidArgumentException("Card not found: {$cardId}");
        }

        // Calculate new position using Rank service
        $newPosition = $this->calculatePositionBetweenCards($afterCardId, $beforeCardId, $targetColumnId);

        // Use transaction for data consistency
        DB::transaction(function () use ($card, $board, $targetColumnId, $newPosition) {
            $columnIdentifier = $board->getColumnIdentifierAttribute();
            $columnValue = $this->resolveStatusValue($card, $columnIdentifier, $targetColumnId);
            $positionIdentifier = $board->getPositionIdentifierAttribute();

            $card->update([
                $columnIdentifier => $columnValue,
                $positionIdentifier => $newPosition,
            ]);
        });

        // Emit success event after successful transaction
        $this->dispatch('kanban-card-moved', [
            'cardId' => $cardId,
            'columnId' => $targetColumnId,
            'position' => $newPosition,
        ]);
    }

    public function loadMoreItems(string $columnId, ?int $count = null): void
    {
        $count = $count ?? $this->cardsIncrement;

        // Set loading state
        $this->loadingStates[$columnId] = true;

        try {
            $currentLimit = $this->columnCardLimits[$columnId] ?? 20;
            $this->columnCardLimits[$columnId] = $currentLimit + $count;

            // Emit event for frontend update
            $this->dispatch('kanban-items-loaded', [
                'columnId' => $columnId,
                'loadedCount' => $count,
            ]);

        } finally {
            // Clear loading state
            $this->loadingStates[$columnId] = false;
        }
    }

    /**
     * Load all items in a column (disables pagination for that column).
     */
    public function loadAllItems(string $columnId): void
    {
        $this->loadingStates[$columnId] = true;

        try {
            $board = $this->getBoard();
            $totalCount = $board->getBoardRecordCount($columnId);

            // Set limit to total count to load everything
            $this->columnCardLimits[$columnId] = $totalCount;

            $this->dispatch('kanban-all-items-loaded', [
                'columnId' => $columnId,
                'totalCount' => $totalCount,
            ]);

        } finally {
            $this->loadingStates[$columnId] = false;
        }
    }

    /**
     * Check if a column is fully loaded.
     */
    public function isColumnFullyLoaded(string $columnId): bool
    {
        $board = $this->getBoard();
        $totalCount = $board->getBoardRecordCount($columnId);
        $loadedCount = $this->columnCardLimits[$columnId] ?? 20;

        return $loadedCount >= $totalCount;
    }

    /**
     * Calculate position between specific cards (for drag-drop).
     */
    protected function calculatePositionBetweenCards(
        ?string $afterCardId = null,
        ?string $beforeCardId = null,
        ?string $columnId = null
    ): string {
        if (! $afterCardId && ! $beforeCardId && $columnId) {
            return $this->getBoardPositionInColumn($columnId, 'bottom');
        }

        $query = $this->getBoard()->getQuery();
        if (! $query) {
            return Rank::forEmptySequence()->get();
        }

        $positionField = $this->getBoard()->getPositionIdentifierAttribute();
        $beforePos = $beforeCardId ? (clone $query)->find($beforeCardId)?->getAttribute($positionField) : null;
        $afterPos = $afterCardId ? (clone $query)->find($afterCardId)?->getAttribute($positionField) : null;

        if ($beforePos && $afterPos && is_string($beforePos) && is_string($afterPos)) {
            return Rank::betweenRanks(Rank::fromString($beforePos), Rank::fromString($afterPos))->get();
        }

        if ($beforePos && is_string($beforePos)) {
            return Rank::after(Rank::fromString($beforePos))->get();
        }

        if ($afterPos && is_string($afterPos)) {
            return Rank::before(Rank::fromString($afterPos))->get();
        }

        return Rank::forEmptySequence()->get();
    }

    /**
     * Resolve status value, handling enums properly.
     */
    protected function resolveStatusValue(Model $card, string $statusField, string $targetColumnId): mixed
    {
        $castType = $card->getCasts()[$statusField] ?? null;

        if ($castType && enum_exists($castType)) {
            return $castType::from($targetColumnId);
        }

        return $targetColumnId;
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
     * Get next board position for a column with direction control.
     */
    public function getBoardPositionInColumn(string $columnId, string $position = 'top'): string
    {
        $query = $this->getBoard()->getQuery();
        if (! $query) {
            return Rank::forEmptySequence()->get();
        }

        $board = $this->getBoard();
        $statusField = $board->getColumnIdentifierAttribute();
        $positionField = $board->getPositionIdentifierAttribute();
        $queryClone = (clone $query)->where($statusField, $columnId);

        if ($position === 'top') {
            $firstRecord = $queryClone->orderBy($positionField, 'asc')->first();

            return $firstRecord
                ? Rank::before(Rank::fromString($firstRecord->getAttribute($positionField)))->get()
                : Rank::forEmptySequence()->get();
        }

        $lastRecord = $queryClone->orderBy($positionField, 'desc')->first();

        return $lastRecord
            ? Rank::after(Rank::fromString($lastRecord->getAttribute($positionField)))->get()
            : Rank::forEmptySequence()->get();
    }
}

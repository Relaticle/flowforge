<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\Exceptions\MaxRetriesExceededException;
use Relaticle\Flowforge\Services\Rank;
use Throwable;

trait InteractsWithBoard
{
    use InteractsWithBoardTable;

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

        // Calculate and update position with automatic retry on conflicts
        $newPosition = $this->calculateAndUpdatePositionWithRetry($card, $targetColumnId, $afterCardId, $beforeCardId);

        // Emit success event after successful transaction
        $this->dispatch('kanban-card-moved', [
            'cardId' => $cardId,
            'columnId' => $targetColumnId,
            'position' => $newPosition,
        ]);
    }

    /**
     * Calculate position and update card within transaction with pessimistic locking.
     * This prevents race conditions when multiple users drag cards simultaneously.
     */
    protected function calculateAndUpdatePosition(
        Model $card,
        string $targetColumnId,
        ?string $afterCardId,
        ?string $beforeCardId
    ): string {
        $newPosition = null;

        DB::transaction(function () use ($card, $targetColumnId, $afterCardId, $beforeCardId, &$newPosition) {
            $board = $this->getBoard();
            $query = $board->getQuery();
            $positionField = $board->getPositionIdentifierAttribute();

            // LOCK reference cards for reading to prevent stale data
            $afterCard = $afterCardId
                ? (clone $query)->where('id', $afterCardId)->lockForUpdate()->first()
                : null;

            $beforeCard = $beforeCardId
                ? (clone $query)->where('id', $beforeCardId)->lockForUpdate()->first()
                : null;

            // Calculate position INSIDE transaction with locked data
            $newPosition = $this->calculatePositionBetweenLockedCards(
                $afterCard,
                $beforeCard,
                $targetColumnId
            );

            // Update card position
            $columnIdentifier = $board->getColumnIdentifierAttribute();
            $columnValue = $this->resolveStatusValue($card, $columnIdentifier, $targetColumnId);

            $card->update([
                $columnIdentifier => $columnValue,
                $positionField => $newPosition,
            ]);
        });

        return $newPosition;
    }

    /**
     * Calculate position between locked cards (used within transaction).
     */
    protected function calculatePositionBetweenLockedCards(
        ?Model $afterCard,
        ?Model $beforeCard,
        string $columnId
    ): string {
        if (! $afterCard && ! $beforeCard) {
            return $this->getBoardPositionInColumn($columnId, 'bottom');
        }

        $positionField = $this->getBoard()->getPositionIdentifierAttribute();

        $beforePos = $beforeCard?->getAttribute($positionField);
        $afterPos = $afterCard?->getAttribute($positionField);

        if ($beforePos && $afterPos && is_string($beforePos) && is_string($afterPos)) {
            return Rank::betweenRanks(Rank::fromString($afterPos), Rank::fromString($beforePos))->get();
        }

        if ($beforePos && is_string($beforePos)) {
            return Rank::before(Rank::fromString($beforePos))->get();
        }

        if ($afterPos && is_string($afterPos)) {
            return Rank::after(Rank::fromString($afterPos))->get();
        }

        return Rank::forEmptySequence()->get();
    }

    /**
     * Calculate and update position with automatic retry on conflicts.
     * Wraps calculateAndUpdatePosition() with retry logic to handle rare duplicate position conflicts.
     */
    protected function calculateAndUpdatePositionWithRetry(
        Model $card,
        string $targetColumnId,
        ?string $afterCardId,
        ?string $beforeCardId,
        int $maxAttempts = 3
    ): string {
        $baseDelay = 50; // milliseconds
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $this->calculateAndUpdatePosition(
                    $card,
                    $targetColumnId,
                    $afterCardId,
                    $beforeCardId
                );
            } catch (QueryException $e) {
                // Check if this is a unique constraint violation
                if (! $this->isDuplicatePositionError($e)) {
                    throw $e; // Not a duplicate, rethrow
                }

                $lastException = $e;

                // Log the conflict for monitoring
                Log::info('Position conflict detected, retrying', [
                    'card_id' => $card->id,
                    'target_column' => $targetColumnId,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                ]);

                // Max retries reached?
                if ($attempt >= $maxAttempts) {
                    throw new MaxRetriesExceededException(
                        "Failed to move card after {$maxAttempts} attempts due to position conflicts",
                        previous: $e
                    );
                }

                // Exponential backoff: 50ms, 100ms, 200ms
                $delay = $baseDelay * pow(2, $attempt - 1);
                usleep($delay * 1000);

                // Refresh reference cards before retry (they may have moved)
                continue;
            }
        }

        // Should never reach here
        throw $lastException ?? new \RuntimeException('Unexpected retry loop exit');
    }

    /**
     * Check if a QueryException is due to unique constraint violation on positions.
     */
    protected function isDuplicatePositionError(QueryException $e): bool
    {
        $errorCode = $e->errorInfo[1] ?? null;

        // SQLite: SQLITE_CONSTRAINT (19)
        // MySQL: ER_DUP_ENTRY (1062)
        // PostgreSQL: unique_violation (23505)

        return in_array($errorCode, [19, 1062, 23505]) ||
               str_contains($e->getMessage(), 'unique_position_per_column') ||
               str_contains($e->getMessage(), 'UNIQUE constraint failed');
    }

    public function loadMoreItems(string $columnId, ?int $count = null): void
    {
        $count = $count ?? $this->getBoard()->getCardsPerColumn();

        // Set loading state
        $this->loadingStates[$columnId] = true;

        try {
            $board = $this->getBoard();
            $currentLimit = $this->columnCardLimits[$columnId] ?? $board->getCardsPerColumn();
            $newLimit = $currentLimit + $count;

            // Check if we have more items to load
            $totalCount = $board->getBoardRecordCount($columnId);
            $actualNewLimit = min($newLimit, $totalCount);

            $this->columnCardLimits[$columnId] = $actualNewLimit;

            // Calculate how many items were actually loaded
            $actualLoadedCount = $actualNewLimit - $currentLimit;

            // Emit event for frontend update
            $this->dispatch('kanban-items-loaded', [
                'columnId' => $columnId,
                'loadedCount' => $actualLoadedCount,
                'totalCount' => $totalCount,
                'isFullyLoaded' => $actualNewLimit >= $totalCount,
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
        $loadedCount = $this->columnCardLimits[$columnId] ?? $board->getCardsPerColumn();

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

        $beforeCard = $beforeCardId ? (clone $query)->find($beforeCardId) : null;
        $beforePos = $beforeCard?->getAttribute($positionField);

        $afterCard = $afterCardId ? (clone $query)->find($afterCardId) : null;
        $afterPos = $afterCard?->getAttribute($positionField);

        if ($beforePos && $afterPos && is_string($beforePos) && is_string($afterPos)) {
            return Rank::betweenRanks(Rank::fromString($afterPos), Rank::fromString($beforePos))->get();
        }

        if ($beforePos && is_string($beforePos)) {
            return Rank::before(Rank::fromString($beforePos))->get();
        }

        if ($afterPos && is_string($afterPos)) {
            return Rank::after(Rank::fromString($afterPos))->get();
        }

        return Rank::forEmptySequence()->get();
    }

    /**
     * Resolve status value, handling enums properly.
     */
    protected function resolveStatusValue(Model $card, string $statusField, string $targetColumnId): mixed
    {
        $castType = $card->getCasts()[$statusField] ?? null;

        if ($castType && enum_exists($castType) && is_subclass_of($castType, \BackedEnum::class)) {
            /** @var class-string<\BackedEnum> $castType */
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

        foreach ($board->getRecordActions() as $action) {
            $actionClone = $action->getClone();
            $actionClone->livewire($this);
            $actionClone->record($record['model']);
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
     * Get next board position for a column with direction control.
     * Handles null positions gracefully and ensures valid position assignment.
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
            // Get first valid position (ignore null positions)
            $firstRecord = $queryClone
                ->whereNotNull($positionField)
                ->orderBy($positionField, 'asc')
                ->first();

            if ($firstRecord) {
                $firstPosition = $firstRecord->getAttribute($positionField);
                if (is_string($firstPosition)) {
                    return Rank::before(Rank::fromString($firstPosition))->get();
                }
            }

            return Rank::forEmptySequence()->get();
        }

        // Get last valid position (ignore null positions)
        $lastRecord = $queryClone
            ->whereNotNull($positionField)
            ->orderBy($positionField, 'desc')
            ->first();

        if ($lastRecord) {
            $lastPosition = $lastRecord->getAttribute($positionField);
            if (is_string($lastPosition)) {
                return Rank::after(Rank::fromString($lastPosition))->get();
            }
        }

        return Rank::forEmptySequence()->get();
    }
}

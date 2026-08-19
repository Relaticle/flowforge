<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

/**
 * Clean action management for Board (mirrors Filament's HasActions).
 */
trait HasBoardActions
{
    /**
     * @var array<Action|ActionGroup>
     */
    protected array $actions = [];

    /**
     * @var array<Action|ActionGroup>
     */
    protected array $recordActions = [];

    /**
     * @var array<Action|ActionGroup>
     */
    protected array $columnActions = [];

    protected ?string $cardAction = null;

    protected array $registeredCardActions = [];

    /**
     * Configure board-level actions.
     */
    public function actions(array | Closure $actions): static
    {
        $this->actions = $this->evaluate($actions);

        return $this;
    }

    /**
     * Configure record-level actions.
     */
    public function recordActions(array | Closure $actions): static
    {
        $this->recordActions = $this->evaluate($actions);

        return $this;
    }

    /**
     * Configure column-level actions.
     */
    public function columnActions(array | Closure $actions): static
    {
        $this->columnActions = $this->evaluate($actions);

        return $this;
    }

    /**
     * Set default card action.
     */
    public function cardAction(string | Closure | null $action): static
    {
        $this->cardAction = $this->evaluate($action);

        return $this;
    }

    /**
     * Alias for recordActions (API compatibility).
     */
    public function cardActions(array | Closure $actions): static
    {
        return $this->recordActions($actions);
    }

    /**
     * Get board-level actions.
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get record-level actions.
     */
    public function getRecordActions(): array
    {
        return $this->recordActions;
    }

    /**
     * Get column-level actions.
     */
    public function getColumnActions(): array
    {
        return $this->columnActions;
    }

    /**
     * Get default card action.
     */
    public function getCardAction(): ?string
    {
        return $this->cardAction;
    }

    /**
     * Find the configured card action among already-processed record actions.
     *
     * The record actions are passed in rather than rebuilt so the returned action
     * keeps the record binding applied by getBoardRecordActions(), which is what
     * makes per-record closures such as ->url(fn ($record) => ...) resolvable.
     *
     * @param  array<Action|ActionGroup>  $recordActions
     */
    public function resolveCardAction(array $recordActions): ?Action
    {
        $name = $this->getCardAction();

        if (blank($name)) {
            return null;
        }

        foreach ($recordActions as $action) {
            if ($action instanceof ActionGroup) {
                foreach ($action->getFlatActions() as $flatAction) {
                    if ($flatAction->getName() === $name) {
                        return $flatAction;
                    }
                }

                continue;
            }

            if ($action->getName() === $name) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Get registered card actions.
     */
    public function getRegisteredCardActions(): array
    {
        return $this->registeredCardActions;
    }

    /**
     * Get a specific registered card action.
     */
    public function getRegisteredCardAction(string $name): ?Action
    {
        return $this->registeredCardActions[$name] ?? null;
    }

    /**
     * Process record actions for a specific record (delegates to Livewire).
     */
    public function getBoardRecordActions(array $record): array
    {
        $livewire = $this->getLivewire();

        if (method_exists($livewire, 'getBoardRecordActions')) {
            return $livewire->getBoardRecordActions($record);
        }

        // Fallback: return raw actions
        return $this->getRecordActions();
    }

    /**
     * Process column actions for a specific column (delegates to Livewire).
     */
    public function getBoardColumnActions(string $columnId): array
    {
        $livewire = $this->getLivewire();

        if (method_exists($livewire, 'getBoardColumnActions')) {
            return $livewire->getBoardColumnActions($columnId);
        }

        // Fallback: return raw actions
        return $this->getColumnActions();
    }
}

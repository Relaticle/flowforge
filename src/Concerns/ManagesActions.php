<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

/**
 * Unified trait for managing all board actions.
 * Consolidates HasActions, HasCardAction, HasRecordActions into one focused trait.
 */
trait ManagesActions
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

    /**
     * Default card action name.
     */
    protected ?string $cardAction = null;

    /**
     * Registered card actions by name.
     */
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
     * Set the default card action.
     */
    public function cardAction(string | Closure | null $action): static
    {
        $this->cardAction = $this->evaluate($action);

        return $this;
    }

    /**
     * Register a named card action.
     */
    public function registerCardAction(string $name, Action $action): static
    {
        $this->registeredCardActions[$name] = $action;

        return $this;
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
     * Get the default card action.
     */
    public function getCardAction(): ?string
    {
        return $this->cardAction;
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
     * Get all actions flattened.
     */
    public function getFlatActions(): array
    {
        $actions = [];

        foreach ([...$this->actions, ...$this->recordActions, ...$this->columnActions] as $action) {
            if ($action instanceof ActionGroup) {
                $actions = [...$actions, ...$action->getFlatActions()];
            } elseif ($action instanceof Action) {
                $actions[] = $action;
            }
        }

        return $actions;
    }

    /**
     * Find an action by name.
     */
    public function getAction(string $name): ?Action
    {
        foreach ($this->getFlatActions() as $action) {
            if ($action->getName() === $name) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Alias for recordActions (Filament API compatibility).
     */
    public function cardActions(array | Closure $actions): static
    {
        return $this->recordActions($actions);
    }
}
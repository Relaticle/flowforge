<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

trait HasActions
{
    /**
     * @var array<Action|ActionGroup>
     */
    protected array $cardActions = [];

    /**
     * @var array<Action|ActionGroup>
     */
    protected array $columnActions = [];

    /**
     * @param array<Action|ActionGroup> $actions
     */
    public function cardActions(array $actions): static
    {
        $this->cardActions = [];
        $this->pushCardActions($actions);

        return $this;
    }

    /**
     * @param array<Action|ActionGroup> $actions
     */
    public function pushCardActions(array $actions): static
    {
        foreach ($actions as $action) {
            $this->pushCardAction($action);
        }

        return $this;
    }

    public function pushCardAction(Action|ActionGroup $action): static
    {
        $this->cardActions[] = $action;

        return $this;
    }

    /**
     * @param array<Action|ActionGroup> $actions
     */
    public function columnActions(array $actions): static
    {
        $this->columnActions = [];
        $this->pushColumnActions($actions);

        return $this;
    }

    /**
     * @param array<Action|ActionGroup> $actions
     */
    public function pushColumnActions(array $actions): static
    {
        foreach ($actions as $action) {
            $this->pushColumnAction($action);
        }

        return $this;
    }

    public function pushColumnAction(Action|ActionGroup $action): static
    {
        $this->columnActions[] = $action;

        return $this;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getCardActions(): array
    {
        return $this->cardActions;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getColumnActions(): array
    {
        return $this->columnActions;
    }

    /**
     * Alias for getCardActions() to maintain backwards compatibility
     * 
     * @return array<Action|ActionGroup>
     */
    public function getRecordActions(): array
    {
        return $this->getCardActions();
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'cardActions' => [$this->getCardActions()],
            'columnActions' => [$this->getColumnActions()],
            'recordActions' => [$this->getCardActions()], // backwards compatibility
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
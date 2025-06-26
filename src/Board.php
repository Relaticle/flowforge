<?php

namespace Relaticle\Flowforge;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

class Board
{
    protected ?string $recordTitleAttribute = null;

    protected ?string $columnIdentifierAttribute = null;

    protected ?array $defaultSort = null;

    /**
     * @var Column[]
     */
    protected array $columns = [];

    /**
     * @var Action[]|ActionGroup[]
     */
    protected array $columnActions = [];

    protected array $recordProperties = [];

    /**
     * @var Action[]|ActionGroup[]
     */
    protected array $recordActions = [];

    public function recordTitleAttribute(string $attribute): static
    {
        $this->recordTitleAttribute = $attribute;

        return $this;
    }

    public function columnIdentifierAttribute(string $attribute): static
    {
        $this->columnIdentifierAttribute = $attribute;

        return $this;
    }

    public function defaultSort(string $column, string $direction = 'asc'): static
    {
        $this->defaultSort = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * @param  Column[]  $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @param  Action[]|ActionGroup[]  $actions
     */
    public function columnActions(array $actions): static
    {
        $this->columnActions = $actions;

        return $this;
    }

    public function getRecordTitleAttribute(): ?string
    {
        return $this->recordTitleAttribute;
    }

    public function getColumnIdentifierAttribute(): ?string
    {
        return $this->columnIdentifierAttribute;
    }

    public function getDefaultSort(): ?array
    {
        return $this->defaultSort;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param  Property[]  $properties
     */
    public function recordProperties(array $properties): static
    {
        $this->recordProperties = $properties;

        return $this;
    }

    /**
     * @return Property[]
     */
    public function getRecordProperties(): array
    {
        return $this->recordProperties;
    }

    /**
     * @param  Action[]|ActionGroup[]  $actions
     */
    public function recordActions(array $actions): static
    {
        $this->recordActions = $actions;

        return $this;
    }

    /**
     * @return Action[]|ActionGroup[]
     */
    public function getColumnActions(): array
    {
        return $this->columnActions;
    }

    /**
     * @return Action[]|ActionGroup[]
     */
    public function getRecordActions(): array
    {
        return $this->recordActions;
    }
}

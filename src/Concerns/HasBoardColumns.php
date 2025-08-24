<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Relaticle\Flowforge\Column;

/**
 * Clean column management for Board (mirrors Filament's HasColumns).
 */
trait HasBoardColumns
{
    /**
     * @var array<Column>
     */
    protected array $columns = [];

    /**
     * Configure board columns.
     */
    public function columns(array | Closure $columns): static
    {
        $this->columns = $this->evaluate($columns);

        foreach ($this->columns as $column) {
            if ($column instanceof Column) {
                $column->board($this);
            }
        }

        return $this;
    }

    /**
     * Get configured columns.
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get a specific column by identifier.
     */
    public function getColumn(string $identifier): ?Column
    {
        foreach ($this->columns as $column) {
            if ($column->getName() === $identifier) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Get column identifiers.
     */
    public function getColumnIdentifiers(): array
    {
        return array_map(fn (Column $column) => $column->getName(), $this->columns);
    }

    /**
     * Get column labels mapped by identifier.
     */
    public function getColumnLabels(): array
    {
        $labels = [];

        foreach ($this->columns as $column) {
            $labels[$column->getName()] = $column->getLabel() ?? $column->getName();
        }

        return $labels;
    }

    /**
     * Get column colors mapped by identifier.
     */
    public function getColumnColors(): array
    {
        $colors = [];

        foreach ($this->columns as $column) {
            if ($color = $column->getColor()) {
                $colors[$column->getName()] = $color;
            }
        }

        return $colors;
    }
}

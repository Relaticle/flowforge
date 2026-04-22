<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
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
     * Get configured columns, excluding any that are hidden.
     *
     * @return array<Column>
     */
    public function getColumns(): array
    {
        return $this->getVisibleColumns();
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
     * Get column identifiers for visible columns.
     *
     * @return array<string>
     */
    public function getColumnIdentifiers(): array
    {
        return array_map(fn (Column $column) => $column->getName(), $this->getVisibleColumns());
    }

    /**
     * Get column labels mapped by identifier for visible columns.
     *
     * @return array<string, string|Htmlable>
     */
    public function getColumnLabels(): array
    {
        $labels = [];

        foreach ($this->getVisibleColumns() as $column) {
            $labels[$column->getName()] = $column->getLabel() ?? $column->getName();
        }

        return $labels;
    }

    /**
     * Get column colors mapped by identifier for visible columns.
     *
     * @return array<string, mixed>
     */
    public function getColumnColors(): array
    {
        $colors = [];

        foreach ($this->getVisibleColumns() as $column) {
            if ($color = $column->getColor()) {
                $colors[$column->getName()] = $color;
            }
        }

        return $colors;
    }

    /**
     * Internal helper returning a sequentially keyed list of visible columns.
     *
     * @return array<int, Column>
     */
    protected function getVisibleColumns(): array
    {
        return array_values(array_filter(
            $this->columns,
            fn (Column $column) => $column->isVisible(),
        ));
    }
}

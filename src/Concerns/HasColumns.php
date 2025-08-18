<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Relaticle\Flowforge\Column;

trait HasColumns
{
    /**
     * @var array<string, Column>
     */
    protected array $columns = [];

    /**
     * @param array<Column> $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = [];
        $this->pushColumns($columns);

        return $this;
    }

    /**
     * @param array<Column> $columns
     */
    public function pushColumns(array $columns): static
    {
        foreach ($columns as $column) {
            $this->pushColumn($column);
        }

        return $this;
    }

    public function pushColumn(Column $column): static
    {
        $column->board($this);
        $this->columns[$column->getName()] = $column;

        return $this;
    }

    /**
     * @return array<string, Column>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getColumn(string $name): ?Column
    {
        return $this->columns[$name] ?? null;
    }

    public function hasColumn(string $name): bool
    {
        return array_key_exists($name, $this->columns);
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'columns' => [$this->getColumns()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
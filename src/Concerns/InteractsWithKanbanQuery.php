<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait InteractsWithKanbanQuery
{
    protected Builder | Relation | Closure | null $query = null;

    protected string | Closure | null $columnIdentifierAttribute = null;

    protected array | Closure | null $reorderBy = null;

    public function query(Builder | Relation | Closure $query): static
    {
        $this->query = $query;

        return $this;
    }

    public function columnIdentifier(string | Closure $attribute): static
    {
        $this->columnIdentifierAttribute = $attribute;

        return $this;
    }

    public function reorderBy(string $column, string $direction = 'asc'): static
    {
        $this->reorderBy = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    public function getQuery(): Builder|Relation|null
    {
        return $this->evaluate($this->query);
    }

    public function getColumnIdentifierAttribute(): ?string
    {
        return $this->evaluate($this->columnIdentifierAttribute);
    }

    public function getReorderBy(): ?array
    {
        return $this->evaluate($this->reorderBy);
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'query' => [$this->getQuery()],
            'columnIdentifierAttribute' => [$this->getColumnIdentifierAttribute()],
            'reorderBy' => [$this->getReorderBy()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}

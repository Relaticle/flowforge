<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;

trait InteractsWithKanbanQuery
{
    protected Builder | Closure | null $query = null;
    protected string | Closure | null $columnIdentifierAttribute = null;
    protected array | Closure | null $reorderBy = null;

    public function query(Builder | Closure $query): static
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

    public function getQuery(): ?Builder
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

    public function isReadonly(): bool
    {
        return $this->getReorderBy() === null;
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

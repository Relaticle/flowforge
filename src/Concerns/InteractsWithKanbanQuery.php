<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;

trait InteractsWithKanbanQuery
{
    protected Builder | Closure | null $query = null;

    protected string | Closure | null $columnIdentifierAttribute = null;

    protected string | Closure | null $positionIdentifierAttribute = null;

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

    public function positionIdentifier(string | Closure $attribute): static
    {
        $this->positionIdentifierAttribute = $attribute;

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

    public function getPositionIdentifierAttribute(): ?string
    {
        return $this->evaluate($this->positionIdentifierAttribute);
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'query' => [$this->getQuery()],
            'columnIdentifierAttribute' => [$this->getColumnIdentifierAttribute()],
            'positionIdentifierAttribute' => [$this->getPositionIdentifierAttribute()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}

<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait InteractsWithKanbanQuery
{
    protected Builder|Closure|null $query = null;

    protected string|Closure|null $cardTitleAttribute = null;

    protected string|Closure|null $columnIdentifierAttribute = null;

    protected string|Closure|null $descriptionAttribute = null;

    protected array|Closure|null $defaultSort = null;

    public function query(Builder|Closure $query): static
    {
        $this->query = $query;

        return $this;
    }

    public function cardTitle(string|Closure $attribute): static
    {
        $this->cardTitleAttribute = $attribute;

        return $this;
    }

    public function columnIdentifier(string|Closure $attribute): static
    {
        $this->columnIdentifierAttribute = $attribute;

        return $this;
    }

    public function cardDescription(string|Closure $attribute): static
    {
        $this->descriptionAttribute = $attribute;

        return $this;
    }

    /**
     * @deprecated Use cardDescription() instead
     */
    public function description(string|Closure $attribute): static
    {
        return $this->cardDescription($attribute);
    }

    public function defaultSort(string $column, string $direction = 'asc'): static
    {
        $this->defaultSort = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    public function getQuery(): ?Builder
    {
        return $this->evaluate($this->query);
    }

    public function getCardTitleAttribute(): ?string
    {
        return $this->evaluate($this->cardTitleAttribute);
    }

    public function getColumnIdentifierAttribute(): ?string
    {
        return $this->evaluate($this->columnIdentifierAttribute);
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->evaluate($this->descriptionAttribute);
    }

    public function getDefaultSort(): ?array
    {
        return $this->evaluate($this->defaultSort);
    }

    /**
     * Backwards compatibility aliases
     */
    public function recordTitleAttribute(string|Closure $attribute): static
    {
        return $this->cardTitle($attribute);
    }

    public function getRecordTitleAttribute(): ?string
    {
        return $this->getCardTitleAttribute();
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'query' => [$this->getQuery()],
            'cardTitleAttribute', 'recordTitleAttribute' => [$this->getCardTitleAttribute()],
            'columnIdentifierAttribute' => [$this->getColumnIdentifierAttribute()],
            'descriptionAttribute' => [$this->getDescriptionAttribute()],
            'defaultSort' => [$this->getDefaultSort()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
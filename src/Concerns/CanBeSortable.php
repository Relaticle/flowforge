<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;

trait CanBeSortable
{
    protected bool | Closure $isSortable = false;

    public function sortable(bool | Closure $condition = true): static
    {
        $this->isSortable = $condition;

        return $this;
    }

    public function isSortable(): bool
    {
        return (bool) $this->evaluate($this->isSortable);
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'isSortable' => [$this->isSortable()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}

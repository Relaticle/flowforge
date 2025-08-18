<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;

trait HasLimit
{
    protected int|Closure|null $limit = null;

    public function limit(int|Closure|null $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->evaluate($this->limit);
    }

    public function hasLimit(): bool
    {
        return $this->getLimit() !== null;
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'limit' => [$this->getLimit()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
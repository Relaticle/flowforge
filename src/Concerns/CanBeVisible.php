<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;

trait CanBeVisible
{
    protected bool | Closure $isVisible = true;

    protected bool | Closure $isHidden = false;

    public function visible(bool | Closure $condition = true): static
    {
        $this->isVisible = $condition;

        return $this;
    }

    public function hidden(bool | Closure $condition = true): static
    {
        $this->isHidden = $condition;

        return $this;
    }

    public function isVisible(): bool
    {
        if ($this->isHidden()) {
            return false;
        }

        return (bool) $this->evaluate($this->isVisible);
    }

    public function isHidden(): bool
    {
        return (bool) $this->evaluate($this->isHidden);
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'isVisible' => [$this->isVisible()],
            'isHidden' => [$this->isHidden()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}

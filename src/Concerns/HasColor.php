<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;

trait HasColor
{
    protected string|array|Closure|null $color = null;

    public function color(string|array|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): string|array|null
    {
        return $this->evaluate($this->color);
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'color' => [$this->getColor()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;

trait HasIcon
{
    protected string|Closure|null $icon = null;

    protected string|Closure|null $iconPosition = null;

    protected string|array|Closure|null $iconColor = null;

    public function icon(string|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function iconPosition(string|Closure $position): static
    {
        $this->iconPosition = $position;

        return $this;
    }

    public function iconColor(string|array|Closure|null $color): static
    {
        $this->iconColor = $color;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->evaluate($this->icon);
    }

    public function getIconPosition(): ?string
    {
        return $this->evaluate($this->iconPosition);
    }

    public function getIconColor(): string|array|null
    {
        return $this->evaluate($this->iconColor);
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'icon' => [$this->getIcon()],
            'iconPosition' => [$this->getIconPosition()],
            'iconColor' => [$this->getIconColor()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
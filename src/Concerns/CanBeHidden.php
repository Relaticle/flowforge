<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Filament\Support\Components\ViewComponent;

/**
 * Adds Filament-style visibility controls to a component.
 *
 * The consuming class MUST expose an `evaluate()` method capable of resolving
 * `bool | Closure` values. In practice this trait should only be applied to
 * classes extending {@see ViewComponent} (or any other Filament component
 * class that pulls in `Filament\Support\Concerns\EvaluatesClosures`).
 *
 * @mixin ViewComponent
 */
trait CanBeHidden
{
    protected bool | Closure $isHidden = false;

    protected bool | Closure $isVisible = true;

    public function hidden(bool | Closure $condition = true): static
    {
        $this->isHidden = $condition;

        return $this;
    }

    public function visible(bool | Closure $condition = true): static
    {
        $this->isVisible = $condition;

        return $this;
    }

    public function isHidden(): bool
    {
        if ($this->evaluate($this->isHidden)) {
            return true;
        }

        return ! $this->evaluate($this->isVisible);
    }

    public function isVisible(): bool
    {
        return ! $this->isHidden();
    }
}

<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Board\Concerns;

use Closure;

/**
 * Minimal board filters - just stores filter definitions.
 */
trait HasBoardFilters
{
    protected array $boardFilters = [];

    public function filters(array | Closure $filters): static
    {
        $this->boardFilters = $this->evaluate($filters);

        return $this;
    }

    public function getBoardFilters(): array
    {
        return $this->boardFilters;
    }

    public function hasBoardFilters(): bool
    {
        return ! empty($this->boardFilters);
    }
}

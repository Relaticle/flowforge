<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Filament\Support\Enums\Width;
use Filament\Tables\Table\Concerns\HasFilters;

/**
 * Minimal board filters - just stores filter definitions.
 */
trait HasBoardFilters
{
    use HasFilters;

    protected array $boardFilters = [];

    protected Width | string | Closure | null $filtersFormWidth = null;

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

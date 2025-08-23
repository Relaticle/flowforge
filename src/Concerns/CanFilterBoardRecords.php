<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Integrates Filament table filters into Flowforge boards.
 * Provides seamless filtering capabilities that feel native to Flowforge.
 */
trait CanFilterBoardRecords
{
    protected array $boardFilters = [];

    protected array $filterData = [];

    protected bool $isFilterable = false;

    protected string | Closure | null $filterFormMaxWidth = null;

    protected bool $deferFilters = true;

    /**
     * Configure board filters using Filament's table filter system.
     */
    public function filters(array | Closure $filters): static
    {
        $this->boardFilters = $this->evaluate($filters);
        $this->isFilterable = true;

        return $this;
    }

    /**
     * Check if the board has filters configured.
     */
    public function isFilterable(): bool
    {
        return $this->isFilterable && ! empty($this->boardFilters);
    }

    /**
     * Get configured board filters.
     */
    public function getBoardFilters(): array
    {
        return $this->boardFilters;
    }

    /**
     * Set filter form maximum width.
     */
    public function filterFormMaxWidth(string | Closure $maxWidth): static
    {
        $this->filterFormMaxWidth = $maxWidth;

        return $this;
    }

    /**
     * Get filter form maximum width.
     */
    public function getFilterFormMaxWidth(): ?string
    {
        return $this->evaluate($this->filterFormMaxWidth);
    }

    /**
     * Enable or disable deferred filters (default: true).
     * When true, filters require explicit apply action.
     */
    public function deferFilters(bool $condition = true): static
    {
        $this->deferFilters = $condition;

        return $this;
    }

    /**
     * Check if filters are deferred.
     */
    public function shouldDeferFilters(): bool
    {
        return $this->deferFilters;
    }

    /**
     * Apply filters to the board query.
     * Integrates with Flowforge's existing query system.
     */
    public function applyFiltersToQuery(Builder $query, array $filterData = []): Builder
    {
        $this->filterData = $filterData;

        foreach ($this->getBoardFilters() as $filter) {
            if (! $filter instanceof BaseFilter) {
                continue;
            }

            $filterState = $filterData[$filter->getName()] ?? [];

            if (empty($filterState)) {
                continue;
            }

            // Apply the filter's query modifications
            $query = $filter->apply($query, $filterState);
        }

        return $query;
    }

    /**
     * Get current filter data.
     */
    public function getFilterData(): array
    {
        return $this->filterData;
    }

    /**
     * Set filter data (used by Livewire components).
     */
    public function setFilterData(array $data): static
    {
        $this->filterData = $data;

        return $this;
    }

    /**
     * Reset all filters to their default state.
     */
    public function resetFilters(): static
    {
        $this->filterData = [];

        return $this;
    }

    /**
     * Get active filter indicators for display.
     */
    public function getActiveFilterIndicators(): array
    {
        $indicators = [];

        foreach ($this->getBoardFilters() as $filter) {
            if (! $filter instanceof BaseFilter) {
                continue;
            }

            $filterState = $this->filterData[$filter->getName()] ?? [];

            if (empty($filterState)) {
                continue;
            }

            // Get filter indicators if the filter supports them
            if (method_exists($filter, 'getIndicators')) {
                $filterIndicators = $filter->getIndicators($filterState);
                if (! empty($filterIndicators)) {
                    $indicators[$filter->getName()] = $filterIndicators;
                }
            }
        }

        return $indicators;
    }

    /**
     * Check if any filters are currently active.
     */
    public function hasActiveFilters(): bool
    {
        return ! empty($this->getActiveFilterIndicators());
    }
}

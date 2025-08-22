<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Components\FilterBridge;

/**
 * Integrates Filament table filtering into Flowforge board pages.
 * This trait extends existing Livewire components to support filtering.
 */
trait InteractsWithBoardFilters
{
    public array $boardFilterData = [];

    protected array $boardFilters = [];

    protected bool $boardFiltersEnabled = false;

    protected bool $deferBoardFilters = true;

    protected string | Closure | null $boardFilterFormMaxWidth = 'sm';

    /**
     * Configure board filters.
     */
    public function boardFilters(array | Closure $filters): static
    {
        $this->boardFilters = is_callable($filters) ? $filters() : $filters;
        $this->boardFiltersEnabled = true;

        return $this;
    }

    /**
     * Get configured board filters.
     */
    public function getBoardFilters(): array
    {
        return $this->boardFilters;
    }

    /**
     * Check if board filtering is enabled.
     */
    public function hasBoardFilters(): bool
    {
        return $this->boardFiltersEnabled && ! empty($this->boardFilters);
    }

    /**
     * Configure filter deferring behavior.
     */
    public function deferBoardFilters(bool $condition = true): static
    {
        $this->deferBoardFilters = $condition;

        return $this;
    }

    /**
     * Check if board filters are deferred.
     */
    public function shouldDeferBoardFilters(): bool
    {
        return $this->deferBoardFilters;
    }

    /**
     * Set board filter form maximum width.
     */
    public function boardFilterFormMaxWidth(string | Closure $maxWidth): static
    {
        $this->boardFilterFormMaxWidth = $maxWidth;

        return $this;
    }

    /**
     * Get board filter form maximum width.
     */
    public function getBoardFilterFormMaxWidth(): string
    {
        return is_callable($this->boardFilterFormMaxWidth) 
            ? ($this->boardFilterFormMaxWidth)() 
            : $this->boardFilterFormMaxWidth ?? 'sm';
    }

    /**
     * Apply board filters to the main query.
     * This integrates with Flowforge's existing query system.
     */
    public function applyBoardFiltersToQuery(Builder $query): Builder
    {
        if (! $this->hasBoardFilters()) {
            return $query;
        }

        foreach ($this->getBoardFilters() as $filter) {
            $filterName = $filter->getName();
            $filterState = $this->boardFilterData[$filterName] ?? [];

            if (empty($filterState)) {
                continue;
            }

            // Apply the filter's query modifications
            if (method_exists($filter, 'apply')) {
                $query = $filter->apply($query, $filterState);
            } elseif (method_exists($filter, 'query')) {
                $query = $filter->query($query, $filterState);
            }
        }

        return $query;
    }

    /**
     * Get the filter bridge component for rendering.
     */
    public function getBoardFilterBridge(): ?FilterBridge
    {
        if (! $this->hasBoardFilters()) {
            return null;
        }

        return FilterBridge::make($this)
            ->filters($this->getBoardFilters())
            ->filterData($this->boardFilterData)
            ->deferFilters($this->shouldDeferBoardFilters());
    }

    /**
     * Livewire action to apply board filters.
     */
    public function applyBoardFilters(): void
    {
        // Trigger board refresh with new filter data
        $this->resetBoardRecords();
        
        // Emit event for other components to react
        $this->dispatch('board-filters-applied', $this->boardFilterData);
    }

    /**
     * Livewire action to reset board filters.
     */
    public function resetBoardFilters(): void
    {
        $this->boardFilterData = [];
        $this->resetBoardRecords();
        
        // Emit event for other components to react
        $this->dispatch('board-filters-reset');
    }

    /**
     * Get active filter indicators for display.
     */
    public function getActiveBoardFilterIndicators(): array
    {
        if (! $this->hasBoardFilters()) {
            return [];
        }

        $indicators = [];

        foreach ($this->getBoardFilters() as $filter) {
            $filterName = $filter->getName();
            $filterState = $this->boardFilterData[$filterName] ?? [];

            if (empty($filterState)) {
                continue;
            }

            // Get filter indicators if the filter supports them
            if (method_exists($filter, 'getIndicators')) {
                $filterIndicators = $filter->getIndicators($filterState);
                if (! empty($filterIndicators)) {
                    $indicators = array_merge($indicators, $filterIndicators);
                }
            } elseif (method_exists($filter, 'indicateUsing')) {
                $filterIndicators = $filter->indicateUsing($filterState);
                if (! empty($filterIndicators)) {
                    $indicators = array_merge($indicators, $filterIndicators);
                }
            }
        }

        return $indicators;
    }

    /**
     * Check if any board filters are currently active.
     */
    public function hasActiveBoardFilters(): bool
    {
        return ! empty($this->getActiveBoardFilterIndicators()) || 
               ! empty(array_filter($this->boardFilterData));
    }

    /**
     * Override the base board records method to apply filters.
     * This method should be called by implementing classes.
     */
    protected function getBoardRecordsWithFilters(string $columnId): \Illuminate\Support\Collection
    {
        $query = $this->getBaseQueryForColumn($columnId);
        
        // Apply board filters if they exist
        if ($this->hasBoardFilters()) {
            $query = $this->applyBoardFiltersToQuery($query);
        }

        return $query->get();
    }

    /**
     * Reset board records (called when filters change).
     * Implementing classes should override this to refresh their data.
     */
    protected function resetBoardRecords(): void
    {
        // Base implementation - can be overridden by implementing classes
        $this->dispatch('$refresh');
    }

    /**
     * Get base query for a specific column.
     * Implementing classes should override this method.
     */
    protected function getBaseQueryForColumn(string $columnId): Builder
    {
        throw new \BadMethodCallException(
            'Classes using InteractsWithBoardFilters must implement getBaseQueryForColumn method'
        );
    }

    /**
     * Livewire listeners for filter-related events.
     */
    protected function getListeners(): array
    {
        return array_merge(parent::getListeners() ?? [], [
            'board-filters-applied' => '$refresh',
            'board-filters-reset' => '$refresh',
        ]);
    }
}
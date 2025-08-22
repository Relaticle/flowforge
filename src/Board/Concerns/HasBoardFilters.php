<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Board\Concerns;

use Closure;
use Filament\Schemas\Components\Group;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\BaseFilter;

/**
 * Add Filament table filters support to Flowforge boards using the same pattern as Filament tables.
 */
trait HasBoardFilters
{
    /**
     * @var array<string, BaseFilter>
     */
    protected array $boardFilters = [];

    protected FiltersLayout | Closure | null $filtersLayout = null;

    protected int | array | Closure | null $filtersFormColumns = null;

    protected bool | Closure $hasDeferredFilters = true;

    /**
     * Configure board filters using Filament's table filter components.
     * @param array<BaseFilter> $filters
     */
    public function filters(array | Closure $filters, FiltersLayout | string | Closure | null $layout = null): static
    {
        $this->boardFilters = [];
        $this->pushFilters($this->evaluate($filters));

        if ($layout) {
            $this->filtersLayout($layout);
        }

        return $this;
    }

    /**
     * @param array<BaseFilter> $filters
     */
    public function pushFilters(array $filters): static
    {
        foreach ($filters as $filter) {
            // Store filters without setting table reference yet
            // The Livewire component will handle the table/filter relationship
            $this->boardFilters[$filter->getName()] = $filter;
        }

        return $this;
    }

    public function deferFilters(bool | Closure $condition = true): static
    {
        $this->hasDeferredFilters = $condition;

        return $this;
    }

    public function filtersLayout(FiltersLayout | Closure | null $filtersLayout): static
    {
        $this->filtersLayout = $filtersLayout;

        return $this;
    }

    /**
     * @param int | array<string, int | null> | Closure $columns
     */
    public function filtersFormColumns(int | array | Closure | null $columns): static
    {
        $this->filtersFormColumns = $columns;

        return $this;
    }

    /**
     * Get configured board filters.
     * @return array<string, BaseFilter>
     */
    public function getBoardFilters(bool $withHidden = false): array
    {
        if ($withHidden) {
            return $this->boardFilters;
        }

        return array_filter(
            $this->boardFilters,
            fn (BaseFilter $filter): bool => $filter->isVisible(),
        );
    }

    public function getBoardFilter(string $name, bool $withHidden = false): ?BaseFilter
    {
        return $this->getBoardFilters($withHidden)[$name] ?? null;
    }

    /**
     * Check if board has filters configured.
     */
    public function hasBoardFilters(): bool
    {
        return ! empty($this->getBoardFilters());
    }

    public function hasDeferredFilters(): bool
    {
        return (bool) $this->evaluate($this->hasDeferredFilters);
    }

    public function getFiltersLayout(): FiltersLayout
    {
        return $this->evaluate($this->filtersLayout) ?? FiltersLayout::AboveContent;
    }

    /**
     * @return int | array<string, int | null>
     */
    public function getFiltersFormColumns(): int | array
    {
        return $this->evaluate($this->filtersFormColumns) ?? [
            'sm' => 2,
            'lg' => 3,
            'xl' => 4,
            '2xl' => 5,
        ];
    }

    /**
     * Create form schema for board filters (mirrors Filament's approach).
     * @return array<string, Group>
     */
    public function getBoardFiltersFormSchema(): array
    {
        $filters = [];

        foreach ($this->getBoardFilters() as $filterName => $filter) {
            $filters[$filterName] = Group::make()
                ->schema($filter->getSchemaComponents())
                ->statePath($filterName)
                ->key($filterName)
                ->columnSpan($filter->getColumnSpan())
                ->columnStart($filter->getColumnStart())
                ->columns($filter->getColumns());
        }

        return array_values($filters);
    }

    public function isBoardFilterable(): bool
    {
        return (bool) count($this->getBoardFilters());
    }

    public function getActiveBoardFiltersCount(): int
    {
        return array_reduce(
            $this->getBoardFilters(),
            fn (int $carry, BaseFilter $filter): int => $carry + $filter->getActiveCount(),
            0,
        );
    }

    public function isBoardFiltered(): bool
    {
        return $this->getActiveBoardFiltersCount() > 0;
    }
}
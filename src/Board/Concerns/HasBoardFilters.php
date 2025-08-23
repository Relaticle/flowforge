<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Board\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Group;
use Filament\Support\Enums\Size;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\View\TablesIconAlias;

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
        return $this->evaluate($this->filtersFormColumns) ?? 2; // Simple 2-column layout for dropdown
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
        $count = 0;
        
        foreach ($this->getBoardFilters() as $filter) {
            try {
                // Ensure filter has table reference before getting active count
                if (!isset($filter->table)) {
                    continue;
                }
                $count += $filter->getActiveCount();
            } catch (\Exception $e) {
                // Skip filters that can't provide count
                continue;
            }
        }
        
        return $count;
    }

    public function isBoardFiltered(): bool
    {
        return $this->getActiveBoardFiltersCount() > 0;
    }

    /**
     * Get the board filters trigger action (exactly like Filament tables).
     */
    public function getBoardFiltersTriggerAction(): ActionGroup
    {
        $livewire = $this->getLivewire();
        
        // Get active filter count safely
        $activeCount = 0;
        if (method_exists($livewire, 'hasActiveBoardFilters') && $livewire->hasActiveBoardFilters()) {
            $activeCount = count($livewire->getActiveBoardFilterIndicators());
        }

        // Skip table setup for now to avoid method errors
        // Filters will work without explicit table reference for basic functionality

        // Create the main filter action
        $filterAction = Action::make('openBoardFilters')
            ->label('Filters')
            ->modal()
            ->modalHeading('Filter Tasks')
            ->modalDescription('Apply filters to refine the tasks shown on your board.')
            ->modalSubmitActionLabel('Apply Filters')
            ->modalCancelActionLabel('Close')
            ->schema($this->getBoardFiltersFormSchema())
            ->fillForm(fn () => $livewire->boardDeferredFilters ?? $livewire->boardFilters ?? [])
            ->action(function (array $data) use ($livewire): void {
                // Update the deferred filters with form data
                $livewire->boardDeferredFilters = $data;
                $livewire->applyBoardFilters();
            })
            ->extraModalFooterActions([
                Action::make('resetBoardFilters')
                    ->label('Reset All')
                    ->color('gray')
                    ->action('resetBoardFiltersForm')
                    ->button(),
            ])
            ->livewire($livewire)
            ->authorize(true);

        // Return as ActionGroup with dropdown
        return ActionGroup::make([$filterAction])
            ->label('Filters')
            ->icon(FilamentIcon::resolve(TablesIconAlias::ACTIONS_FILTER) ?? Heroicon::Funnel)
            ->color('gray')
            ->badge($activeCount ?: null)
            ->badgeColor('primary')
            ->size(Size::Small)
            ->button();
    }

    /**
     * Get board filters apply action.
     */
    public function getBoardFiltersApplyAction(): Action
    {
        return Action::make('applyBoardFilters')
            ->label('Apply Filters')
            ->action('applyBoardFilters')
            ->visible($this->hasDeferredFilters())
            ->authorize(true)
            ->button();
    }
}
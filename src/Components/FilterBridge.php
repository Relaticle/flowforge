<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Components;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Support\Components\ViewComponent;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Contracts\View\View;
use Relaticle\Flowforge\Concerns\BelongsToLivewire;
use Relaticle\Flowforge\Contracts\HasBoard;

/**
 * Bridge component that renders Filament table filters in Flowforge's native style.
 * Provides seamless integration between Filament's filtering system and Flowforge's UI.
 */
class FilterBridge extends ViewComponent
{
    use BelongsToLivewire;

    protected string $view = 'flowforge::components.filter-bridge';

    protected string $viewIdentifier = 'filterBridge';

    protected string $evaluationIdentifier = 'filterBridge';

    protected array $filters = [];

    protected array $filterData = [];

    protected string $formStatePath = 'boardFilterData';

    protected bool $deferFilters = true;

    final public function __construct(HasBoard $livewire)
    {
        $this->livewire($livewire);
    }

    public static function make(HasBoard $livewire): static
    {
        return app(static::class, ['livewire' => $livewire]);
    }

    /**
     * Set the filters to be rendered.
     */
    public function filters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Get the configured filters.
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Set whether filters should be deferred.
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
     * Set filter data.
     */
    public function filterData(array $data): static
    {
        $this->filterData = $data;

        return $this;
    }

    /**
     * Get current filter data.
     */
    public function getFilterData(): array
    {
        return $this->filterData;
    }

    /**
     * Get the form state path for Livewire.
     */
    public function getFormStatePath(): string
    {
        return $this->formStatePath;
    }

    /**
     * Build the filter form schema using Filament components.
     */
    public function getFilterFormSchema(): array
    {
        $schema = [];

        foreach ($this->getFilters() as $filter) {
            if (! $filter instanceof BaseFilter) {
                continue;
            }

            // Get the filter's form schema
            $filterSchema = $filter->getFormSchema();

            if (! empty($filterSchema)) {
                // Wrap each filter in a Section for better organization
                $schema[] = Section::make($filter->getLabel())
                    ->description($filter->getDescription())
                    ->schema($filterSchema)
                    ->collapsible()
                    ->persistCollapsed()
                    ->columnSpanFull();
            }
        }

        return $schema;
    }

    /**
     * Get view data for the filter bridge template.
     */
    public function getViewData(): array
    {
        return [
            'filters' => $this->getFilters(),
            'filterData' => $this->getFilterData(),
            'formStatePath' => $this->getFormStatePath(),
            'deferFilters' => $this->shouldDeferFilters(),
            'formSchema' => $this->getFilterFormSchema(),
            'hasFilters' => ! empty($this->getFilters()),
        ];
    }

    /**
     * Get active filter indicators for display.
     */
    public function getActiveFilterIndicators(): array
    {
        $indicators = [];

        foreach ($this->getFilters() as $filter) {
            if (! $filter instanceof BaseFilter) {
                continue;
            }

            $filterState = $this->filterData[$filter->getName()] ?? [];

            if (empty($filterState)) {
                continue;
            }

            // Generate indicators for this filter
            if (method_exists($filter, 'indicateUsing') && $filter->hasIndicators()) {
                $filterIndicators = $filter->getIndicators($filterState);
                if (! empty($filterIndicators)) {
                    $indicators = array_merge($indicators, $filterIndicators);
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

    /**
     * Generate a form action to apply filters (for deferred mode).
     */
    public function getApplyFiltersAction(): array
    {
        return [
            'action' => 'applyBoardFilters',
            'label' => 'Apply Filters',
            'color' => 'primary',
            'size' => 'sm',
        ];
    }

    /**
     * Generate a form action to reset filters.
     */
    public function getResetFiltersAction(): array
    {
        return [
            'action' => 'resetBoardFilters',
            'label' => 'Reset',
            'color' => 'gray',
            'size' => 'sm',
        ];
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'livewire' => [$this->getLivewire()],
            'filters' => [$this->getFilters()],
            'filterData' => [$this->getFilterData()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}

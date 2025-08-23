<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Filament\Schemas\Schema;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * Livewire concern for handling board filters (mirrors Filament's HasFilters trait).
 *
 * @property-read Schema $boardFiltersForm
 */
trait InteractWithBoardFilters
{
    /**
     * @var array<string, mixed>
     */
    public array $boardFilters = [];

    /**
     * @var array<string, mixed>
     */
    public array $boardDeferredFilters = [];

    /**
     * Mount the InteractWithBoardFilters trait.
     */
    public function mountInteractWithBoardFilters(): void
    {
        $this->initializeBoardFilterState();
    }

    /**
     * Boot the InteractWithBoardFilters trait.
     */
    public function bootedInteractWithBoardFilters(): void
    {
        // Ensure filter state is properly initialized
        if (!is_array($this->boardFilters)) {
            $this->boardFilters = [];
        }
        if (!is_array($this->boardDeferredFilters)) {
            $this->boardDeferredFilters = [];
        }

        // Initialize filter state structure based on filter types
        $this->initializeBoardFilterState();
    }

    /**
     * Initialize the board filter state with the correct nested structure
     * that each filter type expects (e.g., SelectFilter uses 'value'/'values').
     */
    protected function initializeBoardFilterState(): void
    {
        foreach ($this->getBoard()->getBoardFilters() as $filterName => $filter) {
            // Initialize filter state arrays only if they don't exist
            if (!isset($this->boardFilters[$filterName])) {
                $this->boardFilters[$filterName] = [];
            }
            if (!isset($this->boardDeferredFilters[$filterName])) {
                $this->boardDeferredFilters[$filterName] = [];
            }

            // Handle different filter types with their specific property names
            if ($filter instanceof \Filament\Tables\Filters\SelectFilter) {
                // SelectFilter uses 'value' for single, 'values' for multiple
                $propertyName = $filter->isMultiple() ? 'values' : 'value';
                $defaultValue = $filter->isMultiple() ? [] : null;

                // Only initialize if property doesn't exist (preserve existing values)
                if (!isset($this->boardFilters[$filterName][$propertyName])) {
                    $this->boardFilters[$filterName][$propertyName] = $defaultValue;
                }
                if (!isset($this->boardDeferredFilters[$filterName][$propertyName])) {
                    $this->boardDeferredFilters[$filterName][$propertyName] = $defaultValue;
                }
            } elseif ($filter instanceof \Filament\Tables\Filters\Filter) {
                // Regular Filter with custom schema - initialize based on schema
                $schemaComponents = $filter->getSchemaComponents();

                foreach ($schemaComponents as $component) {
                    $componentName = $component->getName();
                    $defaultValue = null; // Safe default

                    // Only initialize if property doesn't exist (preserve existing values)
                    if (!isset($this->boardFilters[$filterName][$componentName])) {
                        $this->boardFilters[$filterName][$componentName] = $defaultValue;
                    }
                    if (!isset($this->boardDeferredFilters[$filterName][$componentName])) {
                        $this->boardDeferredFilters[$filterName][$componentName] = $defaultValue;
                    }
                }
            } else {
                // Other filter types - use 'isActive' for checkbox-like filters
                if (!isset($this->boardFilters[$filterName]['isActive'])) {
                    $this->boardFilters[$filterName]['isActive'] = false;
                }
                if (!isset($this->boardDeferredFilters[$filterName]['isActive'])) {
                    $this->boardDeferredFilters[$filterName]['isActive'] = false;
                }
            }
        }
    }

    public function getBoardFiltersForm(): Schema
    {
        if ((! $this->isCachingSchemas) && $this->hasCachedSchema('boardFiltersForm')) {
            return $this->getSchema('boardFiltersForm');
        }

        $board = $this->getBoard();

        // Create table instance once and reuse
        $tableInstance = $this->makeFilamentTable();

        // Set up filters to work with this Livewire component
        foreach ($board->getBoardFilters() as $filter) {
            $filter->table($tableInstance);
        }

        return $this->makeSchema()
            ->columns($board->getFiltersFormColumns())
            ->model($board->getQuery()?->getModel())
            ->schema($board->getBoardFiltersFormSchema())
            ->when(
                $board->hasDeferredFilters(),
                fn (Schema $schema) => $schema
                    ->statePath('boardDeferredFilters')
                    ->partiallyRender(),
                fn (Schema $schema) => $schema
                    ->statePath('boardFilters')
                    ->live(),
            );
    }

    /**
     * Create a minimal Table object for filter compatibility.
     * Since BoardPage doesn't implement HasTable, we create a simple wrapper.
     */
    protected function makeFilamentTable(): \Filament\Tables\Table
    {
        // Create a simple table wrapper that provides the minimal interface needed for filters
        $tableWrapper = new class($this) implements \Filament\Tables\Contracts\HasTable {
            public function __construct(private $livewire) {}

            public function getTableFilterState(string $name): ?array
            {
                return $this->livewire->getBoardFilterState($name);
            }

            public function parseTableFilterName(string $name): string
            {
                return $this->livewire->parseBoardFilterName($name);
            }

            // Minimal implementations for other required methods
            public function callTableColumnAction(string $name, string $recordKey): mixed { return null; }
            public function deselectAllTableRecords(): void {}
            public function getActiveTableLocale(): ?string { return null; }
            public function getAllSelectableTableRecordKeys(): array { return []; }
            public function getAllTableRecordsCount(): int { return 0; }
            public function getAllSelectableTableRecordsCount(): int { return 0; }
            public function getSelectedTableRecords(bool $shouldFetchSelectedRecords = true, ?int $chunkSize = null): \Illuminate\Support\Collection { return collect(); }
            public function getSelectedTableRecordsQuery(bool $shouldFetchSelectedRecords = true, ?int $chunkSize = null): \Illuminate\Database\Eloquent\Builder { throw new \Exception('Not implemented'); }
            public function getTableGrouping(): ?\Filament\Tables\Grouping\Group { return null; }
            public function getMountedTableAction(): ?\Filament\Actions\Action { return null; }
            public function getMountedTableActionForm(): ?\Filament\Schemas\Schema { return null; }
            public function getMountedTableActionRecord(): ?\Illuminate\Database\Eloquent\Model { return null; }
            public function getMountedTableBulkAction(): ?\Filament\Actions\Action { return null; }
            public function getMountedTableBulkActionForm(): ?\Filament\Schemas\Schema { return null; }
            public function getTable(): \Filament\Tables\Table { throw new \Exception('Not implemented'); }
            public function getTableFiltersForm(): \Filament\Schemas\Schema { return $this->livewire->getBoardFiltersForm(); }
            public function getTableRecords(): \Illuminate\Support\Collection { return collect(); }
            public function getTableRecordsPerPage(): int|string|null { return null; }
            public function getTablePage(): int { return 1; }
            public function getTableSortColumn(): ?string { return null; }
            public function getTableSortDirection(): ?string { return null; }
            public function getAllTableSummaryQuery(): ?\Illuminate\Database\Eloquent\Builder { return null; }
            public function getPageTableSummaryQuery(): ?\Illuminate\Database\Eloquent\Builder { return null; }
            public function isTableColumnToggledHidden(string $name): bool { return false; }
            public function getTableRecord(?string $key): \Illuminate\Database\Eloquent\Model|array|null { return null; }
            public function getTableRecordKey(\Illuminate\Database\Eloquent\Model|array $record): string { return ''; }
            public function toggleTableReordering(): void {}
            public function isTableReordering(): bool { return false; }
            public function isTableLoaded(): bool { return true; }
            public function hasTableSearch(): bool { return false; }
            public function resetTableSearch(): void {}
            public function resetTableColumnSearch(string $column): void {}
            public function getTableSearchIndicator(): \Filament\Tables\Filters\Indicator { return new \Filament\Tables\Filters\Indicator(''); }
            public function getTableColumnSearchIndicators(): array { return []; }
            public function getFilteredTableQuery(): ?\Illuminate\Database\Eloquent\Builder { return null; }
            public function getFilteredSortedTableQuery(): ?\Illuminate\Database\Eloquent\Builder { return null; }
            public function getTableQueryForExport(): \Illuminate\Database\Eloquent\Builder { throw new \Exception('Not implemented'); }
            public function makeFilamentTranslatableContentDriver(): ?\Filament\Support\Contracts\TranslatableContentDriver { return null; }
            public function callMountedTableAction(array $arguments = []): mixed { return null; }
            public function mountTableAction(string $name, ?string $record = null, array $arguments = []): mixed { return null; }
            public function replaceMountedTableAction(string $name, ?string $record = null, array $arguments = []): void {}
            public function mountTableBulkAction(string $name, ?array $selectedRecords = null): mixed { return null; }
            public function replaceMountedTableBulkAction(string $name, ?array $selectedRecords = null): void {}
        };

        return \Filament\Tables\Table::make($tableWrapper)
            ->query($this->getBoard()->getQuery())
            ->deferFilters($this->getBoard()->hasDeferredFilters());
    }

    public function updatedBoardFilters(): void
    {
        if ($this->getBoard()->hasDeferredFilters()) {
            $this->boardDeferredFilters = $this->boardFilters;
        }

        $this->handleBoardFilterUpdates();
    }

    protected function handleBoardFilterUpdates(): void
    {
        // Refresh the board data when filters change
        $this->dispatch('$refresh');
    }

    public function removeBoardFilter(string $filterName, ?string $field = null, bool $isRemovingAllFilters = false): void
    {
        $filter = $this->getBoard()->getBoardFilter($filterName);
        $filterResetState = $filter->getResetState();

        $filterFormGroup = $this->getBoardFiltersForm()->getComponent($filterName);

        $filterFields = $filterFormGroup?->getChildSchema()->getFlatFields();

        if (filled($field) && array_key_exists($field, $filterFields)) {
            $filterFields = [$field => $filterFields[$field]];
        }

        foreach ($filterFields as $fieldName => $field) {
            $state = $field->getState();

            $field->state($filterResetState[$fieldName] ?? match (true) {
                is_array($state) => [],
                is_bool($state) => $field->hasNullableBooleanState() ? null : false,
                default => null,
            });
        }

        if ($isRemovingAllFilters) {
            return;
        }

        if ($this->getBoard()->hasDeferredFilters()) {
            $this->applyBoardFilters();
            return;
        }

        $this->handleBoardFilterUpdates();
    }

    public function removeBoardFilters(): void
    {
        $filters = $this->getBoard()->getBoardFilters();

        foreach ($filters as $filterName => $filter) {
            $this->removeBoardFilter(
                $filterName,
                isRemovingAllFilters: true,
            );
        }

        if ($this->getBoard()->hasDeferredFilters()) {
            $this->applyBoardFilters();
            return;
        }

        $this->handleBoardFilterUpdates();
    }

    public function resetBoardFiltersForm(): void
    {
        $this->getBoardFiltersForm()->fill();

        if ($this->getBoard()->hasDeferredFilters()) {
            $this->applyBoardFilters();
            return;
        }

        $this->handleBoardFilterUpdates();
    }

    public function applyBoardFilters(): void
    {
        $this->boardFilters = $this->boardDeferredFilters;
        $this->handleBoardFilterUpdates();
    }

    /**
     * Apply filters to board query (used by board implementations).
     */
    public function applyFiltersToBoardQuery(Builder $query): Builder
    {
        foreach ($this->getBoard()->getBoardFilters() as $filter) {
            $filterState = $this->getBoardFilterState($filter->getName()) ?? [];
            $filter->applyToBaseQuery($query, $filterState);
        }

        return $query->where(function (Builder $query): void {
            foreach ($this->getBoard()->getBoardFilters() as $filter) {
                $filterState = $this->getBoardFilterState($filter->getName()) ?? [];
                $filter->apply($query, $filterState);
            }
        });
    }

    public function getBoardFilterState(string $name): ?array
    {
        $parsedName = $this->parseBoardFilterName($name);

        // Check both deferred and active filters
        $state = Arr::get($this->boardFilters, $parsedName) ?? Arr::get($this->boardDeferredFilters, $parsedName);

        return $state;
    }

    public function parseBoardFilterName(string $name): string
    {
        if (! class_exists($name)) {
            return $name;
        }

        if (! is_subclass_of($name, BaseFilter::class)) {
            return $name;
        }

        return $name::getDefaultName();
    }

    /**
     * Get active board filter indicators for display.
     */
    public function getActiveBoardFilterIndicators(): array
    {
        $indicators = [];
        $board = $this->getBoard();

        // Create table instance to ensure filters are properly initialized
        $tableInstance = $this->makeFilamentTable();

        foreach ($board->getBoardFilters() as $filter) {
            // Ensure filter has table reference for indicators
            $filter->table($tableInstance);

            $filterState = $this->getBoardFilterState($filter->getName()) ?? [];

            if (empty($filterState)) {
                continue;
            }

            try {
                $filterIndicators = $filter->getIndicators($filterState);
                if (! empty($filterIndicators)) {
                    $indicators = array_merge($indicators, $filterIndicators);
                }
            } catch (\Exception $e) {
                // Skip indicators that fail - this ensures robustness
                continue;
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
               ! empty(array_filter($this->boardFilters ?? []));
    }
}

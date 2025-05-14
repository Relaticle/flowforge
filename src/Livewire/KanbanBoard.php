<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Relaticle\Flowforge\Config\KanbanConfig;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;
use Relaticle\Flowforge\Enums\KanbanColor;
use Relaticle\Flowforge\Filament\Pages\KanbanBoardPage;

class KanbanBoard extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithPagination;

    /**
     * The name of the kanban board page class.
     */
    public KanbanBoardPage | string $pageClass;

    /**
     * The Kanban board adapter.
     */
    #[Locked]
    public KanbanAdapterInterface $adapter;

    /**
     * The Kanban board configuration from the adapter.
     */
    public KanbanConfig $config;

    /**
     * The columns data for the Kanban board.
     */
    public array $columns = [];

    /**
     * Column card limits.
     *
     * @var array<string, int>
     */
    public array $columnCardLimits = [];

    /**
     * Cards by column.
     *
     * @var array<string, array>
     */
    public array $columnCards = [];

    /**
     * The active column for modal operations.
     */
    public ?string $currentColumn = null;

    /**
     * The active card for modal operations.
     */
    public string | int | null $currentRecord = null;

    /**
     * Search query for filtering cards.
     */
    public ?string $search = null;

    /**
     * Searchable fields.
     *
     * @var array<int, string>
     */
    public array $searchable = [];

    /**
     * Active filters for the board.
     *
     * @var array
     */
    public array $filters = [];

    /**
     * Number of cards to load when clicking "load more".
     */
    public int $cardsIncrement;

    /**
     * Initialize the Kanban board.
     *
     * @param  KanbanAdapterInterface  $adapter  The Kanban adapter
     * @param  int|null  $initialCardsCount  The initial number of cards to load per column
     * @param  int|null  $cardsIncrement  The number of cards to load on "load more"
     * @param  array<int, string>  $searchable  The searchable fields
     */
    public function mount(
        KanbanAdapterInterface $adapter,
        ?int $initialCardsCount = null,
        ?int $cardsIncrement = null,
        array $searchable = []
    ): void {
        $this->adapter = $adapter;
        $this->searchable = $searchable;
        $this->config = $this->adapter->getConfig();

        // Set default limits
        $initialCardsCount = $initialCardsCount ?? 50;
        $this->cardsIncrement = $cardsIncrement ?? 10;

        // Initialize columns
        $this->columns = collect($this->config->getColumnValues())
            ->map(fn ($label, $value) => [
                'id' => $value,
                'label' => $label,
                'color' => $this->resolveColumnColors()[$value] ?? null,
                'items' => [],
                'total' => 0,
            ])
            ->toArray();

        // Set initial card limits
        foreach ($this->columns as $column) {
            $this->columnCardLimits[$column['id']] = $initialCardsCount;
        }

        // Load initial data
        $this->refreshBoard();
    }

    /**
     * Resolve column colors from adapter config or use defaults.
     */
    protected function resolveColumnColors(): array
    {
        $adapterColors = $this->adapter->getConfig()->getColumnColors();

        if (is_array($adapterColors)) {
            return $adapterColors;
        }

        if ($adapterColors === null) {
            return [];
        }

        // Use default colors if none provided
        $defaultColors = [
            KanbanColor::GRAY->value,
            KanbanColor::RED->value,
            KanbanColor::ORANGE->value,
            KanbanColor::AMBER->value,
            KanbanColor::YELLOW->value,
            KanbanColor::LIME->value,
            KanbanColor::GREEN->value,
            KanbanColor::EMERALD->value,
            KanbanColor::TEAL->value,
            KanbanColor::CYAN->value,
            KanbanColor::SKY->value,
            KanbanColor::BLUE->value,
            KanbanColor::INDIGO->value,
            KanbanColor::VIOLET->value,
            KanbanColor::PURPLE->value,
            KanbanColor::FUCHSIA->value,
            KanbanColor::PINK->value,
            KanbanColor::ROSE->value,
        ];

        $colors = [];
        $columnKeys = array_keys($this->adapter->getConfig()->getColumnValues());

        foreach ($columnKeys as $index => $key) {
            $colorIndex = $index % count($defaultColors);
            $colors[$key] = $defaultColors[$colorIndex];
        }

        return $colors;
    }

    public function createAction(): ?Action
    {
        $boardPage = app($this->pageClass);

        if (! method_exists($boardPage, 'createAction')) {
            return null;
        }

        $action = Action::make('create')
            ->model(function (Action $action, array $arguments) {
                return app($this->pageClass)->getSubject()->getModel()::class;
            })
            ->action(function (Action $action, array $arguments) {
                $record = app($this->pageClass)->getSubject()->getModel();
                $record->fill([
                    ...$action->getFormData(),
                    $this->config->getColumnField() => $arguments['column'],
                ]);

                $record->save();
            })
            ->after(function (Action $action, array $arguments) {
                $this->refreshBoard();
            });

        return $boardPage->createAction($action);
    }

    public function editAction(): ?Action
    {
        $boardPage = app($this->pageClass);

        if (! method_exists($boardPage, 'editAction')) {
            return null;
        }

        $action = Action::make('edit')
            ->model(function (Action $action, array $arguments) {
                return $this->adapter->getModelById($arguments['record'])::class;
            })
            ->record(function (array $arguments) {
                return $this->adapter->getModelById($arguments['record']);
            })
            ->fillForm(function (Action $action, array $arguments) {
                $record = $this->adapter->getModelById($arguments['record']);

                return $record->toArray();
            })
            ->action(function (Action $action, array $arguments) {
                $record = $this->adapter->getModelById($arguments['record']);
                $record->fill($action->getFormData());
                $record->save();

                Notification::make()
                    ->title(__(':Record updated successfully', ['record' => $this->config->getSingularCardLabel()]))
                    ->success()
                    ->send();
            })
            ->after(function (Action $action, array $arguments) {
                $this->refreshBoard();

                $this->dispatch('kanban-record-updated', [
                    'record' => $this->adapter->getModelById($arguments['record']),
                ]);
            });

        return $boardPage->editAction($action);
    }

    /**
     * Apply a filter to the board.
     *
     * @param  string  $field  The field to filter by
     * @param  mixed  $value  The filter value
     */
    public function applyFilter(string $field, $value): void
    {
        // Set the filter value
        $this->filters[$field] = $value;
        
        // Reset card limits to ensure consistent view
        foreach ($this->columns as $columnId => $column) {
            $this->columnCardLimits[$columnId] = 10; // Reset to default limit
        }
        
        // Refresh the board with the new filter
        $this->refreshBoard();
    }

    /**
     * Remove a specific filter from the board.
     *
     * @param  string  $field  The field to remove filtering for
     */
    public function removeFilter(string $field): void
    {
        if (isset($this->filters[$field])) {
            unset($this->filters[$field]);
            $this->refreshBoard();
        }
    }

    /**
     * Reset all applied filters.
     */
    public function resetFilters(): void
    {
        $this->filters = [];
        $this->refreshBoard();
    }
    
    /**
     * Get available options for a filter field.
     *
     * @param  string  $field  The filter field name
     * @return array  The available options
     */
    public function getFilterOptions(string $field): array
    {
        $fieldConfig = $this->config->getFilterableFields()[$field] ?? null;
        
        if (!$fieldConfig) {
            return [];
        }
        
        // If options are pre-defined in the config, use those
        if (isset($fieldConfig['options']) && is_array($fieldConfig['options'])) {
            return $fieldConfig['options'];
        }
        
        // Otherwise, try to load them dynamically if a source is defined
        if (isset($fieldConfig['options_source'])) {
            // This could be a method that loads options from a related model
            $sourceMethod = $fieldConfig['options_source'];
            
            if (method_exists($this->adapter, $sourceMethod)) {
                return $this->adapter->$sourceMethod();
            }
        }
        
        return [];
    }

    /**
     * Format a filter value for display in the UI.
     *
     * @param  string  $field  The filter field name
     * @param  mixed  $value  The filter value
     * @return string  The formatted value
     */
    public function formatFilterValue(string $field, $value): string
    {
        $fieldConfig = $this->config->getFilterableFields()[$field] ?? null;
        
        if (!$fieldConfig) {
            return (string) $value;
        }
        
        // If it's a select field, try to get the label for the value
        if ($fieldConfig['type'] === 'select' && isset($fieldConfig['options'][$value])) {
            return $fieldConfig['options'][$value];
        }
        
        return (string) $value;
    }

    /**
     * Refresh all board data.
     */
    public function refreshBoard(): void
    {
        $this->loadColumnsData();
    }

    /**
     * Load data for all columns.
     */
    protected function loadColumnsData(): void
    {
        foreach ($this->columns as $columnId => $column) {
            $limit = $this->columnCardLimits[$columnId] ?? 10;

            // Use filtered method if filters are active
            if (count($this->filters) > 0) {
                $items = $this->adapter->getFilteredItemsForColumn($columnId, $limit, $this->filters);
                $this->columnCards[$columnId] = $this->formatItems($items);
                
                // Get the filtered total count
                $this->columns[$columnId]['total'] = $this->adapter->getFilteredColumnItemsCount($columnId, $this->filters);
            } else {
                // Use standard methods if no filters are active
                $items = $this->adapter->getItemsForColumn($columnId, $limit);
                $this->columnCards[$columnId] = $this->formatItems($items);
                
                // Get the total count
                $this->columns[$columnId]['total'] = $this->adapter->getColumnItemsCount($columnId);
            }

            // Ensure that items are stored in columns data
            $this->columns[$columnId]['items'] = $this->columnCards[$columnId];
        }
    }

    /**
     * Get items for a specific column.
     *
     * @param  string|int  $columnId  The column ID
     * @return array The formatted items
     */
    public function getItemsForColumn(string | int $columnId): array
    {
        return $this->columnCards[$columnId] ?? [];
    }

    /**
     * Get the total count of items for a specific column.
     *
     * @param  string|int  $columnId  The column ID
     * @return int The total count
     */
    public function getColumnItemsCount(string | int $columnId): int
    {
        if (count($this->filters) > 0) {
            return $this->adapter->getFilteredColumnItemsCount($columnId, $this->filters);
        }
        
        return $this->adapter->getColumnItemsCount($columnId);
    }

    /**
     * Load more items for a column.
     *
     * @param  string  $columnId  The column ID
     * @param  int|null  $count  The number of items to load
     */
    public function loadMoreItems(string $columnId, ?int $count = null): void
    {
        $count = $count ?? $this->cardsIncrement;
        $currentLimit = $this->columnCardLimits[$columnId] ?? 10;
        $newLimit = $currentLimit + $count;

        $this->columnCardLimits[$columnId] = $newLimit;

        if (count($this->filters) > 0) {
            $items = $this->adapter->getFilteredItemsForColumn($columnId, $newLimit, $this->filters);
        } else {
            $items = $this->adapter->getItemsForColumn($columnId, $newLimit);
        }
        
        $this->columnCards[$columnId] = $this->formatItems($items);
        $this->refreshBoard();
    }

    /**
     * Format items for display.
     *
     * @param  Collection  $items  The items to format
     * @return array The formatted items
     */
    protected function formatItems(Collection $items): array
    {
        return $items->toArray();
    }

    /**
     * Update the order of cards in a column.
     *
     * @param  int|string  $columnId  The column ID
     * @param  array  $cardIds  The card IDs in their new order
     * @return bool Whether the operation was successful
     */
    public function updateRecordsOrderAndColumn(int | string $columnId, array $cardIds): bool
    {
        $success = $this->adapter->updateRecordsOrderAndColumn($columnId, $cardIds);

        if ($success) {
            $this->refreshBoard();
        }

        return $success;
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('flowforge::livewire.kanban-board');
    }
}

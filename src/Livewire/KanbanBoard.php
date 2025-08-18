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

class KanbanBoard extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithPagination;

    /**
     * The name of the kanban board page class.
     */
    public string $pageClass;

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
     * Boot the InteractsWithActions trait after component is hydrated.
     */
    public function bootedInteractsWithActions(): void
    {
        // Cache board actions for Filament's action system
        $this->cacheBoardActions();
    }

    /**
     * Cache board actions for Filament's InteractsWithActions trait.
     */
    protected function cacheBoardActions(): void
    {
        $boardPage = $this->getBoardPage();
        if (!$boardPage) {
            return;
        }

        // Cache column actions
        foreach ($boardPage->getBoard()->getColumnActions() as $action) {
            $this->cacheBoardAction($action);
        }

        // Cache record actions
        foreach ($boardPage->getBoard()->getRecordActions() as $action) {
            $this->cacheBoardAction($action);
        }
    }

    /**
     * Cache a board action for Filament's action system.
     */
    protected function cacheBoardAction(\Filament\Actions\Action | \Filament\Actions\ActionGroup $action): void
    {
        if ($action instanceof \Filament\Actions\ActionGroup) {
            // Cache all actions within the group using Filament's method
            foreach ($action->getFlatActions() as $flatAction) {
                $this->cacheAction($flatAction);
            }
        } elseif ($action instanceof \Filament\Actions\Action) {
            // Use Filament's built-in cacheAction for proper setup
            $this->cacheAction($action);
        }
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

    /**
     * Get the board page instance.
     */
    protected function getBoardPage(): ?\Relaticle\Flowforge\BoardPage
    {
        $boardPage = app($this->pageClass);

        return $boardPage instanceof \Relaticle\Flowforge\BoardPage ? $boardPage : null;
    }

    /**
     * Get processed column actions for a specific column.
     */
    public function getColumnActionsForColumn(string $columnId): array
    {
        $boardPage = $this->getBoardPage();
        if (!$boardPage) {
            return [];
        }

        $processedActions = [];

        foreach ($boardPage->getBoard()->getColumnActions() as $action) {
            try {
                // Skip record-based actions entirely to prevent null record errors
                if ($boardPage->isRecordBasedAction($action)) {
                    continue;
                }

                $actionClone = $action->getClone();

                // Set livewire context to this KanbanBoard component
                $actionClone->livewire($this);

                // Store column context for use in action callbacks
                $actionClone->arguments(['column' => $columnId]);

                // Handle ActionGroup differently
                if ($actionClone instanceof \Filament\Actions\ActionGroup) {
                    // Filter and set context for all actions within the group
                    $validGroupActions = [];
                    foreach ($actionClone->getFlatActions() as $flatAction) {
                        // Skip record-based actions in groups too
                        if ($boardPage->isRecordBasedAction($flatAction)) {
                            continue;
                        }

                        $flatAction->livewire($this);
                        $flatAction->arguments(['column' => $columnId]);

                        $validGroupActions[] = $flatAction;
                    }

                    // Only include the group if it has valid actions
                    if (!empty($validGroupActions)) {
                        $processedActions[] = $actionClone;
                    }
                } else {
                    // Handle individual actions
                    if (!$actionClone->isHidden()) {
                        $processedActions[] = $actionClone;
                    }
                }
            } catch (\Exception) {
                // Skip actions that can't be properly configured for column context
                continue;
            }
        }

        return $processedActions;
    }

    /**
     * Get processed record actions for a specific record.
     */
    public function getCardActionsForRecord(array $recordData): array
    {
        $boardPage = $this->getBoardPage();
        if (!$boardPage) {
            return [];
        }

        $processedActions = [];

        try {
            // Get the record model first
            $recordModel = $this->adapter->getModelById($recordData['id']);

            // If we can't find the record, return empty actions
            if (!$recordModel || !($recordModel instanceof \Illuminate\Database\Eloquent\Model)) {
                return [];
            }

            foreach ($boardPage->getBoard()->getRecordActions() as $action) {
                try {
                    $actionClone = $action->getClone();

                    // Set livewire context to this KanbanBoard component
                    $actionClone->livewire($this);

                    // Handle ActionGroup differently
                    if ($actionClone instanceof \Filament\Actions\ActionGroup) {
                        // Set context for all actions within the group
                        foreach ($actionClone->getFlatActions() as $flatAction) {
                            $flatAction->livewire($this);
                            if (method_exists($flatAction, 'record')) {
                                $flatAction->record($recordModel);
                            }
                            
                            // Add after hook to refresh board after any action
                            $flatAction->after(function () {
                                $this->refreshBoard();
                            });

                        }
                    } else {
                        // Set record context for individual actions
                        if (method_exists($actionClone, 'record')) {
                            $actionClone->record($recordModel);
                        }
                        
                        // Add after hook to refresh board after any action
                        $actionClone->after(function () {
                            $this->refreshBoard();
                        });
                    }

                    // Safely check if action is hidden
                    if (!$actionClone->isHidden()) {
                        $processedActions[] = $actionClone;
                    }
                } catch (\Exception $e) {
                    // Skip actions that can't be properly configured
                    continue;
                }
            }
        } catch (\Exception $e) {
            // If anything goes wrong, return empty actions to prevent errors
            return [];
        }

        return $processedActions;
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

            $items = $this->adapter->getItemsForColumn($columnId, $limit);
            $this->columnCards[$columnId] = $this->formatItems($items);

            // Ensure that items and total keys exist in columns data
            $this->columns[$columnId]['items'] = $this->columnCards[$columnId];

            // Get the total count
            $this->columns[$columnId]['total'] = $this->adapter->getColumnItemsCount($columnId);
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

        $items = $this->adapter->getItemsForColumn($columnId, $newLimit);
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
     * Get the default record for an action - this is how Filament injects records into action closures.
     * This method is called by Filament's action system when resolving record parameters.
     */
    public function getDefaultActionRecord(\Filament\Actions\Action $action): ?\Illuminate\Database\Eloquent\Model
    {
        // Get the current mounted action context
        $mountedActions = $this->mountedActions ?? [];
        
        if (empty($mountedActions)) {
            return null;
        }
        
        // Get the last (current) mounted action
        $currentMountedAction = end($mountedActions);
        
        // Extract recordKey from the context
        $recordKey = $currentMountedAction['context']['recordKey'] ?? null;
        
        if (!$recordKey) {
            return null;
        }
        
        // Resolve the record using our adapter
        return $this->adapter->getModelById($recordKey);
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('flowforge::livewire.kanban-board');
    }
}

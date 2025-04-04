<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Livewire;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Relaticle\Flowforge\Config\KanbanConfig;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;
use Relaticle\Flowforge\Enums\KanbanColor;

class KanbanBoard extends Component implements HasForms
{
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
     * Card data for form operations.
     */
    #[Validate]
    public array $recordData = [];

    /**
     * Number of cards to load when clicking "load more".
     */
    public int $cardsIncrement;

    public array $permissions = [];

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

        // Check permissions
        $this->permissions = [
            'canCreate' => Gate::check('create', $this->adapter->baseQuery->getModel()),
            'canDelete' => Gate::check('delete', $this->adapter->baseQuery->getModel()),
        ];

        // Set default limits
        $initialCardsCount = $initialCardsCount ?? 5;
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

        // Initialize forms
        $this->createRecordForm->fill();
        $this->editRecordForm->fill();

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

    /**
     * Create card form configuration.
     */
    public function createRecordForm(Form $form): Form
    {
        $form = $this->adapter->getCreateForm($form, $this->currentColumn);

        return $form->model($this->adapter->baseQuery->getModel())->statePath('recordData');
    }

    /**
     * Edit card form configuration.
     */
    public function editRecordForm(Form $form): Form
    {
        $form = $this->adapter->getEditForm($form);

        return $form->model($this->adapter->baseQuery->getModel())->statePath('recordData');
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
     * @param  string|int  $columnId  The column ID
     * @param  array  $cardIds  The card IDs in their new order
     * @return bool Whether the operation was successful
     */
    public function updateRecordsOrderAndColumn($columnId, $cardIds): bool
    {
        $success = $this->adapter->updateRecordsOrderAndColumn($columnId, $cardIds);

        if ($success) {
            $this->refreshBoard();
        }

        return $success;
    }

    /**
     * Open the create form modal.
     *
     * @param  string  $columnId  The column ID for the new card
     */
    public function openCreateForm(string $columnId): void
    {
        $this->currentColumn = $columnId;
        $this->recordData = [];

        // Set the base model without filling yet
        $this->createRecordForm->model($this->adapter->baseQuery->getModel());

        // Pre-set the column field
        $columnField = $this->config->getColumnField();
        $this->recordData[$columnField] = $columnId;
    }

    /**
     * Open the edit form modal.
     *
     * @param  string|int  $recordId  The card ID to edit
     * @param  string|int  $columnId  The column ID containing the card
     */
    public function openEditForm(string | int $recordId, string | int $columnId): void
    {
        $this->currentColumn = $columnId;
        $this->currentRecord = $recordId;

        $record = $this->adapter->getModelById($recordId);

        if (! $record) {
            Notification::make()
                ->title('Card not found')
                ->danger()
                ->send();

            return;
        }

        // Set recordData first
        $this->recordData = $record->toArray();

        // Clear form state to avoid reactivity issues
        $this->editRecordForm->fill([]);

        // Set the model on the form - this is crucial for relationships in nested components
        $this->editRecordForm->model($record);

        // Now fill the form, which will allow nested components to initialize with the model's relationships
        // Using the model's data directly instead of recordData to avoid reactivity loops
        $this->editRecordForm->fill($record->toArray());
    }

    /**
     * Create a new card.
     */
    public function createRecord(): void
    {
        if (! $this->permissions['canCreate']) {
            Notification::make()
                ->title('You do not have permission to create records')
                ->danger()
                ->send();

            return;
        }

        // Use form state to get data with validation applied
        $data = $this->createRecordForm->getState();

        // Ensure column field is set
        $columnField = $this->config->getColumnField();
        if (! isset($data[$columnField])) {
            $data[$columnField] = $this->currentColumn;
        }

        $card = $this->adapter->createRecord($data, $this->currentColumn);

        if ($card) {
            $this->refreshBoard();
            $this->resetCreateForm();

            $this->dispatch('kanban-record-created', [
                'record' => $card,
            ]);

            Notification::make()
                ->title('Card created')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Failed to create card')
                ->danger()
                ->send();
        }
    }

    /**
     * Reset the create form.
     */
    private function resetCreateForm(): void
    {
        $this->recordData = [];
        $this->currentColumn = null;

        // Just clear the form without chaining methods
        $this->createRecordForm->fill([]);
    }

    /**
     * Update an existing card.
     */
    public function updateRecord(): void
    {
        // Use form state to get data with any relationship handling applied
        $data = $this->editRecordForm->getState();
        $record = $this->adapter->getModelById($this->currentRecord);

        if (! $record) {
            Notification::make()
                ->title('Card not found')
                ->danger()
                ->send();

            return;
        }

        $success = $this->adapter->updateRecord($record, $data);

        if ($success) {
            $this->refreshBoard();
            $this->resetEditForm();

            $this->dispatch('kanban-record-updated', [
                'record' => $record,
            ]);

            Notification::make()
                ->title('Card updated')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Failed to update card')
                ->danger()
                ->send();
        }
    }

    /**
     * Reset the edit form.
     */
    public function resetEditForm(): void
    {
        $this->recordData = [];
        $this->currentRecord = null;

        // Just clear the form without chaining methods
        $this->editRecordForm->fill([]);
    }

    /**
     * Open the delete confirmation modal.
     *
     * @param  string|int  $cardId  The card ID to delete
     * @param  string  $columnId  The column ID containing the card
     */
    public function confirmDelete(string | int $cardId, string $columnId): void
    {
        $this->currentRecord = $cardId;
        $this->currentColumn = $columnId;
    }

    /**
     * Delete a card.
     */
    public function deleteRecord(): void
    {
        if (! $this->permissions['canDelete']) {
            Notification::make()
                ->title('You do not have permission to delete records')
                ->danger()
                ->send();

            return;
        }

        $record = $this->adapter->getModelById($this->currentRecord);

        if (! $record) {
            Notification::make()
                ->title('Record not found')
                ->danger()
                ->send();

            return;
        }

        $success = $this->adapter->deleteRecord($record);

        if ($success) {
            $this->refreshBoard();

            $this->dispatch('kanban-record-deleted', [
                'record' => $record,
            ]);

            Notification::make()
                ->title('Record deleted')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Failed to delete record')
                ->danger()
                ->send();
        }
    }

    /**
     * Define the forms that are available in this component.
     */
    protected function getForms(): array
    {
        return [
            'createRecordForm',
            'editRecordForm',
        ];
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('flowforge::livewire.kanban-board');
    }
}

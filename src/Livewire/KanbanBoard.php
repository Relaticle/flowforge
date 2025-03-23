<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Livewire;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;
use Relaticle\Flowforge\Enums\KanbanColor;

class KanbanBoard extends Component implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    /**
     * The Kanban board adapter.
     */
    #[Locked]
    public KanbanAdapterInterface $adapter;

    /**
     * The Kanban board configuration from the adapter.
     *
     * @var array
     */
    public array $config = [];

    /**
     * The columns data for the Kanban board.
     *
     * @var array
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
     *
     * @var string|null
     */
    public ?string $activeColumn = null;

    /**
     * The active card for modal operations.
     *
     * @var string|int|null
     */
    public string|int|null $activeCard = null;

    /**
     * Whether the create modal is open.
     */
    public bool $isCreateModalOpen = false;

    /**
     * Whether the edit modal is open.
     */
    public bool $isEditModalOpen = false;

    /**
     * Whether the delete confirm modal is open.
     */
    public bool $isDeleteConfirmOpen = false;

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
     *
     * @var array
     */
    #[Validate]
    public array $cardData = [];

    /**
     * Number of cards to load when clicking "load more".
     */
    public int $cardsIncrement;

    /**
     * Initialize the Kanban board.
     *
     * @param KanbanAdapterInterface $adapter The Kanban adapter
     * @param int|null $initialCardsCount The initial number of cards to load per column
     * @param int|null $cardsIncrement The number of cards to load on "load more"
     * @param array<int, string> $searchable The searchable fields
     */
    public function mount(
        KanbanAdapterInterface $adapter, 
        ?int $initialCardsCount = null, 
        ?int $cardsIncrement = null, 
        array $searchable = []
    ): void {
        $this->adapter = $adapter;
        $this->searchable = $searchable;

        // Extract config from adapter
        $this->config = [
            'columnField' => $adapter->getConfig()->getColumnField(),
            'columnValues' => $adapter->getConfig()->getColumnValues(),
            'titleField' => $adapter->getConfig()->getTitleField(),
            'descriptionField' => $adapter->getConfig()->getDescriptionField(),
            'cardAttributes' => $adapter->getConfig()->getCardAttributes(),
            'columnColors' => $this->resolveColumnColors(),
            'orderField' => $adapter->getConfig()->getOrderField(),
            'cardLabel' => $adapter->getConfig()->getCardLabel(),
            'pluralCardLabel' => $adapter->getConfig()->getPluralCardLabel(),
        ];

        // Set default limits
        $initialCardsCount = $initialCardsCount ?? 10;
        $this->cardsIncrement = $cardsIncrement ?? 10;

        // Initialize columns
        $this->columns = collect($this->config['columnValues'])
            ->map(fn ($label, $value) => [
                'id' => $value,
                'label' => $label,
                'color' => $this->config['columnColors'][$value] ?? null,
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
        
        if ($adapterColors !== null) {
            return $adapterColors;
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
     * Create form configuration.
     */
    public function createForm(Form $form): Form
    {
        return $this->adapter->getCreateForm($form, $this->activeColumn);
    }

    /**
     * Edit form configuration.
     */
    public function editForm(Form $form): Form
    {
        return $this->adapter->getEditForm($form);
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
        foreach ($this->columns as $column) {
            $columnId = $column['id'];
            $limit = $this->columnCardLimits[$columnId] ?? 10;
            
            $items = $this->adapter->getItemsForColumn($columnId, $limit);
            $this->columnCards[$columnId] = $this->formatItems($items);
        }
    }

    /**
     * Get items for a specific column.
     *
     * @param string|int $columnId The column ID
     * @return array The formatted items
     */
    public function getItemsForColumn(string|int $columnId): array
    {
        return $this->columnCards[$columnId] ?? [];
    }

    /**
     * Get the total count of items for a specific column.
     *
     * @param string|int $columnId The column ID
     * @return int The total count
     */
    public function getColumnItemsCount(string|int $columnId): int
    {
        return $this->adapter->getColumnItemsCount($columnId);
    }

    /**
     * Load more items for a column.
     *
     * @param string $columnId The column ID
     * @param int|null $count The number of items to load
     */
    public function loadMoreItems(string $columnId, ?int $count = null): void
    {
        $count = $count ?? $this->cardsIncrement;
        $currentLimit = $this->columnCardLimits[$columnId] ?? 10;
        $newLimit = $currentLimit + $count;
        
        $this->columnCardLimits[$columnId] = $newLimit;
        
        $items = $this->adapter->getItemsForColumn($columnId, $newLimit);
        $this->columnCards[$columnId] = $this->formatItems($items);
    }

    /**
     * Format items for display.
     *
     * @param Collection $items The items to format
     * @return array The formatted items
     */
    protected function formatItems(Collection $items): array
    {
        return $items->toArray();
    }

    /**
     * Update the order of cards in a column.
     *
     * @param string|int $columnId The column ID
     * @param array $cardIds The card IDs in their new order
     * @return bool Whether the operation was successful
     */
    public function reorderCardsInColumn($columnId, $cardIds): bool
    {
        $success = $this->adapter->reorderCardsInColumn($columnId, $cardIds);
        
        if ($success) {
            $this->refreshBoard();
        }
        
        return $success;
    }

    /**
     * Move a card between columns.
     *
     * @param string|int $cardId The card ID
     * @param string|int $fromColumn The source column ID
     * @param string|int $toColumn The target column ID
     * @param array $toColumnCardIds The card IDs in the target column in their new order
     * @return bool Whether the operation was successful
     */
    public function moveCardToColumn($cardId, $fromColumn, $toColumn, $toColumnCardIds): bool
    {
        $card = $this->adapter->getModelById($cardId);
        
        if (!$card) {
            return false;
        }
        
        $success = $this->adapter->moveCardToColumn($card, $toColumn);
        
        if ($success && $this->adapter->getConfig()->getOrderField() !== null) {
            $success = $this->adapter->reorderCardsInColumn($toColumn, $toColumnCardIds);
        }
        
        if ($success) {
            $this->refreshBoard();
        }
        
        return $success;
    }

    /**
     * Open the create form modal.
     *
     * @param string $columnId The column ID for the new card
     */
    public function openCreateForm(string $columnId): void
    {
        $this->activeColumn = $columnId;
        $this->resetCreateForm();
        
        // Pre-set the column field
        $columnField = $this->config['columnField'];
        $this->cardData[$columnField] = $columnId;
        
        // Apply any order field if needed
        $orderField = $this->config['orderField'];
        if ($orderField !== null) {
            $count = $this->getColumnItemsCount($columnId);
            $this->cardData[$orderField] = $count + 1;
        }
        
        $this->isCreateModalOpen = true;
    }

    /**
     * Open the edit form modal.
     *
     * @param string|int $cardId The card ID to edit
     * @param string $columnId The column ID containing the card
     */
    public function openEditForm(string|int $cardId, string $columnId): void
    {
        $this->activeColumn = $columnId;
        $this->activeCard = $cardId;
        
        $card = $this->adapter->getModelById($cardId);
        
        if (!$card) {
            Notification::make()
                ->title('Card not found')
                ->danger()
                ->send();
            
            return;
        }
        
        $this->resetEditForm();
        
        // Fill form with card data
        $this->form->fill($card->toArray());
        $this->cardData = $card->toArray();
        
        $this->isEditModalOpen = true;
    }

    /**
     * Create a new card.
     */
    public function createCard(): void
    {
        $data = $this->form->getState();
        
        // Ensure column field is set
        $columnField = $this->config['columnField'];
        if (!isset($data[$columnField])) {
            $data[$columnField] = $this->activeColumn;
        }
        
        $card = $this->adapter->createCard($data);
        
        if ($card) {
            $this->refreshBoard();
            $this->resetCreateForm();
            $this->isCreateModalOpen = false;
            
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
        $this->cardData = [];
        $this->createForm->fill();
    }

    /**
     * Update an existing card.
     */
    public function updateCard(): void
    {
        $data = $this->form->getState();
        $card = $this->adapter->getModelById($this->activeCard);
        
        if (!$card) {
            Notification::make()
                ->title('Card not found')
                ->danger()
                ->send();
            
            return;
        }
        
        $success = $this->adapter->updateCard($card, $data);
        
        if ($success) {
            $this->refreshBoard();
            $this->resetEditForm();
            $this->isEditModalOpen = false;
            
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
    private function resetEditForm(): void
    {
        $this->cardData = [];
        $this->editForm->fill();
    }

    /**
     * Open the delete confirmation modal.
     *
     * @param string|int $cardId The card ID to delete
     * @param string $columnId The column ID containing the card
     */
    public function confirmDelete(string|int $cardId, string $columnId): void
    {
        $this->activeCard = $cardId;
        $this->activeColumn = $columnId;
        $this->isDeleteConfirmOpen = true;
    }

    /**
     * Delete a card.
     */
    public function deleteCard(): void
    {
        $card = $this->adapter->getModelById($this->activeCard);
        
        if (!$card) {
            Notification::make()
                ->title('Card not found')
                ->danger()
                ->send();
            
            return;
        }
        
        $success = $this->adapter->deleteCard($card);
        
        if ($success) {
            $this->refreshBoard();
            $this->isDeleteConfirmOpen = false;
            
            Notification::make()
                ->title('Card deleted')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Failed to delete card')
                ->danger()
                ->send();
        }
    }

    /**
     * Get the forms for the component.
     */
    protected function getForms(): array
    {
        return [
            'createForm' => $this->makeForm()
                ->statePath('cardData')
                ->schema(fn (Form $form) => $this->createForm($form)->getSchema()),
            
            'editForm' => $this->makeForm()
                ->statePath('cardData')
                ->schema(fn (Form $form) => $this->editForm($form)->getSchema()),
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

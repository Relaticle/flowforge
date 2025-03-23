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
use Relaticle\Flowforge\Contracts\IKanbanAdapter;
use Relaticle\Flowforge\Enums\KanbanColor;

class KanbanBoard extends Component implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    /**
     * The Kanban board adapter.
     */
    #[Locked]
    public IKanbanAdapter $adapter;

    /**
     * The Kanban board configuration.
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
     * The currently active column for creating a card.
     *
     * @var string|null
     */
    public ?string $activeColumn = null;

    /**
     * The currently selected card ID for editing.
     *
     * @var int|string|null
     */
    public string|int|null $activeCardId = null;

    /**
     * Form data property for create form
     */
    #[Validate]
    public array $createFormData = [
        'title' => '',
        'description' => '',
    ];

    /**
     * Form data property for edit form
     */
    #[Validate]
    public array $editFormData = [
        'title' => '',
        'description' => '',
    ];

    /**
     * The number of cards to show per column.
     *
     * @var array
     */
    public array $perPage = [];

    /**
     * The columns currently being loaded.
     *
     * @var array
     */
    public array $loading = [];

    /**
     * The initial number of cards to show per column.
     *
     * @var int
     */
    public int $initialCardsCount = 10;

    /**
     * The number of cards to load when loading more.
     *
     * @var int
     */
    public int $cardsIncrement = 10;

    /**
     * Mount the component.
     *
     * @param IKanbanAdapter $adapter The Kanban board adapter
     * @param int|null $initialCardsCount Initial number of cards to show per column
     * @param int|null $cardsIncrement Number of cards to load when loading more
     * @return void
     */
    public function mount(IKanbanAdapter $adapter, ?int $initialCardsCount = null, ?int $cardsIncrement = null): void
    {
        $this->adapter = $adapter;

        // Set configuration values
        if ($initialCardsCount !== null) {
            $this->initialCardsCount = $initialCardsCount;
        } else {
            // Try to get from config, otherwise use default
            $this->initialCardsCount = config('flowforge.kanban.initial_cards_count', 20);
        }

        if ($cardsIncrement !== null) {
            $this->cardsIncrement = $cardsIncrement;
        } else {
            // Try to get from config, otherwise use default
            $this->cardsIncrement = config('flowforge.kanban.cards_increment', 10);
        }

        $this->config = [
            'statusField' => $adapter->getStatusField(),
            'statusValues' => $adapter->getStatusValues(),
            'statusColors' => $this->resolveStatusColors(),
            'titleAttribute' => $adapter->getTitleAttribute(),
            'descriptionAttribute' => $adapter->getDescriptionAttribute(),
            'cardAttributes' => $adapter->getCardAttributes(),
            'orderField' => $adapter->getOrderField(),
            'recordLabel' => method_exists($adapter, 'getRecordLabel') ? $adapter->getRecordLabel() : 'Card',
            'pluralRecordLabel' => method_exists($adapter, 'getPluralRecordLabel') ? $adapter->getPluralRecordLabel() : 'Cards',
            'initialCardsCount' => $this->initialCardsCount,
            'cardsIncrement' => $this->cardsIncrement,
        ];

        // Ensure the status field is populated in both form data arrays
        $statusField = $adapter->getStatusField();
        $this->createFormData[$statusField] = array_key_first($adapter->getStatusValues());
        $this->editFormData[$statusField] = array_key_first($adapter->getStatusValues());

        // Initialize card attributes in form data
        foreach ($adapter->getCardAttributes() as $attribute => $label) {
            $this->createFormData[$attribute] = '';
            $this->editFormData[$attribute] = '';
        }

        // Initialize per-column pagination with default value
        foreach (array_keys($adapter->getStatusValues()) as $columnId) {
            $this->perPage[$columnId] = $this->initialCardsCount;
        }

        $this->loadColumnsData();
    }

    /**
     * Resolve the status colors with fallback to default
     * These are used for status count badges
     *
     * @return array<string, string>
     */
    protected function resolveStatusColors(): array
    {
        // Get custom colors from adapter if available
        $customColors = method_exists($this->adapter, 'getStatusColors')
            ? $this->adapter->getStatusColors() ?? []
            : [];

        // Ensure all statuses have a color
        $statuses = array_keys($this->adapter->getStatusValues());
        $statusColors = [];

        foreach ($statuses as $status) {
            // Get the color name or default to 'default'
            $colorName = $customColors[$status] ?? null;

            // Convert to KanbanColor enum and get CSS class for the badge
            $color = KanbanColor::fromStringOrDefault($colorName);
            $statusColors[$status] = $color->classes();
        }

        return $statusColors;
    }

    /**
     * Define the create form schema.
     */
    public function createForm(Form $form): Form
    {
        return $this->adapter->getCreateForm($form, $this->activeColumn);
    }

    /**
     * Define the edit form schema.
     */
    public function editForm(Form $form): Form
    {
        return $this->adapter->getEditForm($form);
    }

    /**
     * Load the columns data for the Kanban board.
     *
     * @return void
     */
    protected function loadColumnsData(): void
    {
        $statusValues = $this->adapter->getStatusValues();
        $columns = [];
        foreach ($statusValues as $value => $label) {
            $columns[$value] = [
                'name' => $label,
                'items' => $this->getItemsForStatus($value),
                'total' => $this->getTotalItemsCount($value),
            ];
        }
        $this->columns = $columns;
    }

    /**
     * Get the items for a specific status with pagination.
     *
     * @param string $status The status value
     * @return array
     */
    public function getItemsForStatus(string $status): array
    {
        $perPage = $this->perPage[$status] ?? 10;
        $items = $this->adapter->getItemsForStatus($status, $perPage);
        return $this->formatItems($items);
    }

    /**
     * Get the total count of items for a specific status.
     *
     * @param string $status The status value
     * @return int
     */
    public function getTotalItemsCount(string $status): int
    {
        return $this->adapter->getTotalItemsCount($status);
    }

    /**
     * Load more items for a specific column.
     *
     * @param string $columnId The column ID
     * @param int|null $count Number of additional items to load (null = use configured increment)
     * @return void
     */
    public function loadMoreItems(string $columnId, ?int $count = null): void
    {
        $this->loading[$columnId] = true;

        // Increment only the specific column's perPage value
        if (!isset($this->perPage[$columnId])) {
            $this->perPage[$columnId] = $this->initialCardsCount;
        }

        // Use passed count or default to configured increment
        $increment = $count ?? $this->cardsIncrement;
        $this->perPage[$columnId] += $increment;

        // Only reload the specific column data
        $this->columns[$columnId]['items'] = $this->getItemsForStatus($columnId);

        $this->loading[$columnId] = false;
        $this->dispatch('kanban-items-loaded', ['columnId' => $columnId]);
    }

    /**
     * Format the items for display in the Kanban board.
     *
     * @param Collection $items The items to format
     * @return array
     */
    protected function formatItems(Collection $items): array
    {
        $titleAttribute = $this->adapter->getTitleAttribute();
        $descriptionAttribute = $this->adapter->getDescriptionAttribute();
        $cardAttributes = $this->adapter->getCardAttributes();

        return $items->map(function ($item) use ($titleAttribute, $descriptionAttribute, $cardAttributes) {
            $result = [
                'id' => $item->getKey(),
                'title' => $item->{$titleAttribute},
            ];

            if ($descriptionAttribute) {
                $result['description'] = $item->{$descriptionAttribute};
            }

            foreach ($cardAttributes as $attribute => $label) {
                $value = $item->{$attribute};
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d');
                } elseif (is_object($value) && method_exists($value, '__toString')) {
                    $value = (string) $value;
                }
                $result[$attribute] = $value;
            }

            return $result;
        })->toArray();
    }

    /**
     * Update the order of an item within the same column.
     *
     * @param $columnId
     * @param $cards
     * @return bool
     */
    public function updateColumnCards($columnId, $cards): bool
    {
        $this->adapter->updateColumnCards($columnId, $cards);

        $this->loadColumnsData();

        return true;
    }

    /**
     * Move a card from one column to another.
     *
     * @param string|int $cardId The card ID
     * @param string $fromColumn The source column ID
     * @param string $toColumn The target column ID
     * @param array $toColumnCards The ordered cards in the target column
     * @return bool
     */
    public function moveCardBetweenColumns($cardId, $fromColumn, $toColumn, $toColumnCards): bool
    {
        $card = $this->adapter->getModelById($cardId);

        if (!$card) {
            return false;
        }

        // First update the card's status
        $card->{$this->adapter->getStatusField()} = $toColumn;
        $card->save();

        // Then update the order of cards in the target column
        $this->adapter->updateColumnCards($toColumn, $toColumnCards);

        $this->loadColumnsData();

        return true;
    }

    /**
     * Open the create card form modal.
     *
     * @param string $columnId The column ID
     * @return void
     */
    public function openCreateForm(string $columnId): void
    {
        $this->activeColumn = $columnId;
        $this->createFormData = [
            'title' => '',
            'description' => '',
            $this->adapter->getStatusField() => $columnId,
        ];

        // Initialize card attributes
        foreach ($this->adapter->getCardAttributes() as $attribute => $label) {
            $this->createFormData[$attribute] = '';
        }
    }

    /**
     * Open the edit card form modal.
     *
     * @param string|int $cardId The card ID
     * @param string $columnId The column ID
     * @return void
     */
    public function openEditForm(string|int $cardId, string $columnId): void
    {
        $this->activeCardId = $cardId;
        $this->activeColumn = $columnId;

        $card = $this->adapter->getModelById($cardId);

        if (!$card) {
            Notification::make()
                ->title(__('Card not found'))
                ->danger()
                ->send();
            return;
        }

        $data = [
            $this->adapter->getStatusField() => $columnId,
            'title' => $card->{$this->adapter->getTitleAttribute()},
            'description' => '',
        ];

        if ($descriptionAttribute = $this->adapter->getDescriptionAttribute()) {
            $data['description'] = $card->{$descriptionAttribute} ?? '';
        }

        // Initialize card attributes
        foreach ($this->adapter->getCardAttributes() as $attribute => $label) {
            $data[$attribute] = $card->{$attribute} ?? '';
        }

        $this->editFormData = $data;
    }

    /**
     * Create a new card with the given attributes.
     *
     * @return void
     */
    public function createCard(): void
    {
        $form = $this->createForm;
        $data = $form->getState();

        $card = $this->adapter->createCard($data);

        if ($card) {
            $this->loadColumnsData();
            $this->resetCreateForm();

            Notification::make()
                ->title(__(':recordLabel created successfully', ['recordLabel' => $this->config['recordLabel']]))
                ->success()
                ->send();

            $this->dispatch('kanban-card-created', [
                'id' => $card->getKey(),
                'status' => $card->{$this->adapter->getStatusField()},
            ]);
        }
    }

    /**
     * Reset the create form data.
     */
    private function resetCreateForm(): void
    {
        $this->createFormData = [
            'title' => '',
            'description' => '',
            $this->adapter->getStatusField() => array_key_first($this->adapter->getStatusValues()),
        ];

        // Reset card attributes
        foreach ($this->adapter->getCardAttributes() as $attribute => $label) {
            $this->createFormData[$attribute] = '';
        }
    }

    /**
     * Update an existing card with the given attributes.
     *
     * @return void
     */
    public function updateCard(): void
    {
        $form = $this->editForm;
        $data = $form->getState();

        $card = $this->adapter->getModelById($this->activeCardId);

        if (!$card) {
            Notification::make()
                ->title(__('Card not found'))
                ->danger()
                ->send();
            return;
        }

        $result = $this->adapter->updateCard($card, $data);

        if ($result) {
            $this->loadColumnsData();
            $this->resetEditForm();

            $this->dispatch('kanban-card-updated', ['id' => $this->activeCardId]);

            Notification::make()
                ->title(__(':recordLabel updated successfully', ['recordLabel' => $this->config['recordLabel']]))
                ->success()
                ->send();
        }
    }

    /**
     * Reset the edit form data.
     */
    private function resetEditForm(): void
    {
        $this->editFormData = [
            'title' => '',
            'description' => '',
            $this->adapter->getStatusField() => array_key_first($this->adapter->getStatusValues()),
        ];

        // Reset card attributes
        foreach ($this->adapter->getCardAttributes() as $attribute => $label) {
            $this->editFormData[$attribute] = '';
        }
    }

    /**
     * Delete an existing card.
     *
     * @return void
     */
    public function deleteCard(): void
    {
        $card = $this->adapter->getModelById($this->activeCardId);

        if (!$card) {
            Notification::make()
                ->title(__('Card not found'))
                ->danger()
                ->send();
            return;
        }

        $result = $this->adapter->deleteCard($card);

        if ($result) {
            $this->loadColumnsData();
            $this->resetEditForm();

            $this->dispatch('kanban-card-deleted', ['id' => $this->activeCardId]);

            Notification::make()
                ->title(__(':recordLabel deleted successfully', ['recordLabel' => $this->config['recordLabel']]))
                ->success()
                ->send();
        }
    }

    /**
     * Register the forms with Livewire.
     */
    protected function getForms(): array
    {
        return [
            'createForm',
            'editForm',
        ];
    }

    /**
     * Render the component.
     *
     * @return View
     */
    public function render(): View
    {
        return view('flowforge::livewire.board');
    }
}

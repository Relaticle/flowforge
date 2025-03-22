<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Livewire;

use Filament\Forms\Concerns\HasStateBindingModifiers;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

class KanbanBoard extends Component
{
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
     * Mount the component.
     *
     * @param IKanbanAdapter $adapter The Kanban board adapter
     * @return void
     */
    public function mount(IKanbanAdapter $adapter): void
    {
        $this->adapter = $adapter;

        $this->config = [
            'statusField' => $adapter->getStatusField(),
            'statusValues' => $adapter->getStatusValues(),
            'titleAttribute' => $adapter->getTitleAttribute(),
            'descriptionAttribute' => $adapter->getDescriptionAttribute(),
            'cardAttributes' => $adapter->getCardAttributes(),
        ];
        $this->loadColumnsData();
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
            ];
        }
        $this->columns = $columns;
    }

    /**
     * Get the items for a specific status.
     *
     * @param string $status The status value
     * @return array
     */
    public function getItemsForStatus(string $status): array
    {
        $items = $this->adapter->getItemsForStatus($status);
        return $this->formatItems($items);
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

            foreach ($cardAttributes as $attribute) {
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
     * Update the status of an item.
     *
     * @param mixed $id The ID of the item
     * @param string $status The new status value
     * @return bool
     */
    public function updateStatus($id, string $status): bool
    {
        $item = $this->adapter->getModelById($id);

        if (!$item) {
            return false;
        }

        $result = $this->adapter->updateStatus($item, $status);

        if ($result) {
            $this->loadColumnsData();
            $this->dispatch('kanban-item-moved', ['id' => $id, 'status' => $status]);
        }

        return $result;
    }

    /**
     * Refresh the Kanban board.
     *
     * @return void
     */
    public function refreshBoard(): void
    {
        $this->loadColumnsData();
    }

    /**
     * Create a new card with the given attributes.
     *
     * @param array<string, mixed> $attributes The card attributes
     * @return mixed|null The ID of the created card or null if creation failed
     */
    public function createCard(array $attributes): mixed
    {
        $card = $this->adapter->createCard($attributes);

        if ($card) {
            $this->loadColumnsData();
            $this->dispatch('kanban-card-created', [
                'id' => $card->getKey(),
                'status' => $card->{$this->adapter->getStatusField()},
            ]);

            return $card->getKey();
        }

        return null;
    }

    /**
     * Update an existing card with the given attributes.
     *
     * @param string|int $id The ID of the card to update
     * @param array<string, mixed> $attributes The card attributes to update
     * @return bool
     */
    public function updateCard(string|int $id, array $attributes): bool
    {
        $card = $this->adapter->getModelById($id);

        if (!$card) {
            return false;
        }

        $result = $this->adapter->updateCard($card, $attributes);

        if ($result) {
            $this->loadColumnsData();
            $this->dispatch('kanban-card-updated', ['id' => $id]);
        }

        return $result;
    }

    /**
     * Delete an existing card.
     *
     * @param string|int $id The ID of the card to delete
     * @return bool
     */
    public function deleteCard(string|int $id): bool
    {
        $card = $this->adapter->getModelById($id);

        if (!$card) {
            return false;
        }

        $result = $this->adapter->deleteCard($card);

        if ($result) {
            $this->loadColumnsData();
            $this->dispatch('kanban-card-deleted', ['id' => $id]);
        }

        return $result;
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

<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Livewire;

use Illuminate\Support\Collection;
use Livewire\Component;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

class KanbanBoard extends Component
{
    /**
     * The Kanban board adapter.
     */
    protected IKanbanAdapter $adapter;

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
        $columns = [];
        $statusValues = $this->adapter->getStatusValues();

        foreach ($statusValues as $statusValue => $statusLabel) {
            $items = $this->getItemsForStatus($statusValue);
            $columns[$statusValue] = [
                'name' => $statusLabel,
                'items' => $items,
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
        $model = $this->adapter->getModelById($id);

        if (!$model) {
            return false;
        }

        $result = $this->adapter->updateStatus($model, $status);

        if ($result) {
            // Refresh the columns data
            $this->loadColumnsData();

            // Emit an event to notify the frontend
            $this->dispatch('kanban-card-moved', [
                'id' => $id,
                'status' => $status,
            ]);
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
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('flowforge::livewire.kanban-board');
    }
}

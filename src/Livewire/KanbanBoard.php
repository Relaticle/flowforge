<?php

namespace Relaticle\Flowforge\Livewire;

use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

class KanbanBoard extends Component
{
    use InteractsWithForms;

    /**
     * @var string
     */
    public string $modelClass;

    /**
     * @var string
     */
    public string $statusField;

    /**
     * @var array<string, string>
     */
    public array $statusValues = [];

    /**
     * @var string
     */
    public string $titleAttribute;

    /**
     * @var string|null
     */
    public ?string $descriptionAttribute = null;

    /**
     * @var array<string, string>
     */
    public array $cardAttributes = [];

    /**
     * @var array<string, Collection>
     */
    public array $columns = [];

    /**
     * @var array<string, string>
     */
    public array $columnLabels = [];

    /**
     * @var string|null
     */
    public ?string $boardTitle = null;

    /**
     * Mount the component.
     *
     * @param string $modelClass
     * @param string $statusField
     * @param array<string, string> $statusValues
     * @param string $titleAttribute
     * @param string|null $descriptionAttribute
     * @param array<string, string> $cardAttributes
     * @param string|null $boardTitle
     * @return void
     */
    public function mount(
        string $modelClass,
        string $statusField,
        array $statusValues,
        string $titleAttribute,
        ?string $descriptionAttribute = null,
        array $cardAttributes = [],
        ?string $boardTitle = null
    ): void {
        $this->modelClass = $modelClass;
        $this->statusField = $statusField;
        $this->statusValues = $statusValues;
        $this->titleAttribute = $titleAttribute;
        $this->descriptionAttribute = $descriptionAttribute;
        $this->cardAttributes = $cardAttributes;
        $this->columnLabels = $statusValues;
        $this->boardTitle = $boardTitle;

        $this->loadItems();
    }

    /**
     * Load items into columns with eager loading for performance.
     *
     * @return void
     */
    protected function loadItems(): void
    {
        $modelClass = $this->modelClass;

        // Determine which relations to eager load based on card attributes
        $eagerLoadRelations = $this->getEagerLoadRelations();

        // Get items with eager loading for better performance
        $items = $eagerLoadRelations
            ? $modelClass::with($eagerLoadRelations)->get()
            : $modelClass::all();

        // Initialize columns
        foreach ($this->columnLabels as $value => $label) {
            $this->columns[$value] = collect();
        }

        // Group items by status
        foreach ($items as $item) {
            $status = $item->{$this->statusField};

            if (isset($this->columns[$status])) {
                $this->columns[$status]->push($item);
            }
        }

        // Dispatch browser event for any JavaScript listeners
        $this->dispatch('kanban-items-loaded');
    }

    /**
     * Determine which relations to eager load based on card attributes.
     *
     * @return array
     */
    protected function getEagerLoadRelations(): array
    {
        $relations = [];

        // Check if any card attributes are relationship attributes
        foreach (array_keys($this->cardAttributes) as $attribute) {
            if (str_contains($attribute, '.')) {
                $relation = explode('.', $attribute)[0];
                $relations[] = $relation;
            }
        }

        // Check if title or description are relationship attributes
        if (str_contains($this->titleAttribute, '.')) {
            $relation = explode('.', $this->titleAttribute)[0];
            $relations[] = $relation;
        }

        if ($this->descriptionAttribute && str_contains($this->descriptionAttribute, '.')) {
            $relation = explode('.', $this->descriptionAttribute)[0];
            $relations[] = $relation;
        }

        return array_unique($relations);
    }

    /**
     * Update the status of an item.
     *
     * @param string $itemId
     * @param string $newStatus
     * @return void
     */
    public function updateItemStatus(string $itemId, string $newStatus): void
    {
        $modelClass = $this->modelClass;
        $item = $modelClass::find($itemId);

        if ($item) {
            // Get the old status for event
            $oldStatus = $item->{$this->statusField};

            // Update the status
            $item->{$this->statusField} = $newStatus;
            $item->save();

            // Reload items
            $this->loadItems();

            // Dispatch events for real-time updates
            $this->dispatch('kanban-item-updated', [
                'itemId' => $itemId,
                'oldStatus' => $oldStatus,
                'newStatus' => $newStatus
            ]);

            // Dispatch count updated events for both columns
            $this->dispatch('kanban-count-updated', [
                'oldColumn' => $oldStatus,
                'newColumn' => $newStatus,
                'oldCount' => isset($this->columns[$oldStatus]) ? $this->columns[$oldStatus]->count() : 0,
                'newCount' => isset($this->columns[$newStatus]) ? $this->columns[$newStatus]->count() : 0
            ]);
        }
    }

    /**
     * Get the total count of all items across all columns.
     *
     * @return int
     */
    #[Computed]
    public function getTotalItemCount(): int
    {
        return array_sum(array_map(fn ($col) => $col->count(), $this->columns));
    }

    /**
     * Refresh the board data.
     *
     * @return void
     */
    public function refresh(): void
    {
        $this->loadItems();
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

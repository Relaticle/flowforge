<?php

namespace Relaticle\Flowforge\Adapters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

class DefaultKanbanAdapter implements IKanbanAdapter
{
    /**
     * @var Model
     */
    protected Model $model;

    /**
     * @var string
     */
    protected string $statusField;

    /**
     * @var array<string, string>
     */
    protected array $statusValues;

    /**
     * @var array<string, string>
     */
    protected array $cardAttributes;

    /**
     * @var string
     */
    protected string $titleAttribute;

    /**
     * @var string|null
     */
    protected ?string $descriptionAttribute;

    /**
     * Create a new adapter instance.
     *
     * @param Model $model
     * @param string $statusField
     * @param array<string, string> $statusValues
     * @param string $titleAttribute
     * @param string|null $descriptionAttribute
     * @param array<string, string> $cardAttributes
     */
    public function __construct(
        Model $model,
        string $statusField = 'status',
        array $statusValues = [],
        string $titleAttribute = 'name',
        ?string $descriptionAttribute = null,
        array $cardAttributes = []
    ) {
        $this->model = $model;
        $this->statusField = $statusField;
        $this->statusValues = $statusValues;
        $this->titleAttribute = $titleAttribute;
        $this->descriptionAttribute = $descriptionAttribute;
        $this->cardAttributes = $cardAttributes;
    }

    /**
     * Get the model instance.
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Get the status field name for the model.
     *
     * @return string
     */
    public function getStatusField(): string
    {
        return $this->statusField;
    }

    /**
     * Get all available status values for the model.
     *
     * @return array<string, string>
     */
    public function getStatusValues(): array
    {
        if (empty($this->statusValues)) {
            // If no status values are provided, try to get them from the model
            $query = $this->model->newQuery();
            $values = $query->distinct()->pluck($this->statusField)->filter()->toArray();
            
            return array_combine($values, $values);
        }

        return $this->statusValues;
    }

    /**
     * Get all items for the Kanban board.
     *
     * @return Collection
     */
    public function getItems(): Collection
    {
        return $this->model->newQuery()->get();
    }

    /**
     * Update the status of an item.
     *
     * @param Model $model
     * @param string $status
     * @return bool
     */
    public function updateStatus(Model $model, string $status): bool
    {
        $model->{$this->statusField} = $status;
        
        return $model->save();
    }

    /**
     * Get the attributes to display on the card.
     *
     * @return array<string, string>
     */
    public function getCardAttributes(): array
    {
        return $this->cardAttributes;
    }

    /**
     * Get the title attribute for the card.
     *
     * @return string
     */
    public function getTitleAttribute(): string
    {
        return $this->titleAttribute;
    }

    /**
     * Get the description attribute for the card.
     *
     * @return string|null
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->descriptionAttribute;
    }
}

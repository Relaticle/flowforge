<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Adapters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

class DefaultKanbanAdapter implements IKanbanAdapter
{
    /**
     * The status field for the model.
     *
     * @var string
     */
    protected string $statusField;

    /**
     * The status values for the model.
     *
     * @var array<string, string>
     */
    protected array $statusValues;

    /**
     * The title attribute for the model.
     *
     * @var string
     */
    protected string $titleAttribute;

    /**
     * The description attribute for the model.
     *
     * @var string|null
     */
    protected ?string $descriptionAttribute;

    /**
     * The card attributes for the model.
     *
     * @var array<string>
     */
    protected array $cardAttributes = [];

    /**
     * The model class for the adapter.
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * Create a new adapter instance.
     *
     * @param string $modelClass The model class
     * @param string $statusField The status field
     * @param array<string, string> $statusValues The status values
     * @param string $titleAttribute The title attribute
     * @param string|null $descriptionAttribute The description attribute
     * @param array<string> $cardAttributes The card attributes
     */
    public function __construct(
        string $modelClass,
        string $statusField,
        array $statusValues,
        string $titleAttribute,
        ?string $descriptionAttribute = null,
        array $cardAttributes = []
    ) {
        $this->modelClass = $modelClass;
        $this->statusField = $statusField;
        $this->statusValues = $statusValues;
        $this->titleAttribute = $titleAttribute;
        $this->descriptionAttribute = $descriptionAttribute;
        $this->cardAttributes = $cardAttributes;
    }

    /**
     * Get the model class for the adapter.
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->modelClass;
    }

    /**
     * Find a model by its ID.
     *
     * @param mixed $id The model ID
     * @return Model|null
     */
    public function getModelById($id): ?Model
    {
        $modelClass = $this->getModel();
        return $modelClass::find($id);
    }

    /**
     * Get the status field for the model.
     *
     * @return string
     */
    public function getStatusField(): string
    {
        return $this->statusField;
    }

    /**
     * Get the status values for the model.
     *
     * @return array<string, string>
     */
    public function getStatusValues(): array
    {
        return $this->statusValues;
    }

    /**
     * Get the title attribute for the model.
     *
     * @return string
     */
    public function getTitleAttribute(): string
    {
        return $this->titleAttribute;
    }

    /**
     * Get the description attribute for the model.
     *
     * @return string|null
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->descriptionAttribute;
    }

    /**
     * Get the card attributes for the model.
     *
     * @return array<string>
     */
    public function getCardAttributes(): array
    {
        return $this->cardAttributes;
    }

    /**
     * Get the items for all statuses.
     *
     * @return Collection<int, Model>
     */
    public function getItems(): Collection
    {
        $modelClass = $this->getModel();
        return $modelClass::all();
    }

    /**
     * Get the items for a specific status.
     *
     * @param string $status The status value
     * @return Collection<int, Model>
     */
    public function getItemsForStatus(string $status): Collection
    {
        $modelClass = $this->getModel();
        return $modelClass::where($this->getStatusField(), $status)->get();
    }

    /**
     * Update the status of a model.
     *
     * @param Model $model The model to update
     * @param string $status The new status value
     * @return bool
     */
    public function updateStatus(Model $model, string $status): bool
    {
        $model->{$this->getStatusField()} = $status;
        return $model->save();
    }
}

<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Adapters;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Wireable;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

class DefaultKanbanAdapter implements IKanbanAdapter, Wireable
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
     * The order field for the model.
     *
     * @var string|null
     */
    protected ?string $orderField = null;

    /**
     * The model class for the adapter.
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * The singular label for the model.
     *
     * @var string
     */
    protected string $recordLabel;

    /**
     * The plural label for the model.
     *
     * @var string
     */
    protected string $pluralRecordLabel;

    /**
     * Create a new adapter instance.
     *
     * @param string $modelClass The model class
     * @param string $statusField The status field
     * @param array<string, string> $statusValues The status values
     * @param string $titleAttribute The title attribute
     * @param string|null $descriptionAttribute The description attribute
     * @param array<string> $cardAttributes The card attributes
     * @param string|null $orderField The order field
     * @param string|null $recordLabel The singular label for the model
     * @param string|null $pluralRecordLabel The plural label for the model
     */
    public function __construct(
        string  $modelClass,
        string  $statusField,
        array   $statusValues,
        string  $titleAttribute,
        ?string $descriptionAttribute = null,
        array   $cardAttributes = [],
        ?string $orderField = null,
        ?string $recordLabel = null,
        ?string $pluralRecordLabel = null
    )
    {
        $this->modelClass = $modelClass;
        $this->statusField = $statusField;
        $this->statusValues = $statusValues;
        $this->titleAttribute = $titleAttribute;
        $this->descriptionAttribute = $descriptionAttribute;
        $this->cardAttributes = $cardAttributes;
        $this->orderField = $orderField;

        // Set model labels with defaults
        $this->recordLabel = $recordLabel ?? Str::singular(class_basename($modelClass));
        $this->pluralRecordLabel = $pluralRecordLabel ?? Str::singular(class_basename($modelClass));
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
     * Get the order field for the model.
     *
     * @return string|null
     */
    public function getOrderField(): ?string
    {
        return $this->orderField;
    }

    /**
     * Get the singular label for the model.
     *
     * @return string
     */
    public function getRecordLabel(): string
    {
        return $this->recordLabel;
    }

    /**
     * Get the plural label for the model.
     *
     * @return string
     */
    public function getPluralRecordLabel(): string
    {
        return $this->pluralRecordLabel;
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
        $query = $modelClass::where($this->getStatusField(), $status);

        // Add ordering if order field is set
        if ($this->getOrderField()) {
            $query->orderBy($this->getOrderField());
        }

        return $query->get();
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

    /**
     * Update the order of a model.
     *
     * @param string|int $columnId
     * @param array $cards
     * @return bool
     * @throws \Throwable
     */
    public function updateColumnCards(string|int $columnId, array $cards): bool
    {
        if (!$this->getOrderField()) {
            return false;
        }

        $model = app($this->getModel());

        // Validate column ID exists in status values
        if (!array_key_exists((string)$columnId, $this->getStatusValues())) {
            return false;
        }

        return DB::transaction(function () use ($model, $columnId, $cards) {
            foreach ($cards as $index => $id) {
                $model->newQuery()
                    ->where($model->getQualifiedKeyName(), $id)
                    ->update([
                        $this->getStatusField() => $columnId,
                        $this->getOrderField() => $index,
                    ]);
            }

            return true;
        });
    }

    /**
     * Create a new card with the given attributes.
     *
     * @param array<string, mixed> $attributes The card attributes
     * @return Model|null
     */
    public function createCard(array $attributes): ?Model
    {
        $modelClass = $this->getModel();
        $card = new $modelClass();

        // Set status if provided, otherwise use the first status as default
        $status = $attributes[$this->getStatusField()] ?? array_key_first($this->getStatusValues());
        $card->{$this->getStatusField()} = $status;

        // Set order if the field exists
        if ($this->getOrderField()) {
            // Set the highest order by default (add to the end of the column)
            $maxOrder = $modelClass::where($this->getStatusField(), $status)
                ->max($this->getOrderField()) ?? 0;

            $card->{$this->getOrderField()} = $maxOrder + 1;
        }

        // Set title
        if (isset($attributes[$this->getTitleAttribute()])) {
            $card->{$this->getTitleAttribute()} = $attributes[$this->getTitleAttribute()];
        }

        // Set description if the attribute exists
        if ($this->getDescriptionAttribute() && isset($attributes[$this->getDescriptionAttribute()])) {
            $card->{$this->getDescriptionAttribute()} = $attributes[$this->getDescriptionAttribute()];
        }

        // Set additional card attributes
        foreach ($this->getCardAttributes() as $attribute) {
            if (isset($attributes[$attribute])) {
                $card->{$attribute} = $attributes[$attribute];
            }
        }

        return $card->save() ? $card : null;
    }

    /**
     * Update an existing card with the given attributes.
     *
     * @param Model $card The card to update
     * @param array<string, mixed> $attributes The card attributes to update
     * @return bool
     */
    public function updateCard(Model $card, array $attributes): bool
    {
        // Update status if provided
        if (isset($attributes[$this->getStatusField()])) {
            $card->{$this->getStatusField()} = $attributes[$this->getStatusField()];
        }

        // Update title if provided
        if (isset($attributes[$this->getTitleAttribute()])) {
            $card->{$this->getTitleAttribute()} = $attributes[$this->getTitleAttribute()];
        }

        // Update description if provided and the attribute exists
        if ($this->getDescriptionAttribute() && isset($attributes[$this->getDescriptionAttribute()])) {
            $card->{$this->getDescriptionAttribute()} = $attributes[$this->getDescriptionAttribute()];
        }

        // Update additional card attributes
        foreach ($this->getCardAttributes() as $attribute) {
            if (isset($attributes[$attribute])) {
                $card->{$attribute} = $attributes[$attribute];
            }
        }

        return $card->save();
    }

    /**
     * Delete an existing card.
     *
     * @param Model $card The card to delete
     * @return bool
     */
    public function deleteCard(Model $card): bool
    {
        return $card->delete();
    }

    /**
     * Convert the adapter to a Livewire-compatible array.
     *
     * @return array<string, mixed>
     */
    public function toLivewire(): array
    {
        return [
            'model' => $this->getModel(),
            'statusField' => $this->getStatusField(),
            'statusValues' => $this->getStatusValues(),
            'titleAttribute' => $this->getTitleAttribute(),
            'descriptionAttribute' => $this->getDescriptionAttribute(),
            'cardAttributes' => $this->getCardAttributes(),
            'orderField' => $this->getOrderField(),
            'recordLabel' => $this->getRecordLabel(),
            'pluralRecordLabel' => $this->getPluralRecordLabel(),
        ];
    }

    /**
     * Create a new adapter instance from a Livewire-compatible array.
     *
     * @param array<string, mixed> $value The Livewire-compatible array
     * @return static
     */
    public static function fromLivewire($value)
    {
        return new static(
            $value['model'],
            $value['statusField'],
            $value['statusValues'],
            $value['titleAttribute'],
            $value['descriptionAttribute'] ?? null,
            $value['cardAttributes'] ?? [],
            $value['orderField'] ?? null,
            $value['recordLabel'] ?? null,
            $value['pluralRecordLabel'] ?? null
        );
    }
}

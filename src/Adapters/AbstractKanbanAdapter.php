<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Adapters;

use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Wireable;
use Relaticle\Flowforge\Config\KanbanConfig;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;

abstract class AbstractKanbanAdapter implements KanbanAdapterInterface, Wireable
{
    /**
     * The base Eloquent query builder.
     *
     * @var Builder
     */
    public Builder $baseQuery;

    /**
     * Create a new abstract Kanban adapter instance.
     */
    public function __construct(
        Builder $query,
        public KanbanConfig $config
    ) {
    }

    /**
     * Get a new query builder, cloned from the base query.
     */
    protected function newQuery(): Builder
    {
        return clone $this->baseQuery;
    }

    /**
     * Find a model by its ID.
     *
     * @param mixed $id The model ID
     */
    public function getModelById(mixed $id): ?Model
    {
        // Just find by ID without additional filters from the base query
        // This ensures we can find models by ID regardless of the base query filters
        return $this->baseQuery->getModel()::query()->find($id);
    }

    /**
     * Get all items for the Kanban board.
     */
    public function getItems(): Collection
    {
        $query = $this->newQuery();
        $orderField = $this->config->getOrderField();

        if ($orderField !== null) {
            $query->orderBy($orderField);
        }

        $models = $query->get();

        return $this->formatCardsForDisplay($models);
    }

    /**
     * Get items for a specific column with pagination.
     *
     * @param string|int $columnId The column ID
     * @param int $limit The number of items to return
     */
    public function getItemsForColumn(string|int $columnId, int $limit = 10): Collection
    {
        $columnField = $this->config->getColumnField();
        $orderField = $this->config->getOrderField();

        $query = $this->newQuery()->where($columnField, $columnId);

        if ($orderField !== null) {
            $query->orderBy($orderField);
        }

        $models = $query->limit($limit)->get();

        return $this->formatCardsForDisplay($models);
    }

    /**
     * Get the total count of items for a specific column.
     *
     * @param string|int $columnId The column ID
     */
    public function getColumnItemsCount(string|int $columnId): int
    {
        $columnField = $this->config->getColumnField();

        return $this->newQuery()
            ->where($columnField, $columnId)
            ->count();
    }

    /**
     * Create a new card with the given attributes.
     *
     * @param array<string, mixed> $attributes The card attributes
     */
    public function createCard(array $attributes): ?Model
    {
        $model = $this->baseQuery->getModel()->newInstance();

        // Apply any scopes from the base query if applicable
        // For example, if the base query filters by user_id, we want to set that on the new model
        $wheres = $this->baseQuery->getQuery()->wheres;

        foreach ($wheres as $where) {
            if (isset($where['column']) && isset($where['value']) && $where['type'] === 'Basic') {
                // If the filter is a basic where clause, apply it to the new model
                // This ensures models created through this adapter respect the base query conditions
                $model->{$where['column']} = $where['value'];
            }
        }

        $model->fill($attributes);

        if ($model->save()) {
            return $model;
        }

        return null;
    }

    /**
     * Update an existing card with the given attributes.
     *
     * @param Model $card The card to update
     * @param array<string, mixed> $attributes The card attributes to update
     */
    public function updateCard(Model $card, array $attributes): bool
    {
        $card->fill($attributes);

        return $card->save();
    }

    /**
     * Delete an existing card.
     *
     * @param Model $card The card to delete
     */
    public function deleteCard(Model $card): bool
    {
        return $card->delete();
    }

    /**
     * Get the configuration for this adapter.
     */
    public function getConfig(): KanbanConfig
    {
        return $this->config;
    }

    /**
     * Get the form for creating cards.
     *
     * @param Form $form The form instance
     * @param mixed $activeColumn The active column
     */
    public function getCreateForm(Form $form, mixed $activeColumn): Form
    {
        // Fall back to default create form implementation
        $titleField = $this->config->getTitleField();
        $descriptionField = $this->config->getDescriptionField();

        $schema = KanbanConfig::getDefaultCreateFormSchema($titleField, $descriptionField);

        // For create form, set the column field value based on the active column
        // but hide it from the form as it's determined by where the user clicked
        if ($activeColumn) {
            $form->statePath(null);
        }

        return $form->schema($schema);
    }

    /**
     * Get the form for editing cards.
     *
     * @param Form $form The form instance
     */
    public function getEditForm(Form $form): Form
    {
        // Fall back to default edit form implementation
        $titleField = $this->config->getTitleField();
        $descriptionField = $this->config->getDescriptionField();
        $columnField = $this->config->getColumnField();
        $columnValues = $this->config->getColumnValues();

        $schema = KanbanConfig::getDefaultEditFormSchema(
            $titleField,
            $descriptionField,
            $columnField,
            $columnValues
        );

        return $form->schema($schema);
    }

    /**
     * Format a model as a card for display.
     *
     * @param Model $model The model to format
     * @return array<string, mixed> The formatted card
     */
    protected function formatCardForDisplay(Model $model): array
    {
        $titleField = $this->config->getTitleField();
        $descriptionField = $this->config->getDescriptionField();
        $cardAttributes = $this->config->getCardAttributes();
        $columnField = $this->config->getColumnField();

        $card = [
            'id' => $model->getKey(),
            'title' => $model->{$titleField},
            'column' => $model->{$columnField},
        ];

        if ($descriptionField !== null && isset($model->{$descriptionField})) {
            $card['description'] = $model->{$descriptionField};
        }

        foreach ($cardAttributes as $key => $label) {
            $field = is_string($key) ? $key : $label;
            $card['attributes'][$field] = [
                'label' => is_string($key) ? $label : $field,
                'value' => $model->{$field},
            ];
        }

        return $card;
    }

    /**
     * Format a collection of models as cards for display.
     *
     * @param Collection $models The models to format
     * @return Collection The formatted cards
     */
    protected function formatCardsForDisplay(Collection $models): Collection
    {
        return $models->map(fn (Model $model) => $this->formatCardForDisplay($model));
    }

    /**
     * Update the order of cards in a column.
     *
     * @param string|int $columnId The column ID
     * @param array<int, mixed> $cardIds The card IDs in their new order
     * @return bool Whether the operation was successful
     */
    public function updateCardsOrderAndColumn(string|int $columnId, array $cardIds): bool
    {
        $orderField = $this->config->getOrderField();
        $columnField = $this->config->getColumnField();
        $success = true;

        foreach ($cardIds as $index => $cardId) {
            $model = $this->getModelById($cardId);

            if ($model === null) {
                $success = false;
                continue;
            }

            if ($orderField !== null) {
                $model->{$orderField} = $index + 1;
            }
            $model->{$columnField} = $columnId;

            if (!$model->save()) {
                $success = false;
            }
        }

        return $success;
    }


    /**
     * Convert the adapter to a Livewire-compatible array.
     *
     * @return array<string, mixed>
     */
    public function toLivewire(): array
    {
        return [
            'query' => \EloquentSerialize::serialize($this->baseQuery),
            'config' => $this->config->toArray(),
        ];
    }

    /**
     * Create a new adapter instance from a Livewire-compatible array.
     *
     * @param array<string, mixed> $value The Livewire-compatible array
     * @return static
     */
    public static function fromLivewire($value): static
    {
        $query = \EloquentSerialize::unserialize($value['query']);
        $config = new KanbanConfig(...$value['config']);

        return new static($query, $config);
    }
}

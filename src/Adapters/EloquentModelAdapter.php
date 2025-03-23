<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Adapters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Relaticle\Flowforge\Config\KanbanConfig;

/**
 * Eloquent model adapter for Kanban boards.
 *
 * This adapter handles Eloquent model class references and provides
 * implementation for all data operations required by the Kanban board.
 */
class EloquentModelAdapter extends AbstractKanbanAdapter
{
    /**
     * The Eloquent model class.
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * Create a new Eloquent model adapter instance.
     *
     * @param string $modelClass The Eloquent model class
     * @param KanbanConfig $config The Kanban configuration
     */
    public function __construct(
        string $modelClass,
        KanbanConfig $config
    ) {
        parent::__construct($config);
        
        $this->modelClass = $modelClass;
    }

    /**
     * Get a new query builder for the model.
     */
    protected function newQuery(): Builder
    {
        return $this->modelClass::query();
    }

    /**
     * Find a model by its ID.
     *
     * @param mixed $id The model ID
     */
    public function getModelById(mixed $id): ?Model
    {
        return $this->newQuery()->find($id);
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

        $query = $this->newQuery()
            ->where($columnField, $columnId);

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
        $model = new $this->modelClass();
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
} 
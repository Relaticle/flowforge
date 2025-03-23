<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Adapters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Relaticle\Flowforge\Config\KanbanConfig;

/**
 * Eloquent query adapter for Kanban boards.
 *
 * This adapter handles Eloquent query builder references and provides
 * implementation for all data operations required by the Kanban board.
 * It's used for pre-filtered queries such as `Task::query()->where('user_id', auth()->id())`.
 */
class EloquentQueryAdapter extends AbstractKanbanAdapter
{
    /**
     * The base Eloquent query builder.
     *
     * @var Builder
     */
    protected Builder $baseQuery;

    /**
     * Create a new Eloquent query adapter instance.
     *
     * @param Builder $query The base Eloquent query builder
     * @param KanbanConfig $config The Kanban configuration
     */
    public function __construct(
        Builder $query,
        KanbanConfig $config
    ) {
        parent::__construct($config);
        
        $this->baseQuery = $query;
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
} 
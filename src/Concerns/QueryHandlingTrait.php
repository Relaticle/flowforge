<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Trait for handling query operations in the Kanban adapter.
 */
trait QueryHandlingTrait
{
    /**
     * Get a new query builder, cloned from the base query.
     */
    protected function newQuery(): Builder
    {
        return $this->getQuery()->clone();
    }

    /**
     * Find a model by its ID.
     *
     * @param  mixed  $id  The model ID
     */
    public function getModelById(mixed $id): ?Model
    {
        try {
            // Use the adapter's base query to ensure proper scoping and relationships
            $model = $this->newQuery()->find($id);
            
            // If not found in the scoped query, try a direct find as fallback
            if (!$model) {
                $model = $this->getQuery()->getModel()::query()->find($id);
            }
            
            return $model;
        } catch (\Exception $e) {
            // If anything fails, return null to prevent errors
            return null;
        }
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
     * @param  string|int  $columnId  The column ID
     * @param  int  $limit  The number of items to return
     */
    public function getItemsForColumn(string | int $columnId, int $limit = 10): Collection
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
     * @param  string|int  $columnId  The column ID
     */
    public function getColumnItemsCount(string | int $columnId): int
    {
        $columnField = $this->config->getColumnField();

        return $this->newQuery()
            ->where($columnField, $columnId)
            ->count();
    }
}

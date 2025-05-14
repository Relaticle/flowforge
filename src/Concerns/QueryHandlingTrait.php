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
        return clone $this->baseQuery;
    }

    /**
     * Find a model by its ID.
     *
     * @param  mixed  $id  The model ID
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
    
    /**
     * Get all items with filtering applied.
     *
     * @param  array  $filters  Associative array of field => value filters
     */
    public function getFilteredItems(array $filters = []): Collection
    {
        $query = $this->newQuery();
        $orderField = $this->config->getOrderField();
        
        // Apply all filters to the query
        $query = $this->applyFiltersToQuery($query, $filters);

        if ($orderField !== null) {
            $query->orderBy($orderField);
        }

        $models = $query->get();

        return $this->formatCardsForDisplay($models);
    }
    
    /**
     * Get items for a specific column with filtering.
     *
     * @param  string|int  $columnId  The column ID
     * @param  int  $limit  The number of items to return
     * @param  array  $filters  Associative array of field => value filters
     */
    public function getFilteredItemsForColumn(string | int $columnId, int $limit = 10, array $filters = []): Collection
    {
        $columnField = $this->config->getColumnField();
        $orderField = $this->config->getOrderField();

        $query = $this->newQuery()->where($columnField, $columnId);
        
        // Apply all filters to the query
        $query = $this->applyFiltersToQuery($query, $filters);

        if ($orderField !== null) {
            $query->orderBy($orderField);
        }

        $models = $query->limit($limit)->get();

        return $this->formatCardsForDisplay($models);
    }
    
    /**
     * Get the count of filtered items in a specific column.
     *
     * @param  string|int  $columnId  The column ID
     * @param  array  $filters  Associative array of field => value filters
     */
    public function getFilteredColumnItemsCount(string | int $columnId, array $filters = []): int
    {
        $columnField = $this->config->getColumnField();

        $query = $this->newQuery()->where($columnField, $columnId);
        
        // Apply all filters to the query
        $query = $this->applyFiltersToQuery($query, $filters);

        return $query->count();
    }
    
    /**
     * Apply filters to a query builder instance.
     *
     * @param  Builder  $query  The query builder
     * @param  array  $filters  Associative array of field => value filters
     * @return Builder The modified query builder
     */
    protected function applyFiltersToQuery(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            
            // Get field configuration if available
            $fieldConfig = $this->config->getFilterableFields()[$field] ?? null;
            
            // If no field configuration is available, use simple equals comparison
            if (!$fieldConfig || !isset($fieldConfig['operator'])) {
                $query->where($field, $value);
                continue;
            }
            
            // Handle different operators based on field configuration
            switch ($fieldConfig['operator']) {
                case 'contains':
                    $query->where($field, 'LIKE', "%{$value}%");
                    break;
                case 'starts_with':
                    $query->where($field, 'LIKE', "{$value}%");
                    break;
                case 'ends_with':
                    $query->where($field, 'LIKE', "%{$value}");
                    break;
                case 'greater_than':
                    $query->where($field, '>', $value);
                    break;
                case 'less_than':
                    $query->where($field, '<', $value);
                    break;
                case 'greater_than_or_equal':
                    $query->where($field, '>=', $value);
                    break;
                case 'less_than_or_equal':
                    $query->where($field, '<=', $value);
                    break;
                case 'in':
                    if (is_array($value)) {
                        $query->whereIn($field, $value);
                    }
                    break;
                case 'not_in':
                    if (is_array($value)) {
                        $query->whereNotIn($field, $value);
                    }
                    break;
                default:
                    // Default to equals
                    $query->where($field, $value);
                    break;
            }
        }
        
        return $query;
    }
}

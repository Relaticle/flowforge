<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\Flowforge\Config\KanbanConfig;

/**
 * Interface for Kanban board adapters.
 *
 * Adapters are responsible for managing data operations between the Kanban board
 * and underlying data sources. This interface defines a clear contract for all adapters.
 */
interface KanbanAdapterInterface
{
    /**
     * Get the configuration for this adapter.
     */
    public function getConfig(): KanbanConfig;

    /**
     * Find a model by its ID.
     *
     * @param  mixed  $id  The model ID
     */
    public function getModelById(mixed $id): ?Model;

    /**
     * Get all items for the Kanban board.
     */
    public function getItems(): Collection;

    /**
     * Get items for a specific column with pagination.
     *
     * @param  string|int  $columnId  The column ID
     * @param  int  $limit  The number of items to return
     */
    public function getItemsForColumn(string | int $columnId, int $limit = 10): Collection;

    /**
     * Get the total count of items for a specific column.
     *
     * @param  string|int  $columnId  The column ID
     */
    public function getColumnItemsCount(string | int $columnId): int;

    /**
     * Update the order of records in a column.
     *
     * @param  string|int  $columnId  The column ID
     * @param  array<int, mixed>  $recordIds  The record IDs in their new order
     */
    public function updateRecordsOrderAndColumn(string | int $columnId, array $recordIds): bool;
    
    /**
     * Get items for a specific column with filtering.
     *
     * @param  string|int  $columnId  The column ID
     * @param  int  $limit  The number of items to return
     * @param  array  $filters  Associative array of field => value filters
     */
    public function getFilteredItemsForColumn(string | int $columnId, int $limit = 10, array $filters = []): Collection;

    /**
     * Get all items with filtering applied.
     *
     * @param  array  $filters  Associative array of field => value filters
     */
    public function getFilteredItems(array $filters = []): Collection;

    /**
     * Get the count of filtered items in a specific column.
     *
     * @param  string|int  $columnId  The column ID
     * @param  array  $filters  Associative array of field => value filters
     */
    public function getFilteredColumnItemsCount(string | int $columnId, array $filters = []): int;
}

<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait for handling CRUD operations in the Kanban adapter.
 */
trait CrudOperationsTrait
{
    /**
     * Create a new record with the given attributes.
     *
     * @param  array<string, mixed>  $attributes  The record attributes
     */
    public function createRecord(array $attributes): ?Model
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
     * Update an existing record with the given attributes.
     *
     * @param  Model  $record  The record to update
     * @param  array<string, mixed>  $attributes  The record attributes to update
     */
    public function updateRecord(Model $record, array $attributes): bool
    {
        $record->fill($attributes);

        return $record->save();
    }

    /**
     * Delete an existing record.
     *
     * @param  Model  $record  The record to delete
     */
    public function deleteRecord(Model $record): bool
    {
        return $record->delete();
    }

    /**
     * Update the order of records in a column.
     *
     * @param  string|int  $columnId  The column ID
     * @param  array<int, mixed>  $recordIds  The record IDs in their new order
     * @return bool Whether the operation was successful
     */
    public function updateRecordsOrderAndColumn(string | int $columnId, array $recordIds): bool
    {
        $orderField = $this->config->getOrderField();
        $columnField = $this->config->getColumnField();
        $success = true;

        foreach ($recordIds as $index => $recordId) {
            $model = $this->getModelById($recordId);

            if ($model === null) {
                $success = false;

                continue;
            }

            if ($orderField !== null) {
                $model->{$orderField} = $index + 1;
            }
            $model->{$columnField} = $columnId;

            if (! $model->save()) {
                $success = false;
            }
        }

        return $success;
    }
}

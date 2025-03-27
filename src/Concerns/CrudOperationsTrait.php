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
     * Create a new card with the given attributes.
     *
     * @param  array<string, mixed>  $attributes  The card attributes
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
     * Update an existing card with the given attributes.
     *
     * @param  Model  $record  The card to update
     * @param  array<string, mixed>  $attributes  The card attributes to update
     */
    public function updateRecord(Model $record, array $attributes): bool
    {
        $record->fill($attributes);

        return $record->save();
    }

    /**
     * Delete an existing card.
     *
     * @param  Model  $card  The card to delete
     */
    public function deleteRecord(Model $card): bool
    {
        return $card->delete();
    }

    /**
     * Update the order of cards in a column.
     *
     * @param  string|int  $columnId  The column ID
     * @param  array<int, mixed>  $cardIds  The card IDs in their new order
     * @return bool Whether the operation was successful
     */
    public function updateRecordsOrderAndColumn(string | int $columnId, array $cardIds): bool
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

            if (! $model->save()) {
                $success = false;
            }
        }

        return $success;
    }
}

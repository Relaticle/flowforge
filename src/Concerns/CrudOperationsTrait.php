<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Trait for handling CRUD operations in the Kanban adapter.
 */
trait CrudOperationsTrait
{
    /**
     * Create a new record with the given attributes.
     *
     * @param Form $form
     * @param mixed $currentColumn
     * @return Model|null
     */
    public function createRecord(Form $form, mixed $currentColumn): ?Model
    {
        $model = $this->baseQuery->getModel()->newInstance();

        $model->fill([
            ...$form->getState(),
            $this->config->getColumnField() => $currentColumn,
        ]);

        if ($model->save()) {
            $form->model($model)->saveRelationships();

            return $model;
        }

        return null;
    }

    /**
     * Update an existing record with the given attributes.
     *
     * @param Model $record The record to update
     * @param Form $form
     * @return bool
     */
    public function updateRecord(Model $record, Form $form): bool
    {
        $record->fill($form->getState());

        $form->model($record)->saveRelationships();

        return $record->save();
    }

    /**
     * Delete an existing record.
     *
     * @param Model $record The record to delete
     */
    public function deleteRecord(Model $record): bool
    {
        return $record->delete();
    }

    /**
     * Update the order of records in a column.
     *
     * @param string|int $columnId The column ID
     * @param array<int, mixed> $recordIds The record IDs in their new order
     * @return bool Whether the operation was successful
     */
    public function updateRecordsOrderAndColumn(string|int $columnId, array $recordIds): bool
    {
        $orderField = $this->config->getOrderField();
        $columnField = $this->config->getColumnField();

        $startOrder = 1;

        foreach ($recordIds as $id) {
            $model = $this->getModelById($id);
            $primaryKeyColumn = $model->getQualifiedKeyName();

            $attributes = [
                $columnField => $columnId,
            ];

            if ($orderField !== null) {
                $attributes[$orderField] = $startOrder++;
            }

            $model::withoutGlobalScope(SoftDeletingScope::class)
                ->where($primaryKeyColumn, $id)
                ->update($attributes);
        }

        return true;
    }
}

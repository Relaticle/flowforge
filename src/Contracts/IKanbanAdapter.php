<?php

namespace Relaticle\Flowforge\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Interface for Kanban board adapters.
 * 
 * Adapters are responsible for the interaction between the Kanban board and models.
 */
interface IKanbanAdapter
{
    /**
     * Get the model instance.
     *
     * @return Model
     */
    public function getModel(): Model;

    /**
     * Get the status field name for the model.
     *
     * @return string
     */
    public function getStatusField(): string;

    /**
     * Get all available status values for the model.
     *
     * @return array<string, string>
     */
    public function getStatusValues(): array;

    /**
     * Get all items for the Kanban board.
     *
     * @return Collection
     */
    public function getItems(): Collection;

    /**
     * Update the status of an item.
     *
     * @param Model $model
     * @param string $status
     * @return bool
     */
    public function updateStatus(Model $model, string $status): bool;

    /**
     * Get the attributes to display on the card.
     *
     * @return array<string, string>
     */
    public function getCardAttributes(): array;

    /**
     * Get the title attribute for the card.
     *
     * @return string
     */
    public function getTitleAttribute(): string;

    /**
     * Get the description attribute for the card.
     *
     * @return string|null
     */
    public function getDescriptionAttribute(): ?string;
}
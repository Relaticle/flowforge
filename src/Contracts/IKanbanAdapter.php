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
     * Get the model class.
     *
     * @return string
     */
    public function getModel(): string;

    /**
     * Find a model by its ID.
     *
     * @param mixed $id The model ID
     * @return Model|null
     */
    public function getModelById($id): ?Model;

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

    /**
     * Create a new card with the given attributes.
     *
     * @param array<string, mixed> $attributes The card attributes
     * @return Model|null
     */
    public function createCard(array $attributes): ?Model;

    /**
     * Update an existing card with the given attributes.
     *
     * @param Model $card The card to update
     * @param array<string, mixed> $attributes The card attributes to update
     * @return bool
     */
    public function updateCard(Model $card, array $attributes): bool;

    /**
     * Delete an existing card.
     *
     * @param Model $card The card to delete
     * @return bool
     */
    public function deleteCard(Model $card): bool;

    /**
     * Get the order field name for the model.
     *
     * @return string|null
     */
    public function getOrderField(): ?string;

    /**
     * Update the status and order of an item.
     *
     * @param string|int $columnId
     * @param array $cards
     * @return bool
     */
    public function updateColumnCards(string|int $columnId, array $cards): bool;
}

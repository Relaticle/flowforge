<?php

namespace Relaticle\Flowforge\Contracts;

use Filament\Forms\Form;
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
     * Get items for a specific status with pagination.
     *
     * @param string $status The status value
     * @param int $limit The number of items to return
     * @return Collection
     */
    public function getItemsForStatus(string $status, int $limit = 10): Collection;

    /**
     * Get the total count of items for a specific status.
     *
     * @param string $status The status value
     * @return int
     */
    public function getTotalItemsCount(string $status): int;

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
     * Get the form class for creating cards.
     *
     * @param Form $form
     * @return Form
     */
    public function getCreateForm(Form $form, mixed $activeColumn): Form;

    /**
     * Get the form class for creating cards.
     *
     * @param Form $form
     * @return Form
     */
    public function getEditForm(Form $form): Form;


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

    /**
     * Get the color for each status.
     * If not implemented or null is returned for a status, default colors from config will be used.
     *
     * @return array<string, string>|null
     */
    public function getStatusColors(): ?array;
}

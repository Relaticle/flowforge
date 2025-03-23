<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Contracts;

use Filament\Forms\Form;
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
     * @param mixed $id The model ID
     */
    public function getModelById(mixed $id): ?Model;
    
    /**
     * Get all items for the Kanban board.
     */
    public function getItems(): Collection;
    
    /**
     * Get items for a specific column with pagination.
     *
     * @param string|int $columnId The column ID
     * @param int $limit The number of items to return
     */
    public function getItemsForColumn(string|int $columnId, int $limit = 10): Collection;
    
    /**
     * Get the total count of items for a specific column.
     *
     * @param string|int $columnId The column ID
     */
    public function getColumnItemsCount(string|int $columnId): int;
    
    /**
     * Move a card to a different column.
     *
     * @param Model $card The card to move
     * @param string|int $columnId The target column ID
     */
    public function moveCardToColumn(Model $card, string|int $columnId): bool;
    
    /**
     * Get the form for creating cards.
     *
     * @param Form $form The form instance
     * @param mixed $activeColumn The active column
     */
    public function getCreateForm(Form $form, mixed $activeColumn): Form;
    
    /**
     * Get the form for editing cards.
     *
     * @param Form $form The form instance
     */
    public function getEditForm(Form $form): Form;
    
    /**
     * Create a new card with the given attributes.
     *
     * @param array<string, mixed> $attributes The card attributes
     */
    public function createCard(array $attributes): ?Model;
    
    /**
     * Update an existing card with the given attributes.
     *
     * @param Model $card The card to update
     * @param array<string, mixed> $attributes The card attributes to update
     */
    public function updateCard(Model $card, array $attributes): bool;
    
    /**
     * Delete an existing card.
     *
     * @param Model $card The card to delete
     */
    public function deleteCard(Model $card): bool;
    
    /**
     * Update the order of cards in a column.
     *
     * @param string|int $columnId The column ID
     * @param array<int, mixed> $cardIds The card IDs in their new order
     */
    public function reorderCardsInColumn(string|int $columnId, array $cardIds): bool;
} 
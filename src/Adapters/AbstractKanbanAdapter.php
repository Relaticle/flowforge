<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Adapters;

use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Relaticle\Flowforge\Config\KanbanConfig;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;

/**
 * Abstract implementation of the Kanban adapter interface.
 *
 * This class provides shared functionality for all Eloquent-based Kanban adapters,
 * including configuration management and common form building logic.
 */
abstract class AbstractKanbanAdapter implements KanbanAdapterInterface
{
    /**
     * Create a new abstract Kanban adapter instance.
     */
    public function __construct(
        protected readonly KanbanConfig $config
    ) {
    }

    /**
     * Get the configuration for this adapter.
     */
    public function getConfig(): KanbanConfig
    {
        return $this->config;
    }

    /**
     * Get the form for creating cards.
     *
     * @param Form $form The form instance
     * @param mixed $activeColumn The active column
     */
    public function getCreateForm(Form $form, mixed $activeColumn): Form
    {
        $callback = $this->config->getCreateFormCallback();

        if ($callback !== null && is_callable($callback)) {
            return $callback($form, $activeColumn);
        }

        // Default implementation can be overridden by child classes
        return $form;
    }

    /**
     * Get the form for editing cards.
     *
     * @param Form $form The form instance
     */
    public function getEditForm(Form $form): Form
    {
        // By default, use the same form as create
        return $this->getCreateForm($form, null);
    }

    /**
     * Format a model as a card for display.
     *
     * @param Model $model The model to format
     * @return array<string, mixed> The formatted card
     */
    protected function formatCardForDisplay(Model $model): array
    {
        $titleField = $this->config->getTitleField();
        $descriptionField = $this->config->getDescriptionField();
        $cardAttributes = $this->config->getCardAttributes();
        $columnField = $this->config->getColumnField();

        $card = [
            'id' => $model->getKey(),
            'title' => $model->{$titleField},
            'column' => $model->{$columnField},
        ];

        if ($descriptionField !== null && isset($model->{$descriptionField})) {
            $card['description'] = $model->{$descriptionField};
        }

        foreach ($cardAttributes as $key => $label) {
            $field = is_string($key) ? $key : $label;
            $card['attributes'][$field] = [
                'label' => is_string($key) ? $label : $field,
                'value' => $model->{$field},
            ];
        }

        return $card;
    }

    /**
     * Format a collection of models as cards for display.
     *
     * @param Collection $models The models to format
     * @return Collection The formatted cards
     */
    protected function formatCardsForDisplay(Collection $models): Collection
    {
        return $models->map(fn (Model $model) => $this->formatCardForDisplay($model));
    }

    /**
     * Update the order of cards in a column.
     *
     * @param string|int $columnId The column ID
     * @param array<int, mixed> $cardIds The card IDs in their new order
     * @return bool Whether the operation was successful
     */
    public function reorderCardsInColumn(string|int $columnId, array $cardIds): bool
    {
        $orderField = $this->config->getOrderField();

        if ($orderField === null) {
            return false;
        }

        $columnField = $this->config->getColumnField();
        $success = true;

        foreach ($cardIds as $index => $cardId) {
            $model = $this->getModelById($cardId);

            if ($model === null) {
                $success = false;
                continue;
            }

            $model->{$orderField} = $index + 1;
            $model->{$columnField} = $columnId;
            
            if (!$model->save()) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Move a card to a different column.
     *
     * @param Model $card The card to move
     * @param string|int $columnId The target column ID
     * @return bool Whether the operation was successful
     */
    public function moveCardToColumn(Model $card, string|int $columnId): bool
    {
        $columnField = $this->config->getColumnField();
        $card->{$columnField} = $columnId;
        
        return $card->save();
    }
} 
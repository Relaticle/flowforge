<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Trait for handling card formatting operations in the Kanban adapter.
 */
trait CardFormattingTrait
{
    /**
     * Format a model as a card for display.
     *
     * @param  Model  $model  The model to format
     * @return array<string, mixed> The formatted card
     */
    protected function formatCardForDisplay(Model $model): array
    {
        $titleField = $this->config->getTitleField();
        $descriptionField = $this->config->getDescriptionField();
        $priorityField = $this->config->getPriorityField();
        $cardAttributes = $this->config->getCardAttributes();
        $cardAttributeColors = $this->config->getCardAttributeColors();
        $cardAttributeIcons = $this->config->getCardAttributeIcons();
        $columnField = $this->config->getColumnField();

        $card = [
            'id' => $model->getKey(),
            'title' => data_get($model, $titleField),
            'column' => data_get($model, $columnField),
        ];

        if ($descriptionField !== null) {
            $card['description'] = data_get($model, $descriptionField);
        }

        if ($priorityField !== null) {
            $card['priority'] = data_get($model, $priorityField);
        }

        foreach ($cardAttributes as $key => $label) {
            $field = is_string($key) ? $key : $label;
            $card['attributes'][$field] = [
                'label' => is_string($key) ? $label : $field,
                'value' => data_get($model, $field),
                'color' => $cardAttributeColors[$field] ?? null,
                'icon' => $cardAttributeIcons[$field] ?? null,
            ];
        }

        return $card;
    }

    /**
     * Format a collection of models as cards for display.
     *
     * @param  Collection  $models  The models to format
     * @return Collection The formatted cards
     */
    protected function formatCardsForDisplay(Collection $models): Collection
    {
        return $models->map(fn (Model $model) => $this->formatCardForDisplay($model));
    }
}

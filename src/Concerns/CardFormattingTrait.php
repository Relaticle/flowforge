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
     * @param  Collection  $models  The models to format
     * @return Collection The formatted cards
     */
    protected function formatCardsForDisplay(Collection $models): Collection
    {
        return $models->map(fn (Model $model) => $this->formatCardForDisplay($model));
    }
}

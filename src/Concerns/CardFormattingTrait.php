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
        $columnField = $this->config->getColumnField();

        $card = [
            'id' => $model->getKey(),
            'title' => data_get($model, $titleField),
            'column' => data_get($model, $columnField),
        ];

        if ($descriptionField !== null) {
            $card['description'] = data_get($model, $descriptionField);
        }

        // Use card properties for formatted display
        if ($cardProperties = $this->config->getCardProperties()) {
            foreach ($cardProperties as $property) {
                $name = $property->getName();
                $value = $property->getFormattedState($model);

                if ($value !== null && $value !== '') {
                    $card['attributes'][$name] = [
                        'label' => $property->getLabel(),
                        'value' => $value,
                        'color' => $property->getColor(),
                        'icon' => $property->getIcon(),
                        'iconColor' => $property->getIconColor(),
                    ];
                }
            }
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

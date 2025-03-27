<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Filament\Forms\Form;
use Relaticle\Flowforge\Config\KanbanConfig;

/**
 * Trait for handling form operations in the Kanban adapter.
 */
trait FormHandlingTrait
{
    /**
     * Get the form for creating cards.
     *
     * @param  Form  $form  The form instance
     * @param  mixed  $currentColumn  The current column
     */
    public function getCreateForm(Form $form, mixed $currentColumn): Form
    {
        // Fall back to default create form implementation
        $titleField = $this->config->getTitleField();
        $descriptionField = $this->config->getDescriptionField();

        $schema = KanbanConfig::getDefaultCreateFormSchema($titleField, $descriptionField);

        // For create form, set the column field value based on the current column
        // but hide it from the form as it's determined by where the user clicked
        if ($currentColumn) {
            $form->statePath(null);
        }

        return $form->schema($schema);
    }

    /**
     * Get the form for editing cards.
     *
     * @param  Form  $form  The form instance
     */
    public function getEditForm(Form $form): Form
    {
        // Fall back to default edit form implementation
        $titleField = $this->config->getTitleField();
        $descriptionField = $this->config->getDescriptionField();
        $columnField = $this->config->getColumnField();
        $columnValues = $this->config->getColumnValues();

        $schema = KanbanConfig::getDefaultEditFormSchema(
            $titleField,
            $descriptionField,
            $columnField,
            $columnValues
        );

        return $form->schema($schema);
    }
}

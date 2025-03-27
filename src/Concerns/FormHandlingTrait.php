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
     * @param Form $form The form instance
     * @param mixed $activeColumn The active column
     */
    public function getCreateForm(Form $form, mixed $activeColumn): Form
    {
        // Fall back to default create form implementation
        $titleField = $this->config->getTitleField();
        $descriptionField = $this->config->getDescriptionField();

        $schema = KanbanConfig::getDefaultCreateFormSchema($titleField, $descriptionField);

        // For create form, set the column field value based on the active column
        // but hide it from the form as it's determined by where the user clicked
        if ($activeColumn) {
            $form->statePath(null);
        }

        return $form->schema($schema);
    }

    /**
     * Get the form for editing cards.
     *
     * @param Form $form The form instance
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

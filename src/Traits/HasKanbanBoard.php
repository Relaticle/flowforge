<?php

namespace Relaticle\Flowforge\Traits;

use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

trait HasKanbanBoard
{
    /**
     * Get the Kanban adapter for this model.
     *
     * @param string $statusField
     * @param array<string, string> $statusValues
     * @param string $titleAttribute
     * @param string|null $descriptionAttribute
     * @param array<string, string> $cardAttributes
     * @return IKanbanAdapter
     */
    public function getKanbanAdapter(
        string $statusField = 'status',
        array $statusValues = [],
        string $titleAttribute = 'name',
        ?string $descriptionAttribute = null,
        array $cardAttributes = []
    ): IKanbanAdapter {
        return new DefaultKanbanAdapter(
            $this,
            $statusField,
            $statusValues,
            $titleAttribute,
            $descriptionAttribute,
            $cardAttributes
        );
    }

    /**
     * Create a new Kanban adapter with the given configuration.
     *
     * @param string $statusField
     * @param array<string, string> $statusValues
     * @param string $titleAttribute
     * @param string|null $descriptionAttribute
     * @param array<string, string> $cardAttributes
     * @return IKanbanAdapter
     */
    public static function kanban(
        string $statusField = 'status',
        array $statusValues = [],
        string $titleAttribute = 'name',
        ?string $descriptionAttribute = null,
        array $cardAttributes = []
    ): IKanbanAdapter {
        return (new static())->getKanbanAdapter(
            $statusField,
            $statusValues,
            $titleAttribute,
            $descriptionAttribute,
            $cardAttributes
        );
    }
}

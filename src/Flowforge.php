<?php

namespace Relaticle\Flowforge;

use Illuminate\Database\Eloquent\Model;
use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

class Flowforge
{
    /**
     * Create a new Kanban adapter for the given model.
     *
     * @param  array<string, string>  $statusValues
     * @param  array<string, string>  $cardAttributes
     */
    public function createAdapter(
        Model $model,
        string $statusField = 'status',
        array $statusValues = [],
        string $titleAttribute = 'name',
        ?string $descriptionAttribute = null,
        array $cardAttributes = []
    ): IKanbanAdapter {
        return new DefaultKanbanAdapter(
            $model,
            $statusField,
            $statusValues,
            $titleAttribute,
            $descriptionAttribute,
            $cardAttributes
        );
    }
}

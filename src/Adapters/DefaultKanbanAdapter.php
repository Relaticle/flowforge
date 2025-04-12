<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Adapters;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Wireable;
use Relaticle\Flowforge\Concerns\CardFormattingTrait;
use Relaticle\Flowforge\Concerns\CrudOperationsTrait;
use Relaticle\Flowforge\Concerns\QueryHandlingTrait;
use Relaticle\Flowforge\Config\KanbanConfig;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;

class DefaultKanbanAdapter implements KanbanAdapterInterface, Wireable
{
    use CardFormattingTrait;
    use CrudOperationsTrait;
    use QueryHandlingTrait;

    /**
     * The base Eloquent query builder.
     */
    public Builder $baseQuery;

    /**
     * Create a new abstract Kanban adapter instance.
     */
    public function __construct(
        Builder $query,
        public KanbanConfig $config
    ) {
        $this->baseQuery = $query;
    }

    /**
     * Get the configuration for this adapter.
     */
    public function getConfig(): KanbanConfig
    {
        return $this->config;
    }

    public function toLivewire(): array
    {
        return [
            'query' => \EloquentSerialize::serialize($this->baseQuery),
            'config' => $this->config,
        ];
    }

    public static function fromLivewire($value): static
    {
        $query = \EloquentSerialize::unserialize($value['query']);

        return new static($query, $value['config']);
    }
}

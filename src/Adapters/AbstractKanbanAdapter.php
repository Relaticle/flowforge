<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Adapters;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Wireable;
use Relaticle\Flowforge\Config\KanbanConfig;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;
use Relaticle\Flowforge\Concerns\CardFormattingTrait;
use Relaticle\Flowforge\Concerns\CrudOperationsTrait;
use Relaticle\Flowforge\Concerns\FormHandlingTrait;
use Relaticle\Flowforge\Concerns\QueryHandlingTrait;

/**
 * Abstract base class for Kanban adapters.
 *
 * This class has been refactored to use traits for better separation of concerns:
 * - QueryHandlingTrait: For database query operations
 * - CardFormattingTrait: For formatting models as cards
 * - CrudOperationsTrait: For card CRUD operations
 * - FormHandlingTrait: For form generation and handling
 */
abstract class AbstractKanbanAdapter implements KanbanAdapterInterface, Wireable
{
    use QueryHandlingTrait;
    use CardFormattingTrait;
    use CrudOperationsTrait;
    use FormHandlingTrait;

    /**
     * The base Eloquent query builder.
     *
     * @var Builder
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

    /**
     * @return array
     */
    public function toLivewire(): array
    {
        return [
            'query' => \EloquentSerialize::serialize($this->baseQuery),
            'config' => $this->config->toArray(),
        ];
    }

    /**
     * @param $value
     * @return static
     */
    public static function fromLivewire($value): static
    {
        $query = \EloquentSerialize::unserialize($value['query']);
        $config = new KanbanConfig(...$value['config']);

        return new static($query, $config);
    }
}

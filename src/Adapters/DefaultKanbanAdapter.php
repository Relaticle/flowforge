<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Adapters;

use Closure;
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
     * The query closure that returns a fresh query builder.
     */
    protected Closure $queryCallback;

    /**
     * The model class name for reconstruction.
     */
    protected string $modelClass;

    /**
     * The connection name for reconstruction.
     */
    protected ?string $connectionName;

    /**
     * Create a new abstract Kanban adapter instance.
     */
    public function __construct(
        Builder $query,
        public KanbanConfig $config
    ) {
        $this->modelClass = get_class($query->getModel());
        $this->connectionName = $query->getModel()->getConnectionName();

        // Store a closure that can recreate the query
        $this->queryCallback = fn () => $query->clone();
    }

    /**
     * Get a fresh query builder instance.
     */
    protected function getQuery(): Builder
    {
        return ($this->queryCallback)();
    }

    /**
     * Get the base query (for backwards compatibility).
     */
    public function getBaseQuery(): Builder
    {
        return $this->getQuery();
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
            'modelClass' => $this->modelClass,
            'connectionName' => $this->connectionName,
            'config' => $this->config,
        ];
    }

    public static function fromLivewire($value): static
    {
        $modelClass = $value['modelClass'];
        $connectionName = $value['connectionName'];

        // Recreate the query from the model class and connection
        $model = new $modelClass;
        if ($connectionName) {
            $model->setConnection($connectionName);
        }

        $query = $model->newQuery();

        return new static($query, $value['config']);
    }
}

<?php

namespace Relaticle\Flowforge\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Relaticle\Flowforge\Adapters\KanbanAdapterFactory;
use Relaticle\Flowforge\Config\KanbanConfig;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;

abstract class KanbanBoardPage extends Page
{
    protected static string $view = 'flowforge::filament.pages.kanban-board-page';

    /**
     * The subject for the Kanban board (model class, query, or relation).
     */
    protected mixed $subject;

    /**
     * The Kanban configuration object.
     */
    protected KanbanConfig $config;

    /**
     * The Kanban adapter instance.
     */
    protected ?KanbanAdapterInterface $adapter = null;

    /**
     * Custom adapter callback.
     */
    protected mixed $adapterCallback = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = new KanbanConfig();
        parent::__construct();
    }

    /**
     * Mount the page.
     */
    public function mount(): void
    {
        // This method can be overridden by child classes
    }

    /**
     * Set the subject for the Kanban board.
     *
     * @param EloquentBuilder|Relation|string $subject
     */
    public function for(EloquentBuilder|Relation|string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set the field that stores the column value.
     *
     * @param string $field
     */
    public function columnField(string $field): static
    {
        $this->config = $this->config->withColumnField($field);

        return $this;
    }

    /**
     * Set the column statuses with labels for the Kanban board.
     *
     * @param array<string, string> $columns
     */
    public function columns(array $columns): static
    {
        $this->config = $this->config->withColumnValues($columns);

        return $this;
    }

    /**
     * Set the title field for the Kanban cards.
     *
     * @param string $field
     */
    public function titleField(string $field): static
    {
        $this->config = $this->config->withTitleField($field);

        return $this;
    }

    /**
     * Set the description field for the Kanban cards.
     *
     * @param string $field
     */
    public function descriptionField(string $field): static
    {
        $this->config = $this->config->withDescriptionField($field);

        return $this;
    }

    /**
     * Set the card attributes for the Kanban cards.
     *
     * @param array<string, string> $attributes
     */
    public function cardAttributes(array $attributes): static
    {
        $this->config = $this->config->withCardAttributes($attributes);

        return $this;
    }

    /**
     * Set the column colors for the Kanban board.
     *
     * @param array<string, string> $colors
     */
    public function columnColors(array $colors): static
    {
        $this->config = $this->config->withColumnColors($colors);

        return $this;
    }

    /**
     * Set the order field for the Kanban board.
     *
     * @param string $field
     */
    public function orderField(string $field): static
    {
        $this->config = $this->config->withOrderField($field);

        return $this;
    }

    /**
     * Set the create form callback for the Kanban board.
     *
     * @param callable $callback
     */
    public function createForm(callable $callback): static
    {
        $this->config = $this->config->withCreateFormCallback($callback);

        return $this;
    }

    /**
     * Set the label for individual cards.
     *
     * @param string $label
     */
    public function cardLabel(string $label): static
    {
        $this->config = $this->config->withCardLabel($label);

        return $this;
    }

    /**
     * Set the plural label for collection of cards.
     *
     * @param string $label
     */
    public function pluralCardLabel(string $label): static
    {
        $this->config = $this->config->withPluralCardLabel($label);

        return $this;
    }

    /**
     * Set a custom adapter for the Kanban board.
     *
     * @param KanbanAdapterInterface $adapter
     */
    public function withCustomAdapter(KanbanAdapterInterface $adapter): static
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * Register a callback to modify the auto-created adapter.
     *
     * @param callable $callback
     */
    public function withAdapterCallback(callable $callback): static
    {
        $this->adapterCallback = $callback;

        return $this;
    }

    /**
     * Set multiple configuration values at once.
     *
     * @param array<string, mixed> $config
     */
    public function withConfiguration(array $config): static
    {
        $this->config = $this->config->with($config);

        return $this;
    }

    /**
     * Enable searchable for specific fields.
     *
     * @param array<int, string> $fields
     */
    public function withSearchable(array $fields): static
    {
        // This method would be implemented in the component
        return $this;
    }

    /**
     * Get the Kanban adapter.
     *
     * @throws \InvalidArgumentException If the subject is not set
     */
    public function getAdapter(): KanbanAdapterInterface
    {
        if ($this->adapter !== null) {
            return $this->adapter;
        }

        if (!isset($this->subject)) {
            throw new \InvalidArgumentException(
                'You must specify a subject using the for() method before getting the adapter.'
            );
        }

        // Create the adapter using the factory
        $adapter = KanbanAdapterFactory::create($this->subject, $this->config);

        // Apply any custom adapter modifications
        if ($this->adapterCallback !== null && is_callable($this->adapterCallback)) {
            $adapter = call_user_func($this->adapterCallback, $adapter);
        }

        $this->adapter = $adapter;

        return $adapter;
    }
}

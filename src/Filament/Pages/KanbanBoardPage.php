<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Filament\Pages;

use Filament\Pages\Page;
use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;
use Relaticle\Flowforge\Config\KanbanConfig;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;

abstract class KanbanBoardPage extends Page
{
    protected static string $view = 'flowforge::filament.pages.kanban-board-page';

    /**
     * The Kanban configuration object.
     */
    protected KanbanConfig $config;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = new KanbanConfig;
    }

    public function updating()
    {
        info('update');
    }

    /**
     * Set the field that stores the column value.
     *
     * @param string $field
     * @return KanbanBoardPage
     */
    public function columnField(string $field): static
    {
        $this->config = $this->config->withColumnField($field);

        return $this;
    }

    /**
     * Set the column statuses with labels for the Kanban board.
     *
     * @param  array<string, string>  $columns
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
     * @return KanbanBoardPage
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
     * @return KanbanBoardPage
     */
    public function descriptionField(string $field): static
    {
        $this->config = $this->config->withDescriptionField($field);

        return $this;
    }

    /**
     * Set the card attributes for the Kanban cards.
     *
     * @param  array<string, string>  $attributes
     */
    public function cardAttributes(array $attributes): static
    {
        $this->config = $this->config->withCardAttributes($attributes);

        return $this;
    }

    /**
     * Set the column colors for the Kanban board.
     *
     * @param  array<string, string>  $colors
     */
    public function columnColors(?array $colors = null): static
    {
        $this->config = $this->config->withColumnColors($colors === null ? true : $colors);

        return $this;
    }

    /**
     * Set the order field for the Kanban board.
     *
     * @param string $field
     * @return KanbanBoardPage
     */
    public function orderField(string $field): static
    {
        $this->config = $this->config->withOrderField($field);

        return $this;
    }

    /**
     * Set the label for individual cards.
     *
     * @param string $label
     * @return KanbanBoardPage
     */
    public function cardLabel(string $label): static
    {
        $this->config = $this->config->withCardLabel($label);

        return $this;
    }

    /**
     * Get the Kanban adapter.
     *
     * @throws \InvalidArgumentException If the subject is not set
     */
    public function getAdapter(): KanbanAdapterInterface
    {
        return new DefaultKanbanAdapter($this->getSubject(), $this->config);
    }
}

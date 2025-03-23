<?php

namespace Relaticle\Flowforge\Filament\Pages;

use Exception;
use Filament\Pages\Page;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

abstract class KanbanBoardPage extends Page
{
    protected static string $view = 'flowforge::filament.pages.kanban-board-page';

    protected string $model;

    protected string $statusField = 'status';

    /**
     * @var array<string, string>
     */
    protected array $statusValues = [];

    protected string $titleAttribute = 'name';

    protected ?string $descriptionAttribute = null;

    /**
     * @var array<string, string>
     */
    protected array $cardAttributes = [];

    /**
     * @var array<string, string>|null
     */
    protected ?array $statusColors = null;

    protected ?string $orderField = null;

    /**
     * @var callable|null
     */
    protected mixed $createFormCallback = null;

    protected ?IKanbanAdapter $adapter = null;

    protected ?string $recordLabel = null;

    protected ?string $pluralRecordLabel = null;

    /**
     * Mount the page.
     */
    public function mount(): void
    {
        // This method can be overridden by child classes
    }

    public function model(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set the status field for the Kanban board.
     */
    public function statusField(string $field): static
    {
        $this->statusField = $field;

        return $this;
    }

    /**
     * Set the status values for the Kanban board.
     *
     * @param  array<string, string>  $values
     */
    public function statusValues(array $values): static
    {
        $this->statusValues = $values;

        return $this;
    }

    /**
     * Set the title attribute for the Kanban cards.
     */
    public function titleAttribute(string $attribute): static
    {
        $this->titleAttribute = $attribute;

        return $this;
    }

    /**
     * Set the description attribute for the Kanban cards.
     */
    public function descriptionAttribute(string $attribute): static
    {
        $this->descriptionAttribute = $attribute;

        return $this;
    }

    /**
     * Set the card attributes for the Kanban cards.
     *
     * @param  array<string, string>  $attributes
     */
    public function cardAttributes(array $attributes): static
    {
        $this->cardAttributes = $attributes;

        return $this;
    }

    /**
     * Set the status colors for the Kanban board columns.
     *
     * @param  array<string, string>  $colors
     */
    public function statusColors(array $colors): static
    {
        $this->statusColors = $colors;

        return $this;
    }

    /**
     * Set the order field for the Kanban board.
     */
    public function orderField(string $field): static
    {
        $this->orderField = $field;

        return $this;
    }

    public function createForm(callable $callback): static
    {
        $this->createFormCallback = $callback;

        return $this;
    }

    /**
     * Set a custom adapter for the Kanban board.
     */
    public function adapter(IKanbanAdapter $adapter): static
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * Set the singular record label for the Kanban items.
     */
    public function recordLabel(string $label): static
    {
        $this->recordLabel = $label;

        return $this;
    }

    /**
     * Set the plural record label for the Kanban items.
     */
    public function pluralRecordLabel(string $label): static
    {
        $this->pluralRecordLabel = $label;

        return $this;
    }

    /**
     * Get the Kanban adapter.
     *
     * @throws Exception
     */
    public function getAdapter(): IKanbanAdapter
    {
        if ($this->adapter) {
            return $this->adapter;
        }

        $model = $this->model;

        if (! class_exists($model)) {
            throw new Exception("Model class {$model} does not exist.");
        }

        // Check if the model uses the HasKanbanBoard trait
        if (method_exists($model, 'getKanbanAdapter')) {
            // Create an instance and configure it
            $instance = new $model;

            // Override default values if they are provided
            if (method_exists($instance, 'setKanbanStatusField') && $this->statusField) {
                $instance->setKanbanStatusField($this->statusField);
            }

            if (method_exists($instance, 'setKanbanStatusValues') && $this->statusValues) {
                $instance->setKanbanStatusValues($this->statusValues);
            }

            if (method_exists($instance, 'setKanbanTitleAttribute') && $this->titleAttribute) {
                $instance->setKanbanTitleAttribute($this->titleAttribute);
            }

            if (method_exists($instance, 'setKanbanDescriptionAttribute') && $this->descriptionAttribute) {
                $instance->setKanbanDescriptionAttribute($this->descriptionAttribute);
            }

            if (method_exists($instance, 'setKanbanCardAttributes') && $this->cardAttributes) {
                $instance->setKanbanCardAttributes($this->cardAttributes);
            }

            if (method_exists($instance, 'setKanbanStatusColors') && $this->statusColors) {
                $instance->setKanbanStatusColors($this->statusColors);
            }

            if (method_exists($instance, 'setKanbanOrderField') && $this->orderField) {
                $instance->setKanbanOrderField($this->orderField);
            }

            if (method_exists($instance, 'setKanbanCreateFormCallback') && $this->createFormCallback) {
                $instance->setKanbanCreateFormCallback($this->createFormCallback);
            }

            if (method_exists($instance, 'setKanbanRecordLabel') && $this->recordLabel) {
                $instance->setKanbanRecordLabel($this->recordLabel);
            }

            if (method_exists($instance, 'setKanbanPluralRecordLabel') && $this->pluralRecordLabel) {
                $instance->setKanbanPluralRecordLabel($this->pluralRecordLabel);
            }

            return $instance->getKanbanAdapter();
        }

        throw new Exception('Model does not use the HasKanbanBoard trait.');
    }
}

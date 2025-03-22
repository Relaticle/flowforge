<?php

namespace Relaticle\Flowforge\Filament\Resources\Pages;

use Filament\Resources\Pages\Page;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

class KanbanBoard extends Page
{
    protected static string $view = 'flowforge::filament.resources.pages.kanban-board';

    /**
     * @var string
     */
    protected string $statusField = 'status';

    /**
     * @var array<string, string>
     */
    protected array $statusValues = [];

    /**
     * @var string
     */
    protected string $titleAttribute = 'name';

    /**
     * @var string|null
     */
    protected ?string $descriptionAttribute = null;

    /**
     * @var array<string, string>
     */
    protected array $cardAttributes = [];

    /**
     * @var IKanbanAdapter|null
     */
    protected ?IKanbanAdapter $adapter = null;

    /**
     * Mount the page.
     *
     * @return void
     */
    public function mount(): void
    {
        // This method can be overridden by child classes
    }

    /**
     * Set the status field for the Kanban board.
     *
     * @param string $field
     * @return static
     */
    public function statusField(string $field): static
    {
        $this->statusField = $field;
        
        return $this;
    }

    /**
     * Set the status values for the Kanban board.
     *
     * @param array<string, string> $values
     * @return static
     */
    public function statusValues(array $values): static
    {
        $this->statusValues = $values;
        
        return $this;
    }

    /**
     * Set the title attribute for the Kanban cards.
     *
     * @param string $attribute
     * @return static
     */
    public function titleAttribute(string $attribute): static
    {
        $this->titleAttribute = $attribute;
        
        return $this;
    }

    /**
     * Set the description attribute for the Kanban cards.
     *
     * @param string $attribute
     * @return static
     */
    public function descriptionAttribute(string $attribute): static
    {
        $this->descriptionAttribute = $attribute;
        
        return $this;
    }

    /**
     * Set the card attributes for the Kanban cards.
     *
     * @param array<string, string> $attributes
     * @return static
     */
    public function cardAttributes(array $attributes): static
    {
        $this->cardAttributes = $attributes;
        
        return $this;
    }

    /**
     * Set a custom adapter for the Kanban board.
     *
     * @param IKanbanAdapter $adapter
     * @return static
     */
    public function adapter(IKanbanAdapter $adapter): static
    {
        $this->adapter = $adapter;
        
        return $this;
    }

    /**
     * Get the Kanban adapter.
     *
     * @return IKanbanAdapter
     */
    public function getAdapter(): IKanbanAdapter
    {
        if ($this->adapter) {
            return $this->adapter;
        }

        $model = $this->getModel();
        
        // Check if the model uses the HasKanbanBoard trait
        if (method_exists($model, 'getKanbanAdapter')) {
            // Create an instance and configure it
            $instance = new $model();
            
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
            
            return $instance->getKanbanAdapter();
        }
        
        throw new \Exception('Model does not use the HasKanbanBoard trait.');
    }

    /**
     * Get the model for the resource.
     *
     * @return string
     */
    public function getModel(): string
    {
        return static::getResource()::getModel();
    }
}

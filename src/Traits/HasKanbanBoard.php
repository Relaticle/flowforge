<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Traits;

use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

trait HasKanbanBoard
{
    /**
     * Status field for Kanban board.
     * 
     * @var string|null
     */
    protected ?string $kanbanStatusField = null;
    
    /**
     * Status values for Kanban board.
     * 
     * @var array|null
     */
    protected ?array $kanbanStatusValues = null;
    
    /**
     * Title attribute for Kanban board.
     * 
     * @var string|null
     */
    protected ?string $kanbanTitleAttribute = null;
    
    /**
     * Description attribute for Kanban board.
     * 
     * @var string|null
     */
    protected ?string $kanbanDescriptionAttribute = null;
    
    /**
     * Card attributes for Kanban board.
     * 
     * @var array|null
     */
    protected ?array $kanbanCardAttributes = null;
    
    /**
     * Order field for Kanban board.
     * 
     * @var string|null
     */
    protected ?string $kanbanOrderField = null;
    
    /**
     * Get the Kanban adapter for the model.
     *
     * @return IKanbanAdapter
     */
    public function getKanbanAdapter(): IKanbanAdapter
    {
        return $this->createDefaultKanbanAdapter();
    }

    /**
     * Create a default Kanban adapter for the model.
     *
     * @return IKanbanAdapter
     */
    protected function createDefaultKanbanAdapter(): IKanbanAdapter
    {
        // Get default values or override with methods if defined in the model
        $statusField = $this->getKanbanStatusField();
        $statusValues = $this->getKanbanStatusValues();
        $titleAttribute = $this->getKanbanTitleAttribute();
        $descriptionAttribute = $this->getKanbanDescriptionAttribute();
        $cardAttributes = $this->getKanbanCardAttributes();
        $orderField = $this->getKanbanOrderField();

        return new DefaultKanbanAdapter(
            static::class,
            $statusField,
            $statusValues,
            $titleAttribute,
            $descriptionAttribute,
            $cardAttributes,
            $orderField
        );
    }

    /**
     * Get the status field for Kanban board.
     * 
     * @return string
     */
    public function getKanbanStatusField(): string
    {
        if ($this->kanbanStatusField !== null) {
            return $this->kanbanStatusField;
        }
        
        return method_exists($this, 'kanbanStatusField')
            ? $this->kanbanStatusField()
            : 'status';
    }
    
    /**
     * Set the status field for Kanban board.
     * 
     * @param string $field
     * @return self
     */
    public function setKanbanStatusField(string $field): self
    {
        $this->kanbanStatusField = $field;
        return $this;
    }
    
    /**
     * Get the status values for Kanban board.
     * 
     * @return array<string, string>
     */
    public function getKanbanStatusValues(): array
    {
        if ($this->kanbanStatusValues !== null) {
            return $this->kanbanStatusValues;
        }
        
        return method_exists($this, 'kanbanStatusValues')
            ? $this->kanbanStatusValues()
            : $this->getDefaultStatusValues($this->getKanbanStatusField());
    }
    
    /**
     * Set the status values for Kanban board.
     * 
     * @param array<string, string> $values
     * @return self
     */
    public function setKanbanStatusValues(array $values): self
    {
        $this->kanbanStatusValues = $values;
        return $this;
    }
    
    /**
     * Get the title attribute for Kanban board.
     * 
     * @return string
     */
    public function getKanbanTitleAttribute(): string
    {
        if ($this->kanbanTitleAttribute !== null) {
            return $this->kanbanTitleAttribute;
        }
        
        return method_exists($this, 'kanbanTitleAttribute')
            ? $this->kanbanTitleAttribute()
            : 'name';
    }
    
    /**
     * Set the title attribute for Kanban board.
     * 
     * @param string $attribute
     * @return self
     */
    public function setKanbanTitleAttribute(string $attribute): self
    {
        $this->kanbanTitleAttribute = $attribute;
        return $this;
    }
    
    /**
     * Get the description attribute for Kanban board.
     * 
     * @return string|null
     */
    public function getKanbanDescriptionAttribute(): ?string
    {
        if ($this->kanbanDescriptionAttribute !== null) {
            return $this->kanbanDescriptionAttribute;
        }
        
        return method_exists($this, 'kanbanDescriptionAttribute')
            ? $this->kanbanDescriptionAttribute()
            : null;
    }
    
    /**
     * Set the description attribute for Kanban board.
     * 
     * @param string|null $attribute
     * @return self
     */
    public function setKanbanDescriptionAttribute(?string $attribute): self
    {
        $this->kanbanDescriptionAttribute = $attribute;
        return $this;
    }
    
    /**
     * Get the card attributes for Kanban board.
     * 
     * @return array<string>
     */
    public function getKanbanCardAttributes(): array
    {
        if ($this->kanbanCardAttributes !== null) {
            return $this->kanbanCardAttributes;
        }
        
        return method_exists($this, 'kanbanCardAttributes')
            ? $this->kanbanCardAttributes()
            : [];
    }
    
    /**
     * Set the card attributes for Kanban board.
     * 
     * @param array<string> $attributes
     * @return self
     */
    public function setKanbanCardAttributes(array $attributes): self
    {
        $this->kanbanCardAttributes = $attributes;
        return $this;
    }
    
    /**
     * Get the order field for Kanban board.
     * 
     * @return string|null
     */
    public function getKanbanOrderField(): ?string
    {
        if ($this->kanbanOrderField !== null) {
            return $this->kanbanOrderField;
        }
        
        return method_exists($this, 'kanbanOrderField')
            ? $this->kanbanOrderField()
            : null;
    }
    
    /**
     * Set the order field for Kanban board.
     * 
     * @param string|null $field
     * @return self
     */
    public function setKanbanOrderField(?string $field): self
    {
        $this->kanbanOrderField = $field;
        return $this;
    }

    /**
     * Get the default status values for the model.
     *
     * @param string $statusField The status field
     * @return array<string, string>
     */
    protected function getDefaultStatusValues(string $statusField): array
    {
        $values = static::query()
            ->distinct()
            ->pluck($statusField)
            ->filter()
            ->toArray();
            
        return array_combine($values, array_map(fn ($value) => ucfirst(str_replace('_', ' ', $value)), $values));
    }
}

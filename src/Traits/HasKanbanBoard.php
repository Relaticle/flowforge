<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Traits;

use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;
use Relaticle\Flowforge\Contracts\IKanbanAdapter;

trait HasKanbanBoard
{
    /**
     * Status field for Kanban board.
     */
    protected ?string $kanbanStatusField = null;

    /**
     * Status values for Kanban board.
     */
    protected ?array $kanbanStatusValues = null;

    /**
     * Status colors for Kanban board.
     */
    protected ?array $kanbanStatusColors = null;

    /**
     * Title attribute for Kanban board.
     */
    protected ?string $kanbanTitleAttribute = null;

    /**
     * Description attribute for Kanban board.
     */
    protected ?string $kanbanDescriptionAttribute = null;

    /**
     * Card attributes for Kanban board.
     */
    protected ?array $kanbanCardAttributes = null;

    /**
     * Order field for Kanban board.
     */
    protected ?string $kanbanOrderField = null;

    /**
     * Create form callback for Kanban board.
     *
     * @var callable|null
     */
    protected mixed $kanbanCreateFormCallback = null;

    /**
     * Record label for Kanban board.
     */
    protected ?string $kanbanRecordLabel = null;

    /**
     * Plural record label for Kanban board.
     */
    protected ?string $kanbanPluralRecordLabel = null;

    /**
     * Get the Kanban adapter for the model.
     */
    public function getKanbanAdapter(): IKanbanAdapter
    {
        return $this->createDefaultKanbanAdapter();
    }

    /**
     * Create a default Kanban adapter for the model.
     */
    protected function createDefaultKanbanAdapter(): IKanbanAdapter
    {
        // Get default values or override with methods if defined in the model
        $statusField = $this->getKanbanStatusField();
        $statusValues = $this->getKanbanStatusValues();
        $titleAttribute = $this->getKanbanTitleAttribute();
        $descriptionAttribute = $this->getKanbanDescriptionAttribute();
        $cardAttributes = $this->getKanbanCardAttributes();
        $statusColors = $this->getKanbanStatusColors();
        $orderField = $this->getKanbanOrderField();
        $createFormCallback = $this->getKanbanCreateFormCallback();
        $recordLabel = $this->getKanbanRecordLabel();
        $pluralRecordLabel = $this->getKanbanPluralRecordLabel();

        return new DefaultKanbanAdapter(
            static::class,
            $statusField,
            $statusValues,
            $titleAttribute,
            $descriptionAttribute,
            $cardAttributes,
            $statusColors,
            $orderField,
            $createFormCallback,
            $recordLabel,
            $pluralRecordLabel
        );
    }

    /**
     * Get the status field for Kanban board.
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
     * @param  array<string, string>  $values
     */
    public function setKanbanStatusValues(array $values): self
    {
        $this->kanbanStatusValues = $values;

        return $this;
    }

    /**
     * Get the status colors for Kanban board.
     *
     * @return array<string, string>
     */
    public function getKanbanStatusColors(): array
    {
        if ($this->kanbanStatusColors !== null) {
            return $this->kanbanStatusColors;
        }

        return method_exists($this, 'kanbanStatusColors')
            ? $this->kanbanStatusColors()
            : [];
    }

    /**
     * Set the status colors for Kanban board.
     *
     * @param  array<string, string>  $colors
     */
    public function setKanbanStatusColors(array $colors): self
    {
        $this->kanbanStatusColors = $colors;

        return $this;
    }

    /**
     * Get the title attribute for Kanban board.
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
     */
    public function setKanbanTitleAttribute(string $attribute): self
    {
        $this->kanbanTitleAttribute = $attribute;

        return $this;
    }

    /**
     * Get the description attribute for Kanban board.
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
     * @param  array<string>  $attributes
     */
    public function setKanbanCardAttributes(array $attributes): self
    {
        $this->kanbanCardAttributes = $attributes;

        return $this;
    }

    /**
     * Get the order field for Kanban board.
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
     */
    public function setKanbanOrderField(?string $field): self
    {
        $this->kanbanOrderField = $field;

        return $this;
    }

    public function setKanbanCreateFormCallback(callable $callback): self
    {
        $this->kanbanCreateFormCallback = $callback;

        return $this;
    }

    /**
     * Get the create form callback for Kanban board.
     */
    public function getKanbanCreateFormCallback(): ?callable
    {
        if ($this->kanbanCreateFormCallback !== null) {
            return $this->kanbanCreateFormCallback;
        }

        return method_exists($this, 'kanbanCreateFormCallback')
            ? $this->kanbanCreateFormCallback()
            : null;
    }

    /**
     * Get the record label for Kanban board.
     */
    public function getKanbanRecordLabel(): ?string
    {
        if ($this->kanbanRecordLabel !== null) {
            return $this->kanbanRecordLabel;
        }

        return method_exists($this, 'kanbanRecordLabel')
            ? $this->kanbanRecordLabel()
            : $this->getModelLabelFromClass();
    }

    /**
     * Set the record label for Kanban board.
     */
    public function setKanbanRecordLabel(string $label): self
    {
        $this->kanbanRecordLabel = $label;

        return $this;
    }

    /**
     * Get the plural record label for Kanban board.
     */
    public function getKanbanPluralRecordLabel(): ?string
    {
        if ($this->kanbanPluralRecordLabel !== null) {
            return $this->kanbanPluralRecordLabel;
        }

        return method_exists($this, 'kanbanPluralRecordLabel')
            ? $this->kanbanPluralRecordLabel()
            : $this->getPluralModelLabelFromClass();
    }

    /**
     * Set the plural record label for Kanban board.
     */
    public function setKanbanPluralRecordLabel(string $label): self
    {
        $this->kanbanPluralRecordLabel = $label;

        return $this;
    }

    /**
     * Get model label from class name.
     */
    protected function getModelLabelFromClass(): string
    {
        $reflection = new \ReflectionClass($this);
        $className = $reflection->getShortName();

        return $className;
    }

    /**
     * Get plural model label from class name.
     */
    protected function getPluralModelLabelFromClass(): string
    {
        $singular = $this->getModelLabelFromClass();

        // Simple pluralization - this is a basic implementation
        // You might want to use a more sophisticated pluralization method
        $lastChar = substr($singular, -1);

        if ($lastChar === 'y') {
            return substr($singular, 0, -1) . 'ies';
        }

        if (in_array($lastChar, ['s', 'x', 'z']) || in_array(substr($singular, -2), ['ch', 'sh'])) {
            return $singular . 'es';
        }

        return $singular . 's';
    }

    /**
     * Get the default status values for the model.
     *
     * @param  string  $statusField  The status field
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

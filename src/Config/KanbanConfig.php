<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Config;

use Illuminate\Support\Str;

/**
 * Immutable configuration for a Kanban board.
 *
 * This class serves as the single source of truth for all Kanban board
 * configuration and prevents unintended configuration changes at runtime.
 */
final class KanbanConfig
{
    /**
     * Create a new Kanban configuration instance.
     */
    public function __construct(
        private readonly string $columnField = 'status',
        private readonly array $columnValues = [],
        private readonly ?array $columnColors = null,
        private readonly string $titleField = 'name',
        private readonly ?string $descriptionField = null,
        private readonly array $cardAttributes = [],
        private readonly ?string $orderField = null,
        private readonly ?string $cardLabel = null,
        private readonly ?string $pluralCardLabel = null,
        private readonly mixed $createFormCallback = null,
    ) {
    }

    /**
     * Get the field that stores the column value.
     */
    public function getColumnField(): string
    {
        return $this->columnField;
    }

    /**
     * Get the available column values with their labels.
     *
     * @return array<string, string>
     */
    public function getColumnValues(): array
    {
        return $this->columnValues;
    }

    /**
     * Get the colors for each column.
     *
     * @return array<string, string>|null
     */
    public function getColumnColors(): ?array
    {
        return $this->columnColors;
    }

    /**
     * Get the field used for card titles.
     */
    public function getTitleField(): string
    {
        return $this->titleField;
    }

    /**
     * Get the field used for card descriptions.
     */
    public function getDescriptionField(): ?string
    {
        return $this->descriptionField;
    }

    /**
     * Get the additional fields to display on cards.
     *
     * @return array<string, string>
     */
    public function getCardAttributes(): array
    {
        return $this->cardAttributes;
    }

    /**
     * Get the field used for maintaining card order.
     */
    public function getOrderField(): ?string
    {
        return $this->orderField;
    }

    /**
     * Get the label for individual cards.
     */
    public function getCardLabel(): ?string
    {
        return $this->cardLabel;
    }

    /**
     * Get the plural label for collection of cards.
     */
    public function getPluralCardLabel(): ?string
    {
        return $this->pluralCardLabel;
    }

    /**
     * Get the form callback for creating cards.
     */
    public function getCreateFormCallback(): mixed
    {
        return $this->createFormCallback;
    }

    /**
     * Create a new configuration with the specified property updated.
     *
     * This method supports property modifications via the magic __call method.
     * For example, `withColumnField('status')` will create a new configuration
     * with the columnField property set to 'status'.
     *
     * @param string $method The method name
     * @param array $arguments The method arguments
     * @return self A new instance with the updated property
     * @throws \BadMethodCallException If the method is not a valid with* method
     */
    public function __call(string $method, array $arguments): self
    {
        if (!Str::startsWith($method, 'with')) {
            throw new \BadMethodCallException("Method {$method} not found");
        }

        $property = lcfirst(Str::after($method, 'with'));

        if (!property_exists($this, $property)) {
            throw new \BadMethodCallException("Property {$property} not found");
        }

        return $this->with([$property => $arguments[0]]);
    }

    /**
     * Create a new configuration with the specified properties updated.
     *
     * @param array<string, mixed> $properties The properties to update
     * @return self A new instance with the updated properties
     */
    public function with(array $properties): self
    {
        $config = [];

        foreach ($this as $property => $value) {
            $config[$property] = $properties[$property] ?? $value;
        }

        return new self(...$config);
    }
    
    /**
     * Legacy method for backward compatibility.
     * 
     * @deprecated Use getColumnField() instead
     */
    public function getStatusField(): string
    {
        return $this->columnField;
    }
}
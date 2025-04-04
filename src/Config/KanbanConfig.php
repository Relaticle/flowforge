<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Config;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Livewire\Wireable;

/**
 * Immutable configuration for a Kanban board.
 *
 * This class serves as the single source of truth for all Kanban board
 * configuration and prevents unintended configuration changes at runtime.
 *
 * @method self withColumnField(string $columnField) Set the field name that determines which column a card belongs to
 * @method self withColumnValues(array $columnValues) Set the available column values with their labels
 * @method self withColumnColors(array $columnColors) Set the color mappings for columns
 * @method self withTitleField(string $titleField) Set the field name used for card titles
 * @method self withDescriptionField(string|null $descriptionField) Set the field name for card descriptions
 * @method self withCardAttributes(array $cardAttributes) Set the additional fields to display on cards
 * @method self withOrderField(string|null $orderField) Set the field name for maintaining card order
 * @method self withCardLabel(string|null $cardLabel) Set the label for individual cards
 * @method self withPluralCardLabel(string|null $pluralCardLabel) Set the plural label for collection of cards
 */
final readonly class KanbanConfig implements Wireable
{
    public function __construct(
        private string $columnField = 'status',
        private array $columnValues = [],
        private array | bool | null $columnColors = null,
        private string $titleField = 'title',
        private ?string $descriptionField = null,
        private array $cardAttributes = [],
        private ?string $orderField = null,
        private ?string $cardLabel = null,
        private ?string $pluralCardLabel = null,
    ) {}

    /**
     * Get the field that stores the column value.
     *
     * @return string The column field name
     */
    public function getColumnField(): string
    {
        return $this->columnField;
    }

    /**
     * Get the available column values with their labels.
     *
     * @return array<string, string> Map of column values to their display labels
     */
    public function getColumnValues(): array
    {
        return $this->columnValues;
    }

    /**
     * Get the colors for each column.
     *
     * @return array|bool|null Map of column values to color codes, or null if not set
     */
    public function getColumnColors(): array | bool | null
    {
        return $this->columnColors;
    }

    /**
     * Get the field used for card titles.
     *
     * @return string The title field name
     */
    public function getTitleField(): string
    {
        return $this->titleField;
    }

    /**
     * Get the field used for card descriptions.
     *
     * @return string|null The description field name, or null if not set
     */
    public function getDescriptionField(): ?string
    {
        return $this->descriptionField;
    }

    /**
     * Get the additional fields to display on cards.
     *
     * @return array<string, string> Map of attribute names to their display labels
     */
    public function getCardAttributes(): array
    {
        return $this->cardAttributes;
    }

    /**
     * Get the field used for maintaining card order.
     *
     * @return string|null The order field name, or null if ordering is not enabled
     */
    public function getOrderField(): ?string
    {
        return $this->orderField;
    }

    /**
     * Get the label for individual cards.
     *
     * @return string|null The singular card label, or null to use default
     */
    public function getCardLabel(): ?string
    {
        return $this->cardLabel;
    }

    /**
     * Get the plural label for collection of cards.
     *
     * @return string|null The plural card label, or null to use default
     */
    public function getPluralCardLabel(): ?string
    {
        return $this->pluralCardLabel;
    }

    /**
     * Get the default form schema for creating cards.
     *
     * @param  string  $titleField  The field name used for card titles
     * @param  string|null  $descriptionField  Optional field name for card descriptions
     * @return array<\Filament\Forms\Components\Component> The default form schema
     */
    public static function getDefaultCreateFormSchema(string $titleField, ?string $descriptionField): array
    {
        $schema = [
            TextInput::make($titleField)
                ->required()
                ->maxLength(255),
        ];

        if ($descriptionField !== null) {
            $schema[] = Textarea::make($descriptionField)->rows(3);
        }

        return $schema;
    }

    /**
     * Get the default form schema for editing cards.
     *
     * @param  string  $titleField  The field name used for card titles
     * @param  string|null  $descriptionField  Optional field name for card descriptions
     * @param  string  $columnField  The field name that determines which column a card belongs to
     * @param  array<string, string>  $columnValues  Available column values with their labels
     * @return array<\Filament\Forms\Components\Component> The default form schema
     */
    public static function getDefaultEditFormSchema(
        string $titleField,
        ?string $descriptionField,
        string $columnField,
        array $columnValues
    ): array {
        $schema = [
            TextInput::make($titleField)
                ->required()
                ->maxLength(255),
        ];

        if ($descriptionField !== null) {
            $schema[] = Textarea::make($descriptionField)
                ->rows(3);
        }

        $schema[] = Select::make($columnField)
            ->options($columnValues)
            ->required();

        return $schema;
    }

    /**
     * Create a new configuration with the specified property updated.
     *
     * This method supports property modifications via the magic __call method.
     * For example, `withColumnField('status')` will create a new configuration
     * with the columnField property set to 'status'.
     *
     * @param  string  $method  The method name
     * @param  array  $arguments  The method arguments
     * @return self A new instance with the updated property
     *
     * @throws \BadMethodCallException If the method is not a valid with* method or targets a non-existent property
     */
    public function __call(string $method, array $arguments): self
    {
        if (! Str::startsWith($method, 'with')) {
            throw new \BadMethodCallException("Method {$method} not found");
        }

        $property = lcfirst(Str::after($method, 'with'));

        if (! property_exists($this, $property)) {
            throw new \BadMethodCallException("Property {$property} not found");
        }

        return $this->with([$property => $arguments[0]]);
    }

    /**
     * Create a new configuration with the specified properties updated.
     *
     * @param  array<string, mixed>  $properties  The properties to update
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

    public function toLivewire(): array
    {
        return [
            'columnField' => $this->columnField,
            'columnValues' => $this->columnValues,
            'columnColors' => $this->columnColors,
            'titleField' => $this->titleField,
            'descriptionField' => $this->descriptionField,
            'cardAttributes' => $this->cardAttributes,
            'orderField' => $this->orderField,
            'cardLabel' => $this->cardLabel,
            'pluralCardLabel' => $this->pluralCardLabel,
        ];
    }

    public static function fromLivewire($value): KanbanConfig
    {
        return new KanbanConfig(
            $value['columnField'],
            $value['columnValues'],
            $value['columnColors'],
            $value['titleField'],
            $value['descriptionField'],
            $value['cardAttributes'],
            $value['orderField'],
            $value['cardLabel'],
            $value['pluralCardLabel']
        );
    }
}

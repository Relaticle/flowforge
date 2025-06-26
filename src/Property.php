<?php

namespace Relaticle\Flowforge;

use Exception;

class Property
{
    protected ?string $label = null;

    protected string $name;

    protected string | \BackedEnum | null $icon = null;

    final public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(?string $name = null): static
    {
        $columnClass = static::class;

        $name ??= static::getDefaultName();

        if (blank($name)) {
            throw new Exception("Column of class [$columnClass] must have a unique name, passed to the [make()] method.");
        }

        $static = app($columnClass, ['name' => $name]);
        $static->configure();

        return $static;
    }

    public static function getDefaultName(): ?string
    {
        return null;
    }

    protected function configure(): void
    {
        // Override in subclasses if needed
    }

    public function icon(string | \BackedEnum $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getIcon(): string | \BackedEnum | null
    {
        return $this->icon;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

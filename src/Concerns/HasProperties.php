<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Concerns;

use Closure;
use Relaticle\Flowforge\Property;

trait HasProperties
{
    /**
     * @var array<string, Property>
     */
    protected array $cardProperties = [];

    /**
     * @param array<Property> $properties
     */
    public function cardProperties(array $properties): static
    {
        $this->cardProperties = [];
        $this->pushCardProperties($properties);

        return $this;
    }

    /**
     * @param array<Property> $properties
     */
    public function pushCardProperties(array $properties): static
    {
        foreach ($properties as $property) {
            $this->pushCardProperty($property);
        }

        return $this;
    }

    public function pushCardProperty(Property $property): static
    {
        $property->board($this);
        $this->cardProperties[$property->getName()] = $property;

        return $this;
    }

    /**
     * @return array<string, Property>
     */
    public function getCardProperties(): array
    {
        return $this->cardProperties;
    }

    public function getCardProperty(string $name): ?Property
    {
        return $this->cardProperties[$name] ?? null;
    }

    public function hasCardProperty(string $name): bool
    {
        return array_key_exists($name, $this->cardProperties);
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'cardProperties' => [$this->getCardProperties()],
            'properties' => [$this->getCardProperties()], // alias
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
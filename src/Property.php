<?php

namespace Relaticle\Flowforge;

use Exception;
use Filament\Support\Components\ViewComponent;
use Relaticle\Flowforge\Concerns\HasIcon;
use Relaticle\Flowforge\Concerns\HasColor;
use Relaticle\Flowforge\Concerns\CanBeVisible;
use Relaticle\Flowforge\Concerns\HasStateFormatting;

class Property extends ViewComponent
{
    use HasIcon;
    use HasColor;
    use CanBeVisible;
    use HasStateFormatting;

    /**
     * @var view-string
     */
    protected string $view = 'flowforge::property';

    protected string $viewIdentifier = 'property';

    protected string $evaluationIdentifier = 'property';

    protected ?string $label = null;

    protected string $name;

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

    protected function setUp(): void
    {
        parent::setUp();

        // Override in subclasses if needed
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'property' => [$this],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}

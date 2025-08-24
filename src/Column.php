<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Closure;
use Exception;
use Filament\Support\Components\ViewComponent;
use Filament\Support\Concerns\HasColor;
use Filament\Support\Concerns\HasIcon;
use Filament\Support\Concerns\HasIconColor;
use Filament\Support\Concerns\HasIconPosition;
use Illuminate\Contracts\Support\Htmlable;
use Relaticle\Flowforge\Concerns\BelongsToBoard;

class Column extends ViewComponent
{
    use BelongsToBoard;
    use HasColor;
    use HasIcon;
    use HasIconColor;
    use HasIconPosition;

    protected string $view = 'flowforge::column';

    protected string $viewIdentifier = 'column';

    protected string $evaluationIdentifier = 'column';

    protected string | Htmlable | Closure | null $label = null;

    protected bool $shouldTranslateLabel = false;

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

    public function label(string | Htmlable | Closure | null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function translateLabel(bool $shouldTranslateLabel = true): static
    {
        $this->shouldTranslateLabel = $shouldTranslateLabel;

        return $this;
    }

    public function getLabel(): string | Htmlable | null
    {
        $label = $this->evaluate($this->label) ?? $this->generateDefaultLabel();

        return $this->shouldTranslateLabel ? __($label) : $label;
    }

    protected function generateDefaultLabel(): string
    {
        return str($this->getName())
            ->kebab()
            ->replace(['-', '_'], ' ')
            ->title()
            ->toString();
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
            'column' => [$this],
            'name' => [$this->getName()],
            'label' => [$this->getLabel()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}

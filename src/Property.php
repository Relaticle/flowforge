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
use Illuminate\Database\Eloquent\Model;
use Relaticle\Flowforge\Concerns\BelongsToBoard;
use Relaticle\Flowforge\Concerns\HasStateFormatting;

class Property extends ViewComponent
{
    use BelongsToBoard;
    use HasColor;
    use HasIcon;
    use HasIconColor;
    use HasIconPosition;
    use HasStateFormatting;

    /**
     * @var view-string
     */
    protected string $view = 'flowforge::property';

    protected string $viewIdentifier = 'property';

    protected string $evaluationIdentifier = 'property';

    protected string | Htmlable | Closure | null $label = null;

    protected bool $shouldTranslateLabel = false;

    protected bool $isIconOnly = false;

    protected mixed $currentState = null;

    protected string $name;

    final public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(?string $name = null): static
    {
        $propertyClass = static::class;

        $name ??= static::getDefaultName();

        if (blank($name)) {
            throw new Exception("Property of class [$propertyClass] must have a unique name, passed to the [make()] method.");
        }

        $static = app($propertyClass, ['name' => $name]);
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
        if ($this->isIconOnly) {
            return null;
        }

        $label = $this->evaluate($this->label) ?? $this->generateDefaultLabel();

        return $this->shouldTranslateLabel ? __($label) : $label;
    }

    protected function generateDefaultLabel(): string
    {
        return str($this->getName())
            ->afterLast('.')
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
     * Configure this property to display only an icon without a label.
     */
    public function iconProperty(): static
    {
        $this->isIconOnly = true;

        return $this;
    }

    /**
     * Get the state value from a record using dot notation
     */
    public function getState(Model $record): mixed
    {
        return data_get($record, $this->getName());
    }

    /**
     * Get the formatted state for display
     */
    public function getFormattedState(Model $record): mixed
    {
        $state = $this->getState($record);
        $this->currentState = $state;

        return $this->formatState($state);
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'property' => [$this],
            'name' => [$this->getName()],
            'label' => [$this->getLabel()],
            'state' => [$this->currentState ?? null],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}

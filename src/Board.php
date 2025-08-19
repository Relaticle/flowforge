<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Filament\Support\Components\ViewComponent;
use Relaticle\Flowforge\Concerns\HasActions;
use Relaticle\Flowforge\Concerns\HasColumns;
use Relaticle\Flowforge\Concerns\HasProperties;
use Relaticle\Flowforge\Concerns\InteractsWithKanbanQuery;

class Board extends ViewComponent
{
    use HasActions;
    use HasColumns;
    use HasProperties;
    use InteractsWithKanbanQuery;

    /**
     * @var view-string
     */
    protected string $view = 'flowforge::board';

    protected string $viewIdentifier = 'board';

    protected string $evaluationIdentifier = 'board';

    protected array $filters = [];

    final public function __construct()
    {
        // Board should be instantiated via make() method only
    }

    public static function make(): static
    {
        $boardClass = static::class;

        $static = app($boardClass);
        $static->configure();

        return $static;
    }

    public function filters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'board' => [$this],
            'filters' => [$this->getFilters()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Any board-specific setup can go here
    }
}

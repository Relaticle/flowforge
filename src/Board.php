<?php

declare(strict_types=1);

namespace Relaticle\Flowforge;

use Filament\Support\Components\ViewComponent;
use Relaticle\Flowforge\Concerns\BelongsToLivewire;
use Relaticle\Flowforge\Concerns\HasActions;
use Relaticle\Flowforge\Concerns\HasCardAction;
use Relaticle\Flowforge\Concerns\HasColumns;
use Relaticle\Flowforge\Concerns\HasProperties;
use Relaticle\Flowforge\Concerns\InteractsWithKanbanQuery;
use Relaticle\Flowforge\Contracts\HasBoard;

class Board extends ViewComponent
{
    use BelongsToLivewire;
    use HasActions;
    use HasCardAction;
    use HasColumns;
    use HasProperties;
    use InteractsWithKanbanQuery;

    /**
     * @var view-string
     */
    protected string $view = 'flowforge::board';

    protected string $viewIdentifier = 'board';

    protected string $evaluationIdentifier = 'board';


    final public function __construct(HasBoard $livewire)
    {
        $this->livewire($livewire);
    }

    public static function make(HasBoard $livewire): static
    {
        $static = app(static::class, ['livewire' => $livewire]);
        $static->configure();

        return $static;
    }


    protected function setUp(): void
    {
        parent::setUp();

        // Any board-specific setup can go here
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'livewire' => [$this->getLivewire()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}
